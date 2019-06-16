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
use coding_exception;
use report_lp\local\measurelist;
use report_lp\local\grouping;
use report_lp\local\measure;
use report_lp\local\persistents\item_configuration;

class item {

    protected $course;

    protected $measurelist;

    public function __construct(stdClass $course, measurelist $measurelist) {
        $this->course = $course;
        $this->measurelist = $measurelist;
    }

    /**
     * Build a grouping, either new or load existing.
     *
     * @param int $id
     * @param stdClass|null $record
     * @return grouping
     * @throws \ReflectionException
     * @throws coding_exception
     */
    public function create_grouping(int $id = 0, stdClass $record = null) : grouping {
        $grouping = new grouping();
        $itemconfiguration = new item_configuration($id, $record);
        if ($id <= 0) {
            $itemconfiguration->set('courseid', $this->course->id);
            $itemconfiguration->set('classname', $grouping->get_class_name());
            $itemconfiguration->set('shortname', $grouping->get_short_name());
            $itemconfiguration->set('isgrouping', 1);
        }
        $grouping->set_configuration($itemconfiguration);
        return $grouping;
    }

    /**
     * Build a measure, either new or load existing.
     *
     * @param int $id
     * @param stdClass|null $record
     * @param string|null $shortname
     * @return measure
     * @throws \ReflectionException
     * @throws coding_exception
     */
    public function create_measure(int $id = 0, stdClass $record = null, string $shortname = null) : measure  {
        $itemconfiguration = new item_configuration($id, $record);
        if ($id <= 0) {
            if (is_null($shortname)) {
                throw new coding_exception("Creating a brand new measure require class shortname to be passed");
            }
            $measure = $this->measureslist->find_by_short_name($shortname);
            $itemconfiguration->set('courseid', $this->course->id);
            $itemconfiguration->set('classname', $measure->get_class_name());
            $itemconfiguration->set('shortname', $measure->get_short_name());
            $itemconfiguration->set('isgrouping', 1);
        } else {
            $measure = $this->measureslist->find_by_short_name($itemconfiguration->get('shortname'));
        }
        $measure->set_configuration($itemconfiguration);
        return $measure;
    }

}