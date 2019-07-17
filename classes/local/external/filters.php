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

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/report/lp/lib.php');

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
use report_lp\local\excluded_learner_list;
use required_capability_exception;
use stdClass;

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
        global $SESSION;

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

    /**
     * @return external_function_parameters
     */
    public static function exclude_learner_add_learner_parameters() {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'The course identifier relates to report instance'),
                'userid' => new external_value(PARAM_INT, 'The user identifier of learner to be exluded'),
            ]
        );
    }

    /**
     * @param int $courseid
     * @param int $userid
     * @return stdClass
     * @throws \dml_exception
     * @throws invalid_parameter_exception
     */
    public static function exclude_learner_add_learner(int $courseid, int $userid) {
        global $SESSION;

        $params = self::validate_parameters(self::exclude_learner_add_learner_parameters(),
            [
                'courseid' => $courseid,
                'userid'   => $userid
            ]
        );

        $courseid = $params['courseid'];
        $userid = $params['userid'];

        if (!isset($SESSION->report_lp_filters)) {
            $SESSION->report_lp_filters = [
                $courseid => [
                    'excludelearners' => []
                ]
            ];
        }
        $SESSION->report_lp_filters[$courseid]['excludelearners'][$userid] = $userid;

        $course = get_course($courseid);
        $excludedlearners = new excluded_learner_list($course, true);
        $response = [];
        foreach ($excludedlearners as $excludedlearner) {
            $data = new stdClass();
            $data->userid = $excludedlearner->id;
            $data->fullname = fullname($excludedlearner);
            $response[] = $data;
        }
        return $response;
    }

    /**
     * Return current list of excluded learners.
     *
     * @return external_multiple_structure
     */
    public static function exclude_learner_add_learner_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'fullname' => new external_value(PARAM_TEXT, 'User full name')
                ]
            )
        );
    }

    /**
     * Accepts course id.
     *
     * @return external_function_parameters
     */
    public static function exclude_learner_reset_parameters() {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'The course identifier relates to report instance')
            ]
        );
    }

    /**
     * Reset SESSION variable.
     *
     * @param int $courseid
     * @return array
     * @throws invalid_parameter_exception
     */
    public static function exclude_learner_reset(int $courseid) {
        global $SESSION;
        $params = self::validate_parameters(self::exclude_learner_reset_parameters(),
            [
                'courseid' => $courseid
            ]
        );
        $courseid = $params['courseid'];

        if (isset($SESSION->report_lp_filters[$courseid]['excludelearners'])) {
            $SESSION->report_lp_filters[$courseid]['excludelearners'] = [];
        }
        return [];
    }

    /**
     * Returns empty array.
     *
     * @return external_single_structure
     */
    public static function exclude_learner_reset_returns() {
        return new external_single_structure([]);
    }
}
