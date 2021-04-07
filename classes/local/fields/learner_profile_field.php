<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace report_lp\local\fields;

use MoodleQuickForm;
use report_lp\local\contracts\extra_configuration;
use report_lp\local\learner_field;
use report_lp\local\user_list;
use report_lp\output\cell;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class learner_profile_field extends learner_field implements extra_configuration {

    /** @var string $fieldinputname Used as reference to field stored on User object. Example $user->profile_field_nsn. */
    public $fieldinputname;

    /**
     * Build field class based off extra configuration data.
     *
     * @return \profile_field_base $field
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_field() : \profile_field_base {
        global $DB, $CFG;
        try {
            $fieldid = $this->get_configuration()->get('extraconfigurationdata')->fieldid ?? null;
            if (is_null($fieldid)) {
                throw new \coding_exception('nofieldiddefinedinextraconfig');
            }
            $fieldrecord = $DB->get_record('user_info_field', ['id' => $fieldid], '*', MUST_EXIST);
            require_once($CFG->dirroot . '/user/profile/field/' . $fieldrecord->datatype . '/field.class.php');
            $classname= 'profile_field_' . $fieldrecord->datatype;
            /** @var profile_field_base $fieldobject */
            $field = new $classname($fieldrecord->id);
            $this->fieldinputname = $field->inputname;
            return $field;
        } catch (\coding_exception $exception) {
            throw $exception;
        }
    }

    /**
     * Build field class based off extra configuration and load user data into class.
     *
     * @param stdClass $user
     * @return mixed|\profile_field_base $field
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_field_with_user_data(stdClass $user) : \profile_field_base {
        global $DB, $CFG;
        try {
            $fieldid = $this->get_configuration()->get('extraconfigurationdata')->fieldid ?? null;
            if (is_null($fieldid)) {
                throw new \coding_exception('nofieldiddefinedinextraconfig');
            }
            $sql = "SELECT uif.*, uic.name AS categoryname, uind.id AS hasuserdata, uind.data, uind.dataformat
                      FROM {user_info_field} uif
                 LEFT JOIN {user_info_category} uic ON uic.id = uif.categoryid 
                 LEFT JOIN {user_info_data} uind ON uind.fieldid = uif.id
                     WHERE uif.id = :fieldid AND uind.userid = :userid";
            $fieldrecord = $DB->get_record_sql($sql, ['fieldid' => $fieldid, 'userid' => $user->id]);
            if (!$fieldrecord) {
                return $this->get_field();
            }
            require_once($CFG->dirroot . '/user/profile/field/' . $fieldrecord->datatype . '/field.class.php');
            $classname = 'profile_field_' . $fieldrecord->datatype;
            /** @var profile_field_base $fieldobject */
            $field = new $classname($fieldrecord->id, $user->id, $fieldrecord);
            $this->fieldinputname = $field->inputname;
            $field->set_category_name($fieldrecord->categoryname);
            unset($fieldrecord->categoryname);
            return $field;
        } catch (\coding_exception $exception) {
            throw $exception;
        }
    }

    public function build_data_cell($user) {
        $cell = new cell();
        $cell->class = "cell";
        if (isset($user->{$this->fieldinputname})) {
            $cell->plaintextcontent = $user->{$this->fieldinputname}->display_data();
        } else {
            $cell->plaintextcontent = '';
        }
        return $cell;
    }

    /**
     * @inheritDoc
     */
    public function get_data_for_user(stdClass $user): stdClass
    {
        $field = $this->get_field_with_user_data($user);
        if ($field) {
            $user->{$field->inputname} = $field;
        }
        return $user;
    }

    /**
     * @inheritDoc
     */
    public function get_data_for_users(user_list $userlist): array
    {
        $data = [];
        foreach ($userlist as $user) {
            $data[$user->id] = $this->get_data_for_user($user);
        }
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function moodlequickform_extend(MoodleQuickForm &$mform)
    {
        global $DB;
        $learnerprofilefields = [];
        $categories = $DB->get_records('user_info_category', null, 'sortorder ASC');
        foreach ($categories as $category) {
            $fields = $DB->get_records('user_info_field',['categoryid' => $category->id], 'sortorder ASC');
            foreach ($fields as $field) {
                $learnerprofilefields[$field->id] = format_text($category->name . '/' . $field->name);
            }
        }
        if (empty($learnerprofilefields)) {
            $mform->addElement(
                'warning',
                'nolearnerprofilefieldswarning',
                null,
                get_string('nolearnerprofilefields', 'report_lp')
            );
            $mform->addElement('hidden', 'nolearnerprofilefields');
            $mform->setType('nolearnerprofilefields', PARAM_INT);
            $mform->setDefault('nolearnerprofilefields', 1);
            $mform->disabledIf('submitbutton', 'nolearnerprofilefields', 'eq', 1);
            $mform->removeElement('specific');
        } else {
            $options = [0 => get_string('choose')] +  $learnerprofilefields;
            $mform->addElement('select', 'field',
                get_string('fieldname', 'report_lp'), $options);
        }

    }

    public function moodlequickform_validation($data, $files): array
    {
        $errors = [];
        if ($data['field'] == 0) {
            $errors['field'] = get_string('pleasechoose', 'report_lp');
        }
        return $errors;
    }


    public function moodlequickform_get_extra_configuration_data($data): stdClass
    {
        if (empty($data['field'])) {
            throw new coding_exception('Something went horribly wrong');
        }

        $object = new stdClass();
        $object->fieldid = $data['field'];
        return $object;
    }


    public function moodlequickform_get_extra_configuration_defaults(): array
    {
        $configuration = $this->get_configuration();
        $extraconfigurationdata = $configuration->get('extraconfigurationdata');
        $defaults = [];
        if (empty($extraconfigurationdata)) {
            $defaults['field'] = 0;
        } else {
            $defaults['field'] = $extraconfigurationdata->fieldid;
        }
        return $defaults;
    }

    /**
     * @inheritDoc
     */
    public function get_name(): string
    {
        return get_string('profilefield:learnerfield:name', 'report_lp');
    }

    /**
     * @inheritDoc
     */
    public function get_default_label(): string
    {
        global $DB;
        if (!empty($this->get_id())) {
            $extraconfigurationdata = $this->get_extraconfigurationdata();
            if ($extraconfigurationdata instanceof stdClass) {
                $field = $DB->get_record('user_info_field', ['id' => $extraconfigurationdata->fieldid]);
                return format_text($field->name, FORMAT_PLAIN);
            }
        }
        return get_string('profilefield:learnerfield:label', 'report_lp');
    }

    /**
     * @inheritDoc
     */
    public function get_description(): string
    {
        return get_string('profilefield:learnerfield:description', 'report_lp');
    }
}
