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
        $availablegroups = course_group::get_available_groups($course);
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

}
