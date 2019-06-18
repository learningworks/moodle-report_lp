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

use report_lp\local\contracts\has_children;

class grouping extends item implements has_children {

    /**
     * @var array $items Store for child items.
     */
    protected $items = [];

    /**
     * @var string $shortname Unique short name.
     */
    protected $shortname = 'grouping';

    public function get_default_label() : ? string {
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
        return get_string('defaultgroupinglabel', 'report_lp', $number);
    }

    public function get_name(): string {
        return get_string('grouping:measure:name', 'report_lp');
    }

    public function get_description(): string {
        return get_string('grouping:measure:description', 'report_lp');
    }

    public function add_item(item $item) {
        $this->items[] = $item;
    }

    public function has_items() {
        return (bool) $this->count();
    }

    public function has_children() : bool {
        return $this->has_items();
    }

    public function count() : int {
        return count($this->items);
    }

    public function get_items() {
        return $this->items;
    }

    public function get_children() : array {
        return $this->get_items();
    }
}
