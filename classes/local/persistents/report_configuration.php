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

namespace report_lp\local\persistents;

defined('MOODLE_INTERNAL') || die();

use core\persistent;

/**
 * Main report configuration model.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_configuration extends persistent {

    /** The associated table name. */
    const TABLE = 'report_lp';

    public static function define_properties() {
        return [
            'courseid' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'enabled' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'timeanalysed' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'analysisinformation' => [
                'type' => PARAM_TEXT,
                'null' => NULL_ALLOWED,
                'default' => null
            ]
        ];
    }

}
