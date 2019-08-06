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
use context_course;
use renderable;
use renderer_base;
use report_lp\local\factories\url;
use report_lp\local\visitors\data_item_visitor;
use stdClass;
use report_lp\local\builders\item_tree;
use templatable;
use report_lp\output\row;

/**
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class summary_report implements renderable, templatable {

    /** @var stdClass $course */
    protected $course;

    /** @var context_course $context */
    protected $context;

    protected $itemtypelist;

    protected $itemtree;

    /** @var learner_list $learnerlist */
    protected $learnerlist;

    /** @var renderer_base $renderer */
    protected $renderer;

    public function __construct(stdClass $course, item_type_list $itemtypelist = null) {
        $this->course = $course;
        $this->context = context_course::instance($course->id);
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

    protected function build_grouping_header($root) {
        $row = [];
        foreach($root->get_children() as $child)  {
            $cell = $child->build_header_cell(1);
            $cell->classes = "cell cell-primary-header";
            $row[] = $cell;
        }
        return $row;
    }

    protected function build_header(array $dataitems) {
        $row = [];
        foreach($dataitems as $dataitem)  {
            $cell = $dataitem->build_header_cell(2);
            $cell->classes = "cell cell-secondary-header";
            $row[] = $cell;
        }
        return $row;

    }

    protected function build_data_row($user, $items) {
        $row = [];
        foreach ($items as $item) {
            $data = $item->get_data_for_user($user);
            $cell = $item->build_data_cell($data);
            if (isset($cell->templatablecontent)) {
                $cell->content = $this->renderer->render_from_template(
                    'report_lp/' . $cell->template,
                    $cell->templatablecontent
                );
            } else if (isset($cell->htmlcontent)) {
                $cell->content = $cell->htmlcontent;
            } else if (isset($cell->plaintextcontent)) {
                $cell->content = $cell->plaintextcontent;
            } else {
                $cell->content = '';
            }
            $row[] = $cell;
        }
        return $row;
    }

    public function export_for_template(renderer_base $output) {
        $this->renderer = $output;

        $data = new stdClass();
        $data->courseid = $this->course->id;
        $data->reportconfigured = true;

        $filteredcoursegroups = course_group::get_active_filter($this->course->id);
        $this->get_learner_list()->add_course_groups_filter($filteredcoursegroups);

        $excludedlist = $this->get_excluded_list();
        $excludedlearnernames = array_map(
            function($learner) {
                return fullname($learner);
            },
            iterator_to_array($excludedlist));

        $data->hasexcludedlearners = ($excludedlist->count()) ? true : false;
        $data->excludedlearnerlist = implode(', ', $excludedlearnernames);

        $tree = new item_tree($this->course, $this->itemtypelist);
        $root = $tree->build_from_item_configurations();
        if (!$root) {
            $data->reportconfigured = false;
        } else {
            $data->canexport = has_capability('report/lp:exportsummary', $this->context);
            $data->exporturl = factories\url::get_export_url($this->course)->out(false);
            // This array of items very special to us.
            $dataitems = $root->accept(new data_item_visitor());
            $thead = new stdClass();
            $thead->rows = [];
            $row = new row();
            $row->cells = $this->build_grouping_header($root);
            $thead->rows[] = $row;
            $row = new row();
            $row->cells = $this->build_header($dataitems);
            $thead->rows[] = $row;
            $data->thead = $thead;

            $tbody = new stdClass();
            $tbody->rows = [];
            $this->get_learner_list()->load();
            $excludedlearnerids = $excludedlist->get_userids();
            foreach ($this->get_learner_list() as $learner) {
                if (in_array($learner->id, $excludedlearnerids)) {
                    continue;
                }
                $row = new row();
                $row->userid = $learner->id;
                $row->fullname = fullname($learner);
                $cells = $this->build_data_row($learner, $dataitems);
                $row->cells = $cells;
                $tbody->rows[] = $row;
            }
            if (!is_null($this->learnerlist->get_pagination())) {
                $data->pagination = $this->learnerlist->get_pagination()->export_for_template($output);
            }
            $data->tbody = $tbody;
        }
        return $data;
    }

}