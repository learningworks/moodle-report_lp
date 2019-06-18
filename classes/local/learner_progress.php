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

use stdClass;
use report_lp\local\factories\item as item_factory;

/**
 *
 * @package
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class learner_progress {

    /**
     * @var stdClass
     */
    protected $course;

    protected $itemfactory;

    protected $measurelist;

    public function __construct(stdClass $course, measurelist $measurelist) {
        $this->course = $course;
        $this->measurelist = $measurelist;
        $this->itemfactory = new item_factory($course, $measurelist);
    }

    public function build_item_tree() {
        $tree = [];
        $reference = [];
        /** @var item[] $items */
        $items = $this->itemfactory->get_items();
        while ($items) {
            $item = array_shift($items);
            $configuration = $item->get_configuration();
            $parentitemid = $configuration->get('parentitemid');
            if ($parentitemid == 0) {
                $id = $configuration->get('id');
                $tree[] = $item;
                $reference[$id] = static::array_key_last($tree); // Available >= PHP 7.3.
            } else {
                $parentitem = $tree[$reference[$parentitemid]];
                $parentisgrouping = $parentitem->get_configuration()->get('isgrouping');
                if ($parentisgrouping) {
                    $parentitem->add_item($item);
                }
            }
        }
        return $tree;
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

}
