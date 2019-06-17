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

use stdClass;
use MoodleQuickForm;
use coding_exception;
use report_lp\local\contracts\has_own_configuration;
use report_lp\local\measure;
use report_lp\local\userlist;

/**
 * Assignment status of learner for an assignment instance.
 *
 * @package
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class assignment_status extends measure implements has_own_configuration {

    public function get_data_for_users(userlist $userlist) : ? array {
        return [];
    }

    public function get_default_label(): ? string {
        global $DB;
        $configuration = $this->get_configuration();
        if (is_null($configuration)) {
            return get_string('assignmentstatusdefaultlabel', 'report_lp');
        }
        $extraconfigurationdata = $configuration->get('extraconfigurationdata');
        if (empty($extraconfigurationdata)) {
            return get_string('assignmentstatusdefaultlabel', 'report_lp');
        }
        $assignmentname = $DB->get_field(
            'assign',
            'name',
            ['id' => $extraconfigurationdata->assignmentid]
        );
        return format_text($assignmentname);
    }

    public function get_name(): string {
        return get_string('assignment_status:measure:name', 'report_lp');
    }


    public function get_description(): string {
        return get_string('assignment_status:measure:description', 'report_lp');
    }

    public function moodlequickform_extend(MoodleQuickForm &$mform) {
        global $DB;

        $configuration = $this->get_configuration();
        $courseid = $configuration->get('courseid');
        if ($courseid <= 0 ) {
            throw new coding_exception("Configuration does not have courseid set");
        }
        $options = $DB->get_records_menu(
            'assign',
            ['course' => $courseid],
            'id',
            'id, name'
        );
        $options = array_merge([0 => get_string('choose')], $options);
        $mform->addElement('select', 'assignment',
            get_string('assignmentname', 'mod_assign'), $options);
    }

    public function moodlequickform_validation($data, $files) : array {
        $errors = [];
        if ($data['assignment'] == 0) {
            $errors['assignment'] = get_string('pleasechoose', 'report_lp');
        }
        return $errors;
    }

    public function moodlequickform_get_extra_configuration_data($data) : stdClass {
        if (empty($data['assignment'])) {
            throw new coding_exception('Something went horribly wrong');
        }
        $object = new stdClass();
        $object->assignmentid = $data['assignment'];
        return $object;
    }

    public function moodlequickform_get_extra_configuration_defaults() : array {
        $configuration = $this->get_configuration();
        $extraconfigurationdata = $configuration->get('extraconfigurationdata');
        $defaults = [];
        if (empty($extraconfigurationdata)) {
            $defaults['assignment'] = 0;
        } else {
            $defaults['assignment'] = $extraconfigurationdata->assignmentid;
        }
        return $defaults;
    }

}
