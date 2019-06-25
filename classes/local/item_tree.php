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
use stdClass;
use ArrayIterator;
use Countable;
use IteratorAggregate;

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

    /** @var array $tree Structure for holding items. */
    protected $tree = [];

    /**
     * item_tree Constructor.
     *
     * @param stdClass $course
     * @param item_type_list $itemtypelist
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function __construct(stdClass $course, item_type_list $itemtypelist) {
        $this->course = $course;
        $this->itemtypelist = $itemtypelist;
        $this->itemfactory = new item_factory($course, $itemtypelist);
        $this->build();
    }

    /**
     * Helper method for PHP emulates PHP 7.3 method.
     *
     * @param $array
     * @return |null
     */
    public static function array_key_last($array) {
        if (!is_array($array) || empty($array)) {
            return null;
        }
        return array_keys($array)[count($array) - 1];
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
        /** @var item[] $items */
        $items = $this->itemfactory->get_ordered_items(true);
        while ($items) {
            $item = array_shift($items);
            $configuration = $item->get_configuration();
            $id = $configuration->get('id');
            $parentitemid = $configuration->get('parentitemid');
            $isgrouping = $configuration->get('isgrouping');
            if ($isgrouping) {
                if ($parentitemid) {
                    $parentitem = $this->tree[$parentitemid];
                    /** @var grouping $parentitem */
                    $parentitem->add_item($item);
                }
            } else {
                $this->tree[$id] = $item;
            }
        }
        return $this->tree;
    }

    /**
     * Count on tree at top level used by Countable interface.
     *
     * @return int
     */
    public function count() : int {
        return count($this->tree);
    }

    /**
     * Return tree array for iteration.
     *
     * @return array|\Traversable
     */
    public function getIterator() {
        return new ArrayIterator($this->tree);
    }

}