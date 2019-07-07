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

use report_lp\local\factories\item as item_factory;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use stdClass;

/**
 * Simple tree, current only supports two levels of depth.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_tree implements Countable, IteratorAggregate {

    /** @var stdClass $course Course object. */
    protected $course;

    /** @var item_factory $itemfactory */
    protected $itemfactory;

    /** @var item_type_list $itemtypelist */
    protected $itemtypelist;

    /** @var item $root */
    protected $root;

    /** @var array $tree Structure for holding items. */
    protected $tree;

    /**
     * item_tree constructor.
     *
     * @param stdClass $course
     * @param item_type_list $itemtypelist
     * @throws \ReflectionException
     * @throws \coding_exception
     */
    public function __construct(stdClass $course, item_type_list $itemtypelist) {
        $this->course = $course;
        $this->itemtypelist = $itemtypelist;
        $this->itemfactory = new item_factory($course, $itemtypelist);
        $this->root = $this->itemfactory->get_root_grouping();
    }

    /**
     * Builds tree based on depth and sort order. Items at depth 2 are added to their parenr
     * group.
     *
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function build() {
        $this->tree = [];
        /** @var item[] $items */
        $items = $this->itemfactory->get_ordered_items(true);
        foreach ($items as $item) {
            $configuration = $item->get_configuration();
            $id = $configuration->get('id');
            $parentitemid = $configuration->get('parentitemid');
            if (!$parentitemid) {
                $this->tree[$id] = $item; // Root.
            } else {
                $parentitem = $items[$parentitemid];
                /** @var grouping $parentitem */
                $parentitem->add_item($item);
            }
        }
        return $this->tree;
    }

    /**
     *
     * @return array
     * @throws \ReflectionException
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_flattened_tree() {
        if (is_null($this->tree)) {
            $this->build();
        }
        $items = [];
        // Flatten the tree of items.
        foreach ($this->tree as $item) {
            if ($item instanceof grouping) {
                $groupingitems = static::process_grouping($item);
                $items = array_merge($items, $groupingitems);
            }
        }
        return $items;
    }

    /**
     * @return array
     * @throws \ReflectionException
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_flattened_configurations() {
        $configurations = [];
        $flattree = $this->get_flattened_tree();
        foreach ($flattree as $branch) {
            $configurations[] = $branch->get_configuration();
        }
        return $configurations;
    }

    /**
     * @return array
     * @throws \ReflectionException
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_measures() {
        $measures = [];
        foreach ($this->get_flattened_tree() as $item) {
            if ($item instanceof measure) {
                $id = $item->get_configuration()->get('id');
                $measures[$id] = $item;
            }
        }
        return $measures;
    }

    /**
     * Recursively parse groupings in tree flatting into 1 dimensional array.
     *
     * @param grouping $grouping
     * @return array
     * @throws \ReflectionException
     * @throws \coding_exception
     */
    protected static function process_grouping(grouping $grouping) {
        $items[] = $grouping;
        if ($grouping->has_children()) {
            foreach ($grouping->get_children() as $child) {
                // Nested grouping.
                if ($child instanceof grouping) {
                    $childitems = static::process_grouping($child);
                    $items = array_merge($items, $childitems);
                } else {
                    $items[] = $child;
                }
            }
        }
        return $items;
    }

    /**
     * Count on tree at top level used by Countable interface.
     *
     * @return int
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function count() : int {
        if (is_null($this->tree)) {
            $this->build();
        }
        return count($this->tree);
    }

    /**
     * Public access to course variable.
     *
     * @return stdClass
     */
    public function get_course() {
        return $this->course;
    }

    /**
     * Return factory instance.
     *
     * @return mixed
     */
    public function get_item_factory() {
        return $this->get_item_factory();
    }

    /**
     * Return the item type list.
     *
     * @return item_type_list
     */
    public function get_item_type_list() {
        return $this->itemtypelist;
    }

    /**
     * Return tree array for iteration.
     *
     * @return ArrayIterator|\Traversable
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function getIterator() {
        if (is_null($this->tree)) {
            $this->build();
        }
        return new ArrayIterator($this->tree);
    }

}