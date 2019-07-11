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

use coding_exception;
use pix_icon;
use ArrayIterator;
use Countable;
use IteratorAggregate;

defined('MOODLE_INTERNAL') || die();

/**
 * Grouping used for display purposes. Allows measures to be grouped together.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grouping extends item implements Countable, IteratorAggregate {

    /** @var int MAXIMUM_ITEMS The maximum number of items/children a grouping can have. */
    public const MAXIMUM_ITEMS = 999;

    /**
     * @var array $childitems Store for child items.
     */
    protected $childitems = [];

    /**
     * Build grouping label.
     *
     * @param string $format
     * @return string
     * @throws coding_exception
     */
    public function get_label($format = FORMAT_PLAIN) {
        $configuration = $this->get_configuration();
        if (is_null($configuration)) {
            $id = 0;
        } else {
            $id = $configuration->get('id');
        }
        if ($id > 0) {
            $number = $id;
        } else {
            $number = get_string('dotn', 'report_lp');
        }
        if ($configuration->get('usecustomlabel')) {
            $name = $configuration->get('customlabel');
        } else {
            $name = get_string('defaultlabelgrouping', 'report_lp', $number);
        }
        return format_text($name, $format);
    }

    /**
     * Item name.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string('grouping:measure:name', 'report_lp');
    }

    /**
     * Description of what this does.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_description(): string {
        return get_string('grouping:measure:description', 'report_lp');
    }

    /**
     * Add child item.
     *
     * @param item $item
     * @return $this
     * @throws coding_exception
     */
    public function add_item(item $item) {
        $key = $item->get_configuration()->get('id');
        $this->childitems[$key] = $item;
        return $this;
    }

    /**
     * Has child items in array.
     *
     * @return bool
     */
    public function has_child_items() {
        return (bool) $this->count();
    }

    /**
     * Alias of has items.
     *
     * @return bool
     */
    public function has_children() : bool {
        return (bool) $this->count();
    }

    /**
     * Count on items used by Countable interface.
     *
     * @return int
     */
    public function count() : int {
        return count($this->childitems);
    }

    /**
     * Return the array of child items.
     *
     * @return array
     */
    public function get_child_items() {
        return $this->childitems;
    }

    /**
     * Alias of get child items.
     *
     * @return array
     */
    public function get_children() : array {
        return $this->get_child_items();
    }

    /**
     * Return items array for iteration.
     *
     * @return array|\Traversable
     */
    public function getIterator() {
        return new ArrayIterator($this->childitems);
    }

}
