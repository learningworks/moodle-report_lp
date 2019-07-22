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
use report_lp\local\item_tree;
use report_lp\local\item_type_list;

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
                'default' => ''
            ],
            'sortorder' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'islocked' => [
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
     * Set internal properties that rely on id and parentitemid properties.
     *
     * @throws \core\invalid_persistent_exception
     * @throws \dml_exception
     * @throws coding_exception
     */
    protected function after_create() {
        $this->raw_set('path', $this->construct_path());
        $this->raw_set('depth', $this->construct_depth());
        $depth = $this->get_max_sort_order_at_depth();
        $this->raw_set('sortorder', ++$depth);
        $this->update();
    }

    /**
     * Construct depth based of path.
     *
     * @param string|null $path
     * @return int
     * @throws coding_exception
     */
    private function construct_depth(string $path = null) {
        if (empty($path)) {
            $path = $this->raw_get('path');
        }
        $depth = 0;
        if (!empty($path)) {
            $pathitems = explode('/', $path);
            if ($pathitems) {
                $depth = count($pathitems);
            }
        }
        return $depth;
    }

    /**
     * Builds a path of item configuration ids. Will add parentitemids until until
     * hit the root grouping.
     *
     * @return string
     * @throws coding_exception
     */
    private function construct_path() {
        $id = $this->raw_get('id');
        $pathitems = [];
        $parentitem = new static($this->get('parentitemid'));
        while ($parentitem->get('id') > 0) {
            array_unshift($pathitems, $parentitem->get('id'));
            $parentitem = new static($parentitem->raw_get('parentitemid'));
        }
        $pathitems[] = $id;
        return implode('/', $pathitems);
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
     * @param int|null $depth
     * @return mixed
     * @throws \dml_exception
     * @throws coding_exception
     */
    protected function get_max_sort_order_at_depth(int $depth = null) {
        global $DB;
        $courseid = $this->raw_get('courseid');
        if ($courseid <= 0) {
            throw new coding_exception('Invalid courseid');
        }
        $sql = 'SELECT MAX(sortorder) 
                  FROM {' . static::TABLE . '} 
                 WHERE courseid = :courseid 
                   AND depth = :depth';
        if (is_null($depth)) {
            $depth = $this->raw_get('depth');
        }
        $params = [
            'courseid' => $courseid,
            'depth' => $depth
        ];
        return (int) $DB->get_field_sql($sql, $params);
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
     * @todo move not in right place.
     *
     * @param item_configuration $source
     * @param int $position
     * @return item_configuration
     * @throws \ReflectionException
     * @throws \dml_exception
     * @throws coding_exception
     */
    public static function move_item_to_position(item_configuration $source, int $position) {
        if ($source->get('id') <= 0) {
            throw new coding_exception('Invalid item configuration');
        }
        if ($position <= 0) {
            throw new coding_exception('Invalid position');
        }
        $courseid = $source->get('courseid');
        $course = get_course($courseid);
        $itemtypelist = new item_type_list(report_lp_get_supported_measures());
        $tree = new item_tree($course, $itemtypelist);
        $items = $tree->get_flattened_configurations();
        if (!isset($items[$position])) {
            throw new coding_exception('No item at position');
        }
        $moveto = $items[$position];
        if ($source->get('parentitemid') == $moveto->get('parentitemid')) {
            $sourcesortorder = $source->get('sortorder');
            $movetosortorder = $moveto->get('sortorder');
            $newsortorder = $movetosortorder;
            foreach ($items as $key => $item) {
                /** var item_configuration $item */
                if ($key < $position) {
                    continue;
                }
                if ($item->get('parentitemid') == $moveto->get('parentitemid')) {
                    $item->set('sortorder', ++$newsortorder);
                    $item->save();
                }
            }
            $source->set('sortorder', $movetosortorder);
            $source->save();

        }
        return $source;
    }

    /**
     * Get the root configuration item.
     *
     * @param int $courseid
     * @return persistent|false
     * @throws coding_exception
     */
    public static function get_root_configuration(int $courseid) {
        if (empty($courseid)) {
            throw new coding_exception('Invalid courseid');
        }
        return static::get_record(['courseid' => $courseid, 'parentitemid' => 0]);
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

    /**
     * Path and depth are determined by parentitemid. See before_create method also.
     *
     * @param $value
     * @throws coding_exception
     */
    protected function set_parentitemid($value) {
        $this->raw_set('parentitemid', $value);
        $this->raw_set('path', $this->construct_path());
        $this->raw_set('depth', $this->construct_depth());
    }

}
