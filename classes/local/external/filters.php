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

namespace report_lp\local\external;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use context;
use context_system;
use context_course;
use context_module;
use context_helper;
use context_user;
use coding_exception;
use external_api;
use external_function_parameters;
use external_value;
use external_format_value;
use external_single_structure;
use external_multiple_structure;
use invalid_parameter_exception;
use required_capability_exception;

/**
 * Webservices for filter components.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filters extends external_api {

    /**
     * Paramters that are accepted.
     *
     * @return external_function_parameters
     */
    public static function set_group_filter_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'The course idenitifier'),
                'groupids' => new external_value(PARAM_TAGLIST, 'The comma separated list of group idenitifiers'),
            )
        );
    }

    /**
     * Set groups in user SESSION for a course.
     *
     * @param int $courseid
     * @param string $groupids
     * @return array
     * @throws invalid_parameter_exception
     */
    public static function set_group_filter(int $courseid, string $groupids) {
        global $CFG, $SESSION;
        require_once("$CFG->dirroot/report/lp/lib.php");

        $params = self::validate_parameters(self::set_group_filter_parameters(),
            [
                'courseid' => $courseid,
                'groupids'   => $groupids

            ]
        );
        $courseid = $params['courseid'];
        $groupids = trim($params['groupids']);
        if (!isset($SESSION->report_lp_filters)) {
            $SESSION->report_lp_filters = [];
        }
        if (!empty($groupids)) {
            $groupids = explode(',', $groupids);
            $SESSION->report_lp_filters[$courseid] = ['group' => $groupids];
        } else {
            $SESSION->report_lp_filters[$courseid] = ['group' => []];
        }
        return [];
    }

    /**
     * Return nothing.
     *
     * @return external_single_structure
     */
    public static function set_group_filter_returns() {
        return new external_single_structure([]);
    }
}
