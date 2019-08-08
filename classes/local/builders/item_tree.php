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

use coding_exception;
use report_lp\local\factories\item as item_factory;
use report_lp\local\grouping;
use report_lp\local\item_type_list;
use stdClass;

/**
 * Simple tree builder.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_tree {

    /** @var stdClass $course Course object. */
    private $course;

    /** @var item_type_list  */
    private $itemtypelist;

    /** @var item_factory $itemfactory Builds items based on configuration. */
    private $itemfactory;

    /**
     * item_tree constructor.
     *
     * @param stdClass $course
     * @param item_type_list $itemtypelist
     */
    public function __construct(stdClass $course, item_type_list $itemtypelist) {
        $this->course = $course;
        $this->itemtypelist = $itemtypelist;
        $this->itemfactory = new item_factory($this->course, $this->itemtypelist);
    }

    /**
     * Build tree from array of item persistents.
     *
     * @param array $itemconfigurations
     * @return grouping|null
     * @throws coding_exception
     */
    public function build_from_item_configurations(array $itemconfigurations) : ? grouping {
        $root = null;
        $items = [];
        foreach ($itemconfigurations as $itemconfiguration) {
            $item = $this->itemfactory->get_item_from_persistent($itemconfiguration);
            if ($this->course->id != $item->get_courseid()) {
                continue;
            }
            if ($item->is_enabled()) {
                $items[$item->get_id()] = $item;
            }
        }
        // Ensure array sorted by depth and then order and maintain keys.
        uasort($items, function($a, $b) {
            $sort = $a->get_depth() <=> $b->get_depth();
            if ($sort == 0) {
                $sort = $a->get_sortorder() <=> $b->get_sortorder();
            }
            return $sort;
        });
        // Maybe a better way, but this works for now.
        foreach ($items as $item) {
            if ($item->is_root()) {
                $root = $item;
            } else {
                $parentitem = $items[$item->get_parentitemid()];
                /** @var grouping $parentitem */
                $parentitem->add_item($item);
            }
        }
        return $root;
    }

}
