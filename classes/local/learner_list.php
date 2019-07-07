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

use report_lp\local\dml\utilities as dml_utilities;
use stdClass;
use coding_exception;

/**
 * A list of learners.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class learner_list extends user_list {

    /**
     * @var array $coursegroupids
     */
    private $coursegroupids = [];

    /**
     * learner_list constructor.
     *
     * @param stdClass $course
     */
    public function __construct(stdClass $course) {
        parent::__construct($course);
    }

    /**
     * Course Groups to filter list on.
     *
     * @param array $coursegroupids
     * @return $this
     */
    public function add_course_groups_filter(array $coursegroupids) {
        $coursegroupids = array_merge($this->coursegroupids, $coursegroupids);
        $this->coursegroupids = array_values(array_unique($coursegroupids));
        return $this;
    }

    /**
     * Return SQL string for ORDER BY.
     *
     * @todo add other sort by options.
     *
     * @return string
     */
    public function get_sort_by() {
        return 'u.firstname, u.lastname';
    }

    /**
     *
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function fetch_all() {
        global $DB;

        $defaultuserfields = static::get_default_user_fields();
        $sqluserfields = dml_utilities::alias($defaultuserfields, 'u');
        [$uesql, $ueparameters] = $this->get_user_enrolment_join('ue');
        $gmsql = '';
        $gmparameters = [];
        if (!empty($this->coursegroupids)) {
            [$gmsql, $gmparameters] = $this->get_course_group_membership_join($this->coursegroupids);
        }
        $sortorder = $this->get_sort_by();

        $sql = "SELECT {$sqluserfields},
                       ue.status
                  FROM {user} u
                  {$uesql}
                  {$gmsql}
              ORDER BY {$sortorder}";

        $parameters = array_merge($ueparameters, $gmparameters);
        $rs = $DB->get_recordset_sql($sql, $parameters);
        foreach ($rs as $record) {
            $this->add_user($record);
        }
        $rs->close();
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

        // Collision avoidance, may be going overboard here.
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

        // Collision avoidance, may be going overboard here.
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

    /**
     * Total of all available learners non-paged.
     *
     * @return int
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function total() {
        global $DB;
        [$uesql, $ueparameters] = $this->get_user_enrolment_join('ue');
        $gmsql = '';
        $gmparameters = [];
        if (!empty($this->coursegroupids)) {
            [$gmsql, $gmparameters] = $this->get_course_group_membership_join($this->coursegroupids);
        }
        $sql = "SELECT COUNT(1)
                  FROM {user} u
                  {$uesql}
                  {$gmsql}";
        $parameters = array_merge($ueparameters, $gmparameters);
        return $DB->count_records_sql($sql, $parameters);
    }


}
