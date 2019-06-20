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

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use stdClass;
use templatable;
use report_lp\local\measure_list;
use report_lp\local\grouping;
use report_lp\local\factories\url;

/**
 *
 * @package
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class add_item_menu implements renderable, templatable {

    protected $course;

    protected $grouping;

    protected $measureslist;

    public function __construct(stdClass $course, measure_list $measureslist) {
        $this->course = $course;
        $this->grouping = new grouping();
        $this->measureslist = $measureslist;
    }

    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $data->groupingname = $this->grouping->get_name();
        $data->groupingurl = url::get_grouping_url($this->course)->out(false);
        $data->measuresdropdownname = get_string('measures', 'report_lp');
        $data->measuresdropdownitems = [];
        foreach ($this->measureslist as $measure) {
            $item = new stdClass();
            $item->measurename = $measure->get_name();
            $item->measureurl = url::get_measure_url($this->course, 0, $measure->get_short_name())->out(false);
            $data->measuresdropdownitems[] = $item;
        }
        return $data;
    }
}
