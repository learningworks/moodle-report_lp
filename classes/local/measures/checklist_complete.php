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

namespace report_lp\local\measures;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use MoodleQuickForm;
use pix_icon;
use report_lp\local\contracts\extra_configuration;
use report_lp\local\measure;
use report_lp\local\persistents\item_configuration;
use report_lp\local\user_list;
use report_lp\output\cell;
use stdClass;
use checklist_class;
use mod_checklist\local\checklist_item;
use html_writer;
use core_plugin_manager;

/**
 * Checklist completion status.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checklist_complete extends measure implements extra_configuration {

    /** @var string COMPONENT_TYPE Used to identify core or plugin type. Moodle frankenstyle. */
    public const COMPONENT_TYPE = 'mod';

    /** @var string COMPONENT_NAME Used to for name of core subsystem or plugin. Moodle frankenstyle. */
    public const COMPONENT_NAME = 'checklist';

    /** @var stdClass $checklist Instance record for checklist */
    protected $checklist;

    /** @var array $checklistitems Checklist items. */
    protected $checklistitems;

    /** @var int $checklistitemstotal Total of items in checklist instance. */
    protected $checklistitemstotal;

    /**
     * checklist_complete constructor.
     *
     * Load any required libaries.
     *
     * @param stdClass|null $course
     */
    public function __construct(stdClass $course = null) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/checklist/lib.php');
        require_once($CFG->dirroot . '/mod/checklist/locallib.php');
        parent::__construct($course);
    }

    public function build_data_cell($user) {
        $percentcomplete = ' - ';
        if (!empty($user->data->percentcomplete)) {
            $percentcomplete = floor($user->data->percentcomplete) . '%';
        }
        $cell = new cell();
        $cell->plaintextcontent = $percentcomplete;
        $cell->htmlcontent = html_writer::span($percentcomplete, 'measure');
        return $cell;
    }

    /**
     * Format user measure data.
     *
     * @param $data
     * @param string $format
     * @return string
     */
    public function format_user_measure_data($data, $format = FORMAT_PLAIN) : string {
        $label = ' - ';
        if (!empty($data)) {
            $label = floor($data) . '%';
        }
        $class = "measure";
        if ($format == FORMAT_HTML) {
            return html_writer::span($label, $class);
        }
        return $label;
    }

    /**
     * Load all required checklist data.
     *
     * @throws \dml_exception
     * @throws coding_exception
     */
    protected function load_checklist() {
        global $DB;

        if (is_null($this->checklist)) {
            $configuration = $this->get_configuration();
            if (is_null($configuration)) {
                throw new coding_exception('Configuration must loaded');
            }
            $extraconfigurationdata = $configuration->get('extraconfigurationdata');
            if (!isset($extraconfigurationdata->id)) {
                throw new coding_exception('No valid extra configuration data found');
            }
            $this->checklist = $DB->get_record(
                'checklist',
                ['id' => $extraconfigurationdata->id],
                '*',
                MUST_EXIST
            );
        }
        if (is_null($this->checklistitems)) {
            $items = checklist_item::fetch_all(['checklist' => $this->checklist->id, 'userid' => 0], true);
            $checklistitems = [];
            foreach ($items as $item) {
                if (!$item->hidden) {
                    if ($item->itemoptional == CHECKLIST_OPTIONAL_NO) {
                        $checklistitems[$item->id] = $item;
                    }
                }
            }
            $this->checklistitems = $checklistitems;
        }
        if (is_null($this->checklistitemstotal)) {
            $this->checklistitemstotal = count($this->checklistitems);
        }
    }

    /**
     * Get percentage completed.
     *
     * @param stdClass $user
     * @return stdClass
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function get_data_for_user(stdClass $user) : stdClass {
        global $DB;

        $this->load_checklist();

        if ($this->checklistitemstotal) {
            list($insql, $inparameters) = $DB->get_in_or_equal(array_keys($this->checklistitems), SQL_PARAMS_NAMED);
            if ($this->checklist->teacheredit == CHECKLIST_MARKING_STUDENT) {
                $sql = "usertimestamp > 0 AND
                        item {$insql} AND 
                        userid = :userid ";
            } else {
                $sql = 'teachermark = ' . CHECKLIST_TEACHERMARK_YES .' AND item ' . $insql . ' AND userid = :userid ';
            }
        }
        if ($this->checklistitemstotal) {
            $inparameters['userid'] = $user->id;
            $tickeditems = $DB->count_records_select('checklist_check', $sql, $inparameters);
            $percentcomplete = ($tickeditems * 100) / $this->checklistitemstotal;
        } else {
            $percentcomplete = 0;
            $tickeditems = 0;
        }

        $data = new stdClass();
        $data->percentcomplete = $percentcomplete;
        $data->tickeditems = $tickeditems;
        $user->data = $data;

        return $user;
    }

    /**
     * @param user_list $userlist
     * @return array
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function get_data_for_users(user_list $userlist) : array {

        $this->load_checklist();

        $data = [];
        foreach ($userlist as $user) {
            $data[$user->id] = $this->get_data_for_user($user->id);
        }
        return $data;
    }

    /**
     * Default label for checklist complete.
     *
     * @return string
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function get_default_label(): string {
        if (is_null( $this->get_id()) || $this->get_id() <= 0) {
            return format_string(
                get_string('checklistcomplete:measure:defaultlabel', 'report_lp'),
                FORMAT_PLAIN
            );
        }
        $this->load_checklist();
        return format_text($this->checklist->name, FORMAT_PLAIN);
    }

    /**
     * Name of measure.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string('checklistcomplete:measure:name', 'report_lp');
    }

    /**
     * Description of what data/information this measure displays.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_description(): string {
        return get_string('checklistcomplete:measure:description', 'report_lp');
    }

    /**
     * Get checklists already used in this course for this measure.
     *
     * @return array
     * @throws \ReflectionException
     * @throws coding_exception
     */
    protected function get_excluded_checklists() {
        $excludes = [];
        $configurations = item_configuration::get_records_select(
            "id <> :id AND courseid = :courseid AND shortname = :shortname",
            [
                'id' => $this->get_configuration()->get('id'),
                'courseid' => $this->get_configuration()->get('courseid'),
                'shortname' => static::get_short_name()
            ]
        );
        foreach ($configurations as $configuration) {
            $extraconfigurationdata = $configuration->get('extraconfigurationdata');
            if (isset($extraconfigurationdata->id)) {
                $excludes[] = $extraconfigurationdata->id;
            }
        }
        return $excludes;
    }

    /**
     * Get available checklists in this course to choose from. Only one checklist
     * per measure.
     *
     * @return array
     * @throws \ReflectionException
     * @throws \dml_exception
     * @throws coding_exception
     */
    protected function get_checklist_options() {
        global $DB;
        $excludes = $this->get_excluded_checklists();
        $params = ['course' => $this->get_configuration()->get('courseid')];
        $select = "course = :course";
        if ($excludes) {
            [$notinsql, $notinparams] = $DB->get_in_or_equal(
                $excludes,
                SQL_PARAMS_NAMED,
                'a',
                false
            );
            $params = array_merge($notinparams, $params);
            $select = "course = :course AND id $notinsql";
        }
        $options = $DB->get_records_select_menu(
            static::COMPONENT_NAME,
            $select,
            $params,
            null,
            'id, name'
        );
        return $options;
    }

    /**
     * Use checklist icon.
     *
     * @return pix_icon|null
     * @throws coding_exception
     */
    public function get_icon() : ? pix_icon {
        return new pix_icon('icon', get_string('pluginname', 'checklist'), 'mod_checklist');
    }

    /**
     * Yes we do.
     *
     * @return bool
     */
    public function has_icon() : bool {
        return true;
    }

    /**
     * Extend main item mform to allow choice of checklist to measure as
     * implements own configuration.
     *
     * @param MoodleQuickForm $mform
     * @return mixed|void
     * @throws \ReflectionException
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function moodlequickform_extend(MoodleQuickForm &$mform) {
        $checklists = $this->get_checklist_options();
        if (empty($checklists)) {
            $mform->addElement(
                'warning',
                'nochecklistswarning',
                null,
                get_string('noavailablemodules', 'report_lp', static::COMPONENT_NAME)
            );
            $mform->addElement('hidden', 'nochecklists');
            $mform->setType('nochecklists', PARAM_INT);
            $mform->setDefault('nochecklists', 1);
            $mform->disabledIf('submitbutton', 'nochecklists', 'eq', 1);
            $mform->removeElement('specific');
        } else {
            $options = [0 => get_string('choose')] +  $checklists;
            $mform->addElement('select', 'checklist',
                get_string('checklistcomplete:measure:name', 'report_lp'), $options);
        }
    }

    /**
     * Extend validation for extra configuration.
     *
     * @param $data
     * @param $files
     * @return array
     * @throws coding_exception
     */
    public function moodlequickform_validation($data, $files) : array {
        $errors = [];
        if ($data['checklist'] == 0) {
            $errors['checklist'] = get_string('pleasechoose', 'report_lp');
        }
        return $errors;
    }

    /**
     * Format extra configuration data.
     *
     * @param $data
     * @return stdClass
     * @throws coding_exception
     */
    public function moodlequickform_get_extra_configuration_data($data) : stdClass {
        if (empty($data['checklist'])) {
            throw new coding_exception('Something went horribly wrong');
        }
        $object = new stdClass();
        $object->id = $data['checklist'];
        return $object;
    }

    /**
     * Get defaults based on extra configuration data.
     *
     * @return array
     * @throws coding_exception
     */
    public function moodlequickform_get_extra_configuration_defaults() : array {
        $configuration = $this->get_configuration();
        $extraconfigurationdata = $configuration->get('extraconfigurationdata');
        $defaults = [];
        if (empty($extraconfigurationdata)) {
            $defaults['checklist'] = 0;
        } else {
            $defaults['checklist'] = $extraconfigurationdata->id;
        }
        return $defaults;
    }

}
