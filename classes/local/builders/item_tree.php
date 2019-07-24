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
 * Simple tree.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_tree {

    private $course;

    private $tree;

    public function __construct(stdClass $course) {
        $this->course = $course;
    }

    public function build() {
        $items = [];
        $itemtypelist = new item_type_list();
        $itemfactory = new item_factory($this->course, $itemtypelist);
        $itemconfigurations = item_configuration::get_ordered_items($this->course->id);
        foreach ($itemconfigurations as $itemconfiguration) {
            $item = $itemfactory->get_item_from_persistent($itemconfiguration);
            $items[$item->get_id()] = $item;
        }
        foreach ($items as $item) {
            if ($item->is_root()) {
                $this->tree = $item;
            } else {
                $parentitem = $items[$item->get_parentitemid()];
                /** @var grouping $parentitem */
                $parentitem->add_item($item);
            }
        }
    }
}