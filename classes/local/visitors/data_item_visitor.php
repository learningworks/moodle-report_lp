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

namespace report_lp\local\visitors;

defined('MOODLE_INTERNAL') || die();

use report_lp\local\grouping;
use report_lp\local\item;
use report_lp\local\contracts\item_visitor;

class data_item_visitor implements item_visitor {

    public function visit(item $item) {
        $items = [];
        if (!($item instanceof grouping)) {
            $items[] = $item;
        }
        if ($item instanceof grouping) {
            foreach ($item->get_children() as $child) {
                $items = array_merge(
                    $items,
                    $child->accept($this)
                );
            }
        }
        return $items;
    }

}
