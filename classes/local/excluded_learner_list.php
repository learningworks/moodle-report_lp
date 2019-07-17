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


use report_lp\local\dml\utilities as dml_utilities;
use stdClass;
use coding_exception;

/**
 * A list of excluded learners.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class excluded_learner_list extends user_list {

    /**
     * excluded_learner_list constructor.
     *
     * @param stdClass $course
     * @param bool $loadfromsession
     */
    public function __construct(stdClass $course, bool $loadfromsession = false) {
        parent::__construct($course);
        if ($loadfromsession) {
            $this->load_from_session();
        }
    }

    protected function load_from_session() {
        global $DB, $SESSION;

        if (!empty($SESSION->report_lp_filters[$this->course->id]['excludelearners'])) {
            $excludedlearners = $SESSION->report_lp_filters[$this->course->id]['excludelearners'];
            $defaultuserfields = static::get_default_user_fields();
            $sqluserfields = dml_utilities::alias($defaultuserfields, 'u');
            [$excludesql, $excludeparameters] = $DB->get_in_or_equal(
                $excludedlearners,
                SQL_PARAMS_NAMED,
                "e"
            );

            $sql = "SELECT {$sqluserfields}
                      FROM {user} u
                     WHERE u.id {$excludesql}
                  ORDER BY u.firstname, u.lastname";

            $rs = $DB->get_recordset_sql($sql, $excludeparameters);
            foreach ($rs as $record) {
                $this->add_user($record);
            }
            $rs->close();
        }
    }

}
