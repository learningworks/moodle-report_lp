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
use coding_exception;
use report_lp\local\grouping;

/**
 * Item model.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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
            'parentitemid' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'depth' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'path' => [
                'type' => PARAM_TEXT,
                'default' => '/'
            ],
            'sortorder' => [
                'type' => PARAM_INT,
                'default' => 0
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
     * Set properties that rely on id property.
     *
     * @throws \core\invalid_persistent_exception
     * @throws coding_exception
     */
    protected function after_create() {
        $this->update();
    }

    /**
     * Set sort order before create.
     *
     * @throws \dml_exception
     * @throws coding_exception
     */
    protected function before_create() {
        $this->raw_set('sortorder', $this->get_next_sort_order_value());
    }

    /**
     * Sets other properties that reply id property.
     *
     * @throws coding_exception
     */
    protected function before_update() {
        $this->set_path_and_depth();
    }

    /**
     * Build parent/child path.
     *
     * @return string
     * @throws coding_exception
     */
    private function set_path_and_depth() {
        $id = $this->raw_get('id');
        if ($id <= 0) {
            throw new coding_exception('Valid record required');
        }
        $pathitems = [];
        while (true) {
            $item = new static($id);
            array_unshift($pathitems, $item->get('id'));
            $id = $item->get('parentitemid');
            if ($id == 0) {
                break;
            }
        }
        $this->raw_set('depth', count($pathitems));
        $this->raw_set('path', implode('/', $pathitems));
    }

    /**
     * Get children items of current item.
     *
     * @return persistent[]
     * @throws coding_exception
     */
    public function get_children() {
        return static::get_records(
            ['parentitemid' => $this->raw_get('id')],
            'sortorder'
        );
    }

    /**
     * Get next sort order value for a child of parent at depth.
     *
     * @return mixed
     * @throws \dml_exception
     * @throws coding_exception
     */
    protected function get_next_sort_order_value() {
        global $DB;
        $sql = 'SELECT MAX(sortorder) 
                  FROM {' . static::TABLE . '} 
                 WHERE courseid = :courseid 
                   AND depth = :depth';
        $params = [
            'courseid' => $this->raw_get('courseid'),
            'depth' => $this->raw_get('depth')
        ];
        $sortorder = $DB->get_field_sql($sql, $params);
        // Increment.
        $sortorder++;
        return $sortorder;
    }

    /**
     * Extra configuration is stored as JSON. Decode JSON before returning.
     *
     * @return mixed
     * @throws coding_exception
     */
    protected function get_extraconfigurationdata() {
        $json = $this->raw_get('extraconfigurationdata');
        return json_decode($json);
    }

    /**
     * Extra configuration is to be stored as JSON.
     *
     * @param $value
     * @return item_configuration
     * @throws coding_exception
     */
    protected function set_extraconfigurationdata($value) {
        if (!(is_array($value) || is_object($value))) {
            throw new coding_exception('Datatype array or object required');
        }
        $json = json_encode($value);
        return $this->raw_set('extraconfigurationdata', $json);
    }

    /**
     * Need to perform multi-sort.
     *
     * @param int $courseid
     * @return array
     * @throws \dml_exception
     */
    public static function get_ordered_items(int $courseid) {
        global $DB;
        $instances = [];
        $records = $DB->get_records(
            static::TABLE,
            ['courseid' => $courseid],
            'depth, sortorder'
        );
        foreach ($records as $record) {
            $newrecord = new static(0, $record);
            array_push($instances, $newrecord);
        }
        return $instances;
    }

    /**
     * The parentitemid determines depth so set depth here. Depth
     * nly supports a maximum of two levels.
     *
     * @param $value
     * @return $this
     * @throws coding_exception
     */
    protected function set_parentitemid($value) {
        $this->raw_set('parentitemid', $value);
        if ($value > 0) {
            $this->raw_set('depth', 2);
        } else {
            $this->raw_set('depth', 1);
        }
        return $this;
    }

    /**
     * Depth is constructed internally.
     *
     * @return $this
     */
    protected function set_depth($value) {
        return $this;
    }

    /**
     * Path is constructed internally.
     *
     * @return $this
     */
    protected function set_path($value) {
        return $this;
    }

}
