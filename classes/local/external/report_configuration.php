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
use report_lp\local\persistents\item_configuration;
use required_capability_exception;

/**
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_configuration extends external_api {

    public static function move_item_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'The course idenitifier'),
                'itemid' => new external_value(PARAM_INT, 'The item idenitifier'),
                'position' => new external_value(PARAM_INT, 'The new position'),
                'moveby' => new external_value(PARAM_INT, 'How many positions to move by (negative - up, positive - down)'),
                'total' => new external_value(PARAM_INT, 'The total positions available'),
            )
        );
    }

    public static function move_item(int $courseid, int $itemid, int $position, int $moveby, int $total) {
        global $CFG;
        require_once("$CFG->dirroot/report/lp/lib.php");

        $params = self::validate_parameters(self::move_item_parameters(),
            [
                'courseid' => $courseid,
                'itemid'   => $itemid,
                'position' => $position,
                'moveby'   => $moveby,
                'total'    => $total
            ]
        );
        $courseid = $params['courseid'];
        $itemid   = $params['itemid'];
        $position = $params['position'];
        $moveby   = $params['moveby'];
        $total    = $params['total'];

        $source = new item_configuration($itemid);
        $source = item_configuration::move_item_to_position($source, $position);
        $depth = $source->get('depth');

        return [
            'depth' => $depth
        ];
    }

    public static function move_item_returns() {
        return new external_single_structure(
            array(
                'depth' => new external_value(PARAM_INT, 'Depth after move.')
            )
        );
    }

}