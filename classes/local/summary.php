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

use coding_exception;
use stdClass;

/**
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class summary {

    protected $course;

    protected $itemtypelist;

    protected $itemtree;

    protected $learnerlist;

    public function __construct(stdClass $course, item_type_list $itemtypelist = null) {
        $this->course = $course;
        if (!is_null($itemtypelist)) {
            $this->set_item_type_list($itemtypelist);
        }
    }

    public function get_course() : stdClass {
        return $this->course;
    }

    public function get_item_type_list() : item_type_list {
        return $this->itemtypelist;
    }

    public function get_item_tree() : item_tree {
        if (is_null($this->itemtypelist)) {
            throw new coding_exception('Parameter $itemtypelist of item_type_list must be set');
        }
        if (is_null($this->itemtree)) {
            $this->itemtree = new item_tree($this->course, $this->itemtypelist);
        }
        return $this->itemtree;
    }

    public function get_learner_list() : learner_list {
        return $this->learnerlist;
    }

    public function get_excluded_list() : user_list {
        return new excluded_learner_list($this->course, true);
    }

    public function add_item_type_list(item_type_list $itemtypelist) {
        $this->itemtypelist = $itemtypelist;
        return $this;
    }

    public function add_learner_list(learner_list $learnerlist) {
        $this->learnerlist = $learnerlist;
        return $this;
    }

}