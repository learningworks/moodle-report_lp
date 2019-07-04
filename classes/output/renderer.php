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

namespace report_lp\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;
use report_lp\local\course_group;
use report_lp\local\factories\url;
use report_lp\local\item_tree;
use stdClass;

class renderer extends plugin_renderer_base {

    public function render_jumbotron($heading, $paragraphtop, $paragraphbottom = null, $button = null) {
        $data = new stdClass();
        $data->heading = $heading;
        $data->paragraphtop = $paragraphtop;
        $data->diaplayhr = 0;
        if (!is_null($paragraphbottom) || !is_null($button)) {
            $data->diaplayhr = 1;
        }
        $data->paragraphbottom = $paragraphbottom;
        $data->button = $button;
        return parent::render_from_template('report_lp/jumbotron', $data);
    }

    public function render_group_filter(stdClass $course) {
        $data = new stdClass;
        $data->courseid = $course->id;
        $data->redirecturl = url::get_summary_url($course);
        $data->activetext = get_string('filtergroups', 'report_lp');
        $activegroups = course_group::get_active_filter($course->id);
        if ($activegroups) {
            $activegroupnames = [];
            foreach ($activegroups as $activegroup) {
                if (is_number($activegroup)) {
                    $activegroupnames[] = course_group::get_group_from_id($course->id, $activegroup)->name;
                }
            }
            if ($activegroupnames) {
                $data->activetext = implode(", ", $activegroupnames);
            }
        }
        $data->groups = [];
        $coursegroup = new course_group($course);
        $availablegroups = $coursegroup->get_available_groups();
        foreach ($availablegroups as $availablegroup) {
            $group = new stdClass();
            $group->id = $availablegroup->id;
            $group->name = $availablegroup->name;
            $group->isactive = false;
            if (in_array($group->id, $activegroups)) {
                $group->isactive = true;
            }
            $data->groups[] = $group;
        }
        return parent::render_from_template("report_lp/group_filter", $data);
    }

    public function item_tree_configuration(item_tree $itemtree) {
        $data = $itemtree->export_for_template($this);
        return parent::render_from_template('report_lp/report_configuration', $data);
    }

    public function no_items_configured(no_items_configured $thing) {
        $data = $thing->export_for_template($this);
        return parent::render_from_template('report_lp/no_items_configured', $data);
    }
}
