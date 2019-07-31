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

defined('MOODLE_INTERNAL') || die();


use report_lp\local\course_group;
use report_lp\local\learner_field;
use report_lp\local\user_list;
use report_lp\output\cell;
use stdClass;

/**
 * Gets course groups learner has memberships in.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class course_groups_learner_field extends learner_field {

    public function build_data_cell($user) {
        $groups = [];
        foreach ($user->coursegroups as $coursegroup) {
            $groups[] = $coursegroup->name;
        }
        $cell = new cell();
        $cell->class = "cell";
        $cell->contents = implode(', ', $groups);
        return $cell;
    }

    public function get_description(): string {
        return get_string('coursegroups:learnerfield:description', 'report_lp');
    }

    public function get_default_label() : string {
        return get_string('coursegroups:learnerfield:label', 'report_lp');
    }

    public function get_name(): string {
        return get_string('coursegroups:learnerfield:name', 'report_lp');
    }

    public function get_data_for_user(stdClass $user) : stdClass {
        if (!property_exists($user, 'coursegroups')) {
            $user->coursegroups = course_group::get_groups_for_user($this->get_courseid(), $user->id);
        }
        return $user;
    }

    public function get_data_for_users(user_list $userlist) : array {
        return [];
    }

}
