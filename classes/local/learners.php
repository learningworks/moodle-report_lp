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

    public function get_ue_join(string $userenrolmentsprefix = 'ue', string $useridcolumn = 'u.id') {
        global $CFG, $DB;
        require_once("$CFG->libdir/enrollib.php");

        // Try to avoid collisions with other potential joins.
        static $i = 0;
        $i++;
        $prefix = $userenrolmentsprefix . $i . '_';

        $parameters = [];
        $parameters["{$prefix}ecourseid"] = $this->course->id;
        $parameters["{$prefix}racontextid"] = $this->context->id;

        [$rolesql, $roleparameters] = $DB->get_in_or_equal(
            explode(',', $CFG->gradebookroles),
            SQL_PARAMS_NAMED,
            "{$prefix}ra"
        );

        $parameters = array_merge($parameters, $roleparameters);

        $sql = "JOIN {user_enrolments} {$userenrolmentsprefix} ON {$userenrolmentsprefix}.userid = {$useridcolumn}
                JOIN {enrol} {$prefix}e
                  ON {$prefix}e.id = {$userenrolmentsprefix}.enrolid AND {$prefix}e.courseid = :{$prefix}ecourseid
                JOIN ( SELECT DISTINCT({$prefix}ra.userid)
                         FROM {role_assignments} {$prefix}ra 
                        WHERE {$prefix}ra.contextid = :{$prefix}racontextid 
                          AND {$prefix}ra.roleid $rolesql) AS {$prefix}dra
                  ON {$prefix}dra.userid = {$userenrolmentsprefix}.userid";

        return [$sql, $parameters];
    }

}
