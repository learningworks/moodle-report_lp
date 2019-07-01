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

/**
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_lp\local;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use context_course;
use coding_exception;

class learners {

    protected $course;

    protected $context;

    public $sql;

    public function __construct(stdClass $course, array $filters = []) {
        $this->course = $course;
        $this->context = context_course::instance($course->id);
    }

    public function add_filters(array $filters) {
    }

    public function set_filter($name, $value) {
    }

    /**
     * Make SQL that will get distinct learner enrolments within the course.
     *
     * @param string $userenrolmentprefix
     * @param string $useridcolumn
     * @return array
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function get_user_enrolment_join(string $userenrolmentprefix = 'ue', string $useridcolumn = 'u.id') {
        global $CFG, $DB;
        require_once("$CFG->libdir/enrollib.php");

        // Collision avoidance, may going overboard here.
        static $i = 0;
        $i++;
        $prefix = $userenrolmentprefix . $i . '_';

        // Use gradebookroles to determine valid learner/student roles.
        [$rolesql, $roleparameters] = $DB->get_in_or_equal(
            explode(',', $CFG->gradebookroles),
            SQL_PARAMS_NAMED,
            "{$prefix}ra"
        );

        $parameters = [];
        $parameters["{$prefix}ecourseid"] = $this->course->id;
        $parameters["{$prefix}racontextid"] = $this->context->id;
        $parameters = array_merge($parameters, $roleparameters);

        // SQL uses sub-select as we only want distinct users.
        $sql = "JOIN {user_enrolments} {$userenrolmentprefix} ON {$userenrolmentprefix}.userid = {$useridcolumn}
                JOIN {enrol} {$prefix}e
                  ON {$prefix}e.id = {$userenrolmentprefix}.enrolid AND {$prefix}e.courseid = :{$prefix}ecourseid
                JOIN (SELECT DISTINCT({$prefix}ra.userid)
                        FROM {role_assignments} {$prefix}ra 
                       WHERE {$prefix}ra.contextid = :{$prefix}racontextid 
                         AND {$prefix}ra.roleid $rolesql) AS {$prefix}dra
                  ON {$prefix}dra.userid = {$userenrolmentprefix}.userid";

        return [$sql, $parameters];
    }

    /**
     * Make SQL that will indicate if a user has a group membership in one or more passed in groups.
     *
     * @param array $groupids
     * @param string $useridcolumn
     * @return array
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function get_course_group_membership_join(array $groupids, string $useridcolumn = 'u.id') {
        global $DB;

        // Collision avoidance, may going overboard here.
        static $i = 0;
        $i++;
        $prefix = 'gm' . $i . '_';

        if (empty($groupids)) {
            throw new coding_exception("No group identifiers");
        }

        [$groupsql, $groupparameters] = $DB->get_in_or_equal(
            $groupids,
            SQL_PARAMS_NAMED,
            "{$prefix}g"
        );

        // SQL uses sub-select as we only want distinct users.
        $sql = "JOIN (SELECT DISTINCT({$prefix}gm.userid)
                        FROM {groups_members} {$prefix}gm
                       WHERE {$prefix}gm.groupid $groupsql) AS {$prefix}cgm
                  ON {$prefix}cgm.userid = {$useridcolumn}";

        return [$sql, $groupparameters];
    }

}
