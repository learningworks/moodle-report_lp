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

/**
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core\persistent;
use coding_exception;

class item_configuration extends persistent {

    /** The associated table name. */
    const TABLE = 'report_lp_items';

    public static function define_properties() {
        return [
            'courseid' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'classname' => [
                'type' => PARAM_TEXT
            ],
            'shortname' => [
                'type' => PARAM_TEXT
            ],
            'usecustomlabel' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'customlabel' => [
                'type' => PARAM_TEXT,
                'null' => NULL_ALLOWED,
                'default' => null
            ],
            'displayorder' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'parentitemid' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'depth' => [
                'type' => PARAM_INT,
                'default' => 1
            ],
            'path' => [
                'type' => PARAM_TEXT,
                'default' => ''
            ],
            'isgrouping' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'visibletosummary' => [
                'type' => PARAM_INT,
                'default' => 1
            ],
            'visibletoinstance' => [
                'type' => PARAM_INT,
                'default' => 1
            ],
            'visibletolearner' => [
                'type' => PARAM_INT,
                'default' => 1
            ],
            'extraconfigurationdata' => [
                'type' => PARAM_TEXT,
                'null' => NULL_ALLOWED,
                'default' => null
            ]
        ];
    }

    /**
     * Hook to execute after a create.
     */
    protected function after_create() {
        // Handle path and depth. Currently only supporting depth of 2 maximum.
        $id = $this->raw_get('id');
        $parentitemid = $this->raw_get('parentitemid');
        if ($parentitemid <= 0) {
            $path = '/' . $id;
            $depth = 1;
        } else {
            $path = '/' . $parentitemid . '/' . $id;
            $depth = 2;
        }
        $this->raw_set('path', $path);
        $this->raw_set('depth', $depth);
        // Handle display order.
        $displayorder = $id * 10000;
        $this->raw_set('displayorder', $displayorder);
        $this->update();
    }

    protected function get_extraconfigurationdata() {
        $json = $this->raw_get('extraconfigurationdata');
        return json_decode($json);
    }

    protected function set_extraconfigurationdata($value) {
        if (!(is_array($value) || is_object($value))) {
            throw new coding_exception('Datatype array or object required');
        }
        $json = json_encode($value);
        return $this->raw_set('extraconfigurationdata', $json);
    }

}
