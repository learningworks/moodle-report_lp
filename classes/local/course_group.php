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
namespace report_lp\local;

defined('MOODLE_INTERNAL') || die();


class course_group {

    public static function get_groups_for_user(int $courseid, int $userid) : array {
        global $CFG;
        require_once("$CFG->libdir/grouplib.php");

        $coursegroups = [];
        $groupmemberships = groups_get_user_groups($courseid, $userid)[0];
        $coursegroupdata = groups_get_course_data($courseid);
        foreach ($groupmemberships as $groupid) {
            if (isset($coursegroupdata->groups[$groupid])) {
                $group = $coursegroupdata->groups[$groupid];
                $coursegroups[$groupid] = $group;
            }
        }
        return $coursegroups;
    }
}

