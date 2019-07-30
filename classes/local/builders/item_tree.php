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

namespace report_lp\local\builders;

defined('MOODLE_INTERNAL') || die();

use ArrayIterator;
use Countable;
use IteratorAggregate;
use report_lp\local\factories\item as item_factory;
use report_lp\local\grouping;
use report_lp\local\item_type_list;
use report_lp\local\persistents\item_configuration;
use stdClass;

/**
 * Simple tree builder.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_tree {

    private $depth = 0;

    /**
     * @var stdClass $course Course object.
     */
    private $course;

    /** @var array $items One dimensional array of all items. */
    private $items = [];

    /** @var item_factory $itemfactory Builds items based on configuration. */
    private $itemfactory;

    /** @var grouping $tree Tree struction, parents and children. */
    private $tree;

    /**
     * item_tree constructor.
     *
     * @param stdClass $course
     * @param item_type_list|null $itemtypelist
     * @throws \ReflectionException
     * @throws \coding_exception
     */
    public function __construct(stdClass $course, item_type_list $itemtypelist = null) {
        $this->course = $course;
        if (is_null($itemtypelist)) {
            $itemtypelist = new item_type_list();
        }
        $this->itemfactory = new item_factory($this->course, $itemtypelist);
    }

    /**
     * Build the tree based on a ordered collection of item configurations.
     *
     * @return grouping|null
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function build_from_item_configurations() : ? grouping {
        $itemconfigurations = item_configuration::get_ordered_items($this->course->id);
        if ($itemconfigurations) {
            foreach ($itemconfigurations as $itemconfiguration) {
                $item = $this->itemfactory->get_item_from_persistent($itemconfiguration);
                $this->items[$item->get_id()] = $item;
            }
            foreach ($this->items as $item) {
                if ($item->is_root()) {
                    $this->tree = $item;
                } else {
                    $parentitem = $this->items[$item->get_parentitemid()];
                    /** @var grouping $parentitem */
                    $parentitem->add_item($item);
                    if ($item->get_depth() > $this->depth) {
                        $this->depth = $item->get_depth();
                    }
                }
            }
        }
        return $this->tree;
    }

    /**
     * Get the current depth of the tree.
     *
     * @return int
     */
    public function get_current_depth() {
        return (int) $this->depth;
    }

}
