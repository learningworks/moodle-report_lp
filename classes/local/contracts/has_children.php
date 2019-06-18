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


namespace report_lp\local\contracts;

defined('MOODLE_INTERNAL') || die();

use Countable;
use report_lp\local\item;

/**
 * Interface for item that support child items. For example, 'Grouping' class.
 *
 * @package
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface has_children extends Countable {

    /**
     * Add an item class.
     *
     * @param item $item
     * @return mixed
     */
    public function add_item(item $item);

    /**
     * Has children.
     *
     * @return bool
     */
    public function has_children() : bool;

    /**
     * Array of child items.
     *
     * @return array
     */
    public function get_children() : array;

}
