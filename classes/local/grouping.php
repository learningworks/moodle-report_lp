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
use ArrayIterator;
use Countable;
use IteratorAggregate;

defined('MOODLE_INTERNAL') || die();

/**
 * Grouping.
 *
 * Tree structure for organising items.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grouping extends item implements Countable, IteratorAggregate {

    /** @var int MAXIMUM_ITEMS The maximum number of items/children a grouping can have. */
    public const MAXIMUM_ITEMS = 999;

    /**
     * @var array $children Store for child items.
     */
    private $children = [];

    /**
     * Default label for grouping.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_default_label(): string {
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
        $label = get_string('defaultlabelgrouping', 'report_lp', $number);
        return format_text($label, FORMAT_PLAIN);
    }

    /**
     * Item name.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string('grouping:name', 'report_lp');
    }

    /**
     * Description of what this does.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_description(): string {
        return get_string('grouping:description', 'report_lp');
    }

    /**
     * Add child item.
     *
     * @param item $item
     * @return $this
     * @throws coding_exception
     */
    public function add_item(item $item) {
        $configuration = $item->get_configuration();
        if (is_null($configuration)) {
            throw new coding_exception("Configuration required");
        }
        $id = $configuration->get('id');
        if ($id <= 0) {
            throw new coding_exception("ID is required");
        }
        $this->children[$id] = $item;
        return $this;
    }

    /**
     * Has children items.
     *
     * @return bool
     */
    public function has_children() : bool {
        return (bool) $this->count();
    }

    /**
     * Always enabled.
     *
     * @return bool|null
     */
    public function is_enabled() {
        return true;
    }

    /**
     * Count on items used by Countable interface.
     *
     * @return int
     */
    public function count() : int {
        return count($this->children);
    }

    /**
     * Alias of get child items.
     *
     * @return array
     */
    public function get_children() : array {
        return $this->children;
    }

    /**
     * Return items array for iteration.
     *
     * @return array|\Traversable
     */
    public function getIterator() {
        return new ArrayIterator($this->children);
    }

}
