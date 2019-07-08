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
use report_lp\local\measure;
use report_lp\local\user_list;

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

    public function format_user_measure_data($data, $format = FORMAT_PLAIN) : string {
        return '';
    }

    public function get_data_for_user(int $userid) {
        return null;
    }

    public function get_data_for_users(user_list $userlist) : array {
        return [];
    }

    /**
     * Nothing fancy here just a language string.
     *
     * @param string $format
     * @return string
     * @throws coding_exception
     */
    public function get_default_label($format = FORMAT_PLAIN): string {
        return format_text(get_string('lastcourseaccess:measure:label', 'report_lp'), $format);
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

}
