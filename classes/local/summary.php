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
use report_lp\local\contracts\data_provider;
use report_lp\local\visitors\depth_item_visitor;
use report_lp\local\visitors\data_item_visitor;
use report_lp\output\row1;
use stdClass;
use report_lp\local\builders\item_tree;
use report_lp\output\row;

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
        if (is_null($itemtypelist)) {
            $this->itemtypelist = $this->get_default_item_type_list();
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

    public function get_default_item_type_list() {
        return new item_type_list();
    }

    public function add_learner_list(learner_list $learnerlist) {
        $this->learnerlist = $learnerlist;
        return $this;
    }

    public function build_data() {
        if (is_null($this->itemtypelist)) {
            $this->add_item_type_list($this->get_default_item_type_list());
        }

        if (is_null($this->learnerlist)) {
            $this->add_learner_list(new learner_list($this->course));
        }

        $filteredcoursegroups = course_group::get_active_filter($this->course->id);
        $this->learnerlist->add_course_groups_filter($filteredcoursegroups);

        $excludedlist = $this->get_excluded_list();
        $excludedlearnernames = array_map(
            function($learner) {
                return fullname($learner);
            },
            iterator_to_array($excludedlist));

        $data = new stdClass();
        $data->courseid = $this->course->id;
        $data->hasexcludedlearners = ($excludedlist->count()) ? true : false;
        $data->excludedlearnerlist = implode(', ', $excludedlearnernames);

        $tree = new item_tree($this->course, $this->itemtypelist);
        $root = $tree->build_from_item_configurations();

        // This array of items very special to us.
        $dataitems = $root->accept(new data_item_visitor());
        $thead = [];
        $row = new row();
        $row->cells = $this->build_grouping_header($root);
        $thead[] = $row;
        $row = new row();
        $row->cells = $this->build_header($dataitems);
        $thead[] = $row;
        $data->thead = $thead;


        return $data;
    }

    protected function build_grouping_header($root) {
        $row = [];
        foreach($root->get_children() as $child)  {
            $cell = $child->build_header_cell(1);
            $row[] = $cell;
        }
        return $row;
    }

    protected function build_header(array $dataitems) {
        $row = [];
        foreach($dataitems as $dataitem)  {
            $cell = $dataitem->build_header_cell(2);
            $row[] = $cell;
        }
        return $row;

    }

}