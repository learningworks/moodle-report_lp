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

namespace report_lp\local\factories;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use report_lp\local\measures_list;
use report_lp\local\grouping;
use report_lp\local\persistents\item_configuration;

class item {

    protected $course;

    protected $measureslist;

    public function __construct(stdClass $course, measures_list $measureslist) {
        $this->course = $course;
        $this->measureslist = $measureslist;
    }

    public function create_grouping(int $id = 0, stdClass $record = null) {
        $grouping = new grouping();
        $itemconfiguration = new item_configuration($id, $record);
        if ($id <= 0) {
            $itemconfiguration->set('courseid', $this->course->id);
        }
        $itemconfiguration->set('classname', $grouping->get_class_name());
        $itemconfiguration->set('shortname', $grouping->get_short_name());
        $itemconfiguration->set('isgrouping', 1);
        $grouping->set_configuration($itemconfiguration);
        return $grouping;
    }

}