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
use html_writer;
use report_lp\local\measure;
use report_lp\local\user_list;
use report_lp\output\cell;
use stdClass;

/**
 * The date and time learner last accessed a course instance.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class last_course_access extends measure {

    /** @var string COMPONENT_TYPE Used to identify core subsystem or plugin type. Moodle frankenstyle. */
    public const COMPONENT_TYPE = 'core';

    /** @var string COMPONENT_NAME Used to for name of core subsystem or plugin. Moodle frankenstyle. */
    public const COMPONENT_NAME = 'course';

    public function build_data_cell($user) {
        if (is_null($user->data->lastaccess)) {
            $label = get_string('never');
            $title = $label;
        } else {
            $label = userdate($user->data->timeaccess, '%A %e %B, %H:%M');
            $title = userdate($user->data->timeaccess);
        }
        $class = "measure";
        $cell = new cell();
        $cell->plaintextcontent = $label;
        $cell->htmlcontent = html_writer::span($label, $class, ['title' => $title]);
        return $cell;
    }

    /**
     * @param $data
     * @param string $format
     * @return string
     * @throws coding_exception
     */
    public function format_user_measure_data($data, $format = FORMAT_PLAIN) : string {
        if (is_null($data->lastaccess)) {
            $label = get_string('never');
            $title = $label;
        } else {
            $label = userdate($data->timeaccess, '%A %e %B, %H:%M');
            $title = userdate($data->timeaccess);
        }
        $class = "measure";
        if ($format == FORMAT_HTML) {
            return html_writer::span($label, $class, ['title' => $title]);
        }
        return $label;
    }

    /**
     * @param stdClass $user
     * @return stdClass
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function get_data_for_user(stdClass $user) : stdClass {
        global $DB;

        /** @var array $lastaccess Used as a static cache. */
        static $lastaccess;

        if (is_null($lastaccess)) {
            $configuration = $this->get_configuration();
            if (is_null($configuration)) {
                throw new coding_exception('Configuration must loaded');
            }
            $sql = "SELECT la.userid, la.timeaccess
                      FROM {user_lastaccess} la
                     WHERE la.courseid = :courseid";
            $lastaccess = $DB->get_records_sql($sql, ['courseid' => $configuration->get('courseid')]);
        }
        $data = new stdClass();
        $data->lastaccess = null;
        if (isset($lastaccess[$user->id])) {
            $data->lastaccess = $lastaccess[$user->id];
        }
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
        $data = [];
        foreach ($userlist as $user) {
            $data[$user->id] = $this->get_data_for_user($user->id);
        }
        return $data;
    }

    /**
     * Default label for last course access.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_default_label(): string {
        return format_text(get_string('lastcourseaccess:measure:label', 'report_lp'), FORMAT_PLAIN);
    }

    /**
     * Name of measure.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string('lastcourseaccess:measure:name', 'report_lp');
    }

    /**
     * Description of what data/information this measure displays.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_description(): string {
        return get_string('lastcourseaccess:measure:description', 'report_lp');
    }

    /**
     * Is core so enabled.
     *
     * @return bool|null
     */
    public function is_enabled() {
        return true;
    }

}
