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

    const MIN_DEPTH = 1;

    const MAX_DEPTH = 2;

    const MAX_CHILDREN = 999;

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
                'default' => 1
            ],
            'path' => [
                'type' => PARAM_TEXT,
                'default' => ''
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
     * Trigger a before update hook.
     */
    protected function after_create() {
        $this->update();
    }

    /**
     * Sets all internal stuff like path, depth, and sortorder.
     *
     * @throws coding_exception
     */
    protected function before_update() {
        $this->raw_set('path', $this->build_path());
        $this->raw_set('depth', $this->build_depth());
        $this->raw_set('sortorder', $this->build_sort_order());
    }

    /**
     * Only support a two levels maximum of depth.
     *
     * @return int
     * @throws coding_exception
     */
    protected function build_depth() {
        $parentitemid = $this->raw_get('parentitemid');
        if ($parentitemid > 0) {
            return 2;
        }
        return static::MIN_DEPTH;
    }

    /**
     * Build parent/child path.
     *
     * @return string
     * @throws coding_exception
     */
    protected function build_path() {
        $id = $this->raw_get('id');
        $parentitemid = $this->raw_get('parentitemid');
        if ($id <= 0) {
            throw new coding_exception('Valid record required');
        }
        if ($parentitemid > 0) {
            return '/' . $parentitemid . '/' . $id;
        }
        return '/' . $id;
    }

    /**
     * Build sort order for a child of parent (depth).
     *
     * @return int
     * @throws coding_exception
     */
    protected function build_sort_order() {
        $childrencount = self::count_records(
            [
                'courseid' => $this->raw_get('courseid'),
                'depth' => $this->raw_get('depth')
            ]
        );

        // Increment, this will be new sort order.
        ++$childrencount;
        if ($childrencount > static::MAX_CHILDREN) {
            throw new coding_exception('Maximum number of child items at a depth reached');
        }
        return $childrencount;
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
     * Depth is constructed internally.
     *
     * @return $this
     */
    protected function set_depth() {
        return $this;
    }

    /**
     * Path is constructed internally.
     *
     * @return $this
     */
    protected function set_path() {
        return $this;
    }

}
