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

global $CFG;
require_once($CFG->libdir . '/gradelib.php');

use coding_exception;
use grade_category;
use report_lp\local\measure;
use report_lp\local\user_list;
use html_writer;
use stdClass;

/**
 * Final course grade measure, taken from gradebook.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_grade extends measure {

    /** @var string COMPONENT_TYPE Used to identify core subsystem or plugin type. Moodle frankenstyle. */
    public const COMPONENT_TYPE = 'core';

    /** @var string COMPONENT_NAME Used to for name of core subsystem or plugin. Moodle frankenstyle. */
    public const COMPONENT_NAME = 'grades';

    /** @var grade_item $coursegradeitem */
    protected $coursegradeitem;

    /**
     * @param $data
     * @param string $format
     * @return string
     * @throws coding_exception
     */
    public function format_user_measure_data($data, $format = FORMAT_PLAIN) : string {
        $label = ' - ';
        $status = 'none';
        if (isset($data->finalgrade) && !is_null($data->finalgrade)) {
            $passed = $this->coursegradeitem->get_grade($data->userid)->is_passed($this->coursegradeitem);
            if ($passed) {
                $label = get_string('achieved', 'report_lp');
                $status = 'achieved';
            } else {
                $label = get_string('notachieved', 'report_lp');
                $status = 'not-achieved';
            }
        }
        $class = "measure measure--status-{$status}";
        if ($format == FORMAT_HTML) {
            return html_writer::span($label, $class);
        }
        return $label;
    }

    /**
     * @param stdClass $user
     * @return stdClass
     * @throws coding_exception
     */
    public function get_data_for_user(stdClass $user) : stdClass {
        $this->load_course_grade_item();
        $finalgrade = $this->coursegradeitem->get_final($user->id);
        return $finalgrade;
    }

    /**
     * @param user_list $userlist
     * @return array
     * @throws coding_exception
     */
    public function get_data_for_users(user_list $userlist) : array {
        $this->load_course_grade_item();
        $data = [];
        foreach ($userlist as $user) {
            $data[$user->id] = $this->get_data_for_user($user->id);
        }
        return $data;
    }

    /**
     * Default label for course grade.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_default_label(): string {
        return format_text(
            get_string('coursegrade:measure:label', 'report_lp'), FORMAT_PLAIN
        );
    }

    /**
     * Name of measure.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string('coursegrade:measure:name', 'report_lp');
    }

    /**
     * Description of what data/information this measure displays.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_description(): string {
        return get_string('coursegrade:measure:description', 'report_lp');
    }

    /**
     * Is core so enabled.
     *
     * @return bool|null
     */
    public function is_enabled() {
        return true;
    }

    /**
     * @return \grade_item|grade_item
     * @throws coding_exception
     */
    protected function load_course_grade_item() {
        if (is_null($this->coursegradeitem)) {
            $configuration = $this->get_configuration();
            if (is_null($configuration)) {
                throw new coding_exception('Configuration must loaded');
            }
            $coursegradeitem = grade_category::fetch_course_category($configuration->get('courseid'));
            $this->coursegradeitem = $coursegradeitem->get_grade_item();
        }
        return $this->coursegradeitem;
    }

}
