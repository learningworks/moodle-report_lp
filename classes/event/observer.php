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

namespace report_lp\event;

defined('MOODLE_INTERNAL') || die();

use core\event\course_deleted;
use core\event\course_module_deleted;
use report_lp\local\report;

/**
 * Event handlers for the plugin.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    public static function course_deleted(course_deleted $event) {
        report::delete_course_instance($event->courseid);
    }

    public static function course_module_deleted(course_module_deleted $event) {
        report::handle_course_module_deletion($event->courseid, $event->other['modulename'], $event->other['instanceid']);
    }

}
