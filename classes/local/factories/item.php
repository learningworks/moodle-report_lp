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
use moodle_exception;
use report_lp\local\grouping;
use report_lp\local\item_type_list;
use report_lp\local\measure;
use report_lp\local\persistents\item_configuration;

/**
 * Simple factory item class resonsible for creating/loading groupings and measures.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item {

    /**
     * @var stdClass $course Associated course.
     */
    protected $course;

    /** @var item_type_list $itemtypelist */
    protected $itemtypelist;

    public function __construct(stdClass $course, item_type_list $itemtypelist) {
        $this->course = $course;
        $this->itemtypelist = $itemtypelist;
    }

    /**
     * @return grouping
     * @throws \ReflectionException
     * @throws coding_exception
     */
    public function get_root_grouping() {
        $rootconfiguration = item_configuration::get_record(
            ['courseid' => $this->course->id, 'parentitemid' => 0]
        );
        if (!$rootconfiguration) {
            $rootconfiguration = new item_configuration();
            $rootconfiguration->set('usecustomlabel', 1);
            $rootconfiguration->set('customlabel', format_text($this->course->fullname, FORMAT_PLAIN));
        }
        return $this->get_grouping($rootconfiguration);
    }

    /**
     * @param item_configuration $configuration
     * @return grouping
     * @throws \ReflectionException
     * @throws coding_exception
     */
    public function get_grouping(item_configuration $configuration) : grouping {
        $grouping = new grouping();
        if ($configuration->get('id') <= 0) {
            $configuration->set('courseid', $this->course->id);
            $configuration->set('classname', $grouping::get_class_name());
            $configuration->set('shortname', $grouping::get_short_name());
            $configuration->set('isgrouping', 1);
        }
        $grouping->set_configuration($configuration);
        return $grouping;
    }

    /**
     * Build a grouping, either new or load existing. A Rapper method.
     *
     * @param int $id
     * @param stdClass|null $record
     * @return grouping
     * @throws \ReflectionException
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function create_grouping(int $id = 0, stdClass $record = null) : grouping {
        $configuration = new item_configuration($id, $record);
        if ($id == 0 || $record == null) {
            $configuration->set('shortname', grouping::get_short_name());
        }
        if ($configuration->get('shortname') != grouping::get_short_name()) {
            throw new moodle_exception('Trying to load incorrect class for configuration');
        }
        return $this->create_item($configuration, grouping::get_short_name());
    }

    /**
     * Build new or existing grouping or measure.
     *
     * @param item_configuration $configuration
     * @param string|null $shortname
     * @return grouping|measure
     * @throws \ReflectionException
     * @throws coding_exception
     */
    public function create_item(item_configuration $configuration, string $shortname = null)  {
        if ($configuration->get('id') <= 0) {
            if (is_null($shortname)) {
                throw new coding_exception("Valid shortname required when creating a brand new item");
            }
            $configuration->set('courseid', $this->course->id);
            // Grouping or measure supported.
            if ($shortname == grouping::get_short_name()) {
                $item = new grouping();
                $configuration->set('classname', $item::get_class_name());
                $configuration->set('shortname', $item::get_short_name());
                $configuration->set('isgrouping', 1);
            } else {
                $item = $this->itemtypelist->find_measure_by_short_name($shortname);
                $configuration->set('classname', $item::get_class_name());
                $configuration->set('shortname', $item::get_short_name());
            }
        } else {
            // Load existing grouping or measure.
            if ($configuration->get('shortname') == grouping::get_short_name()) {
                $item = new grouping();
            } else {
                $item = $this->itemtypelist->find_measure_by_short_name($configuration->get('shortname'));
            }
        }
        $item->set_configuration($configuration);
        return $item;
    }

    /**
     * Build a measure, either new or load existing. A Rapper method.
     *
     * @param int $id
     * @param stdClass|null $record
     * @param string|null $shortname
     * @return measure
     * @throws \ReflectionException
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function create_measure(int $id = 0, stdClass $record = null, string $shortname = null) : measure  {
        $configuration = new item_configuration($id, $record);
        if ($configuration->get('shortname') == grouping::get_short_name()) {
            throw new moodle_exception('Trying to load incorrect class for configuration');
        }
        return $this->create_item($configuration, $shortname);
    }

    /**
     * Get all groupings for the course.
     *
     * @return array
     * @throws \ReflectionException
     * @throws coding_exception
     */
    public function get_groupings() {
        $groupings = [];
        $itemconfigurations = item_configuration::get_records(
            ['courseid' => $this->course->id]
        );
        foreach ($itemconfigurations as $itemconfiguration) {
            // Only want groupings.
            if (grouping::get_short_name() != $itemconfiguration->get('shortname')) {
                continue;
            }
            $grouping = new grouping();
            $grouping->set_configuration($itemconfiguration);
            $groupings[] = $grouping;
        }
        return $groupings;
    }

    /**
     * Builds array of groupings and measures ordered by depth and sort order.
     *
     * @param bool $keyed
     * @return array
     * @throws \dml_exception
     */
    public function get_ordered_items(bool $keyed = true) : array {
        $items = [];
        $configurations = item_configuration::get_ordered_items($this->course->id);
        foreach ($configurations as $configuration) {
            $classname = $configuration->get('classname');
            $item = new $classname;
            $item->set_configuration($configuration);
            if ($keyed) {
                $items[$configuration->get('id')] = $item;
            } else {
                array_push($items, $item);
            }
        }
        return $items;
    }

}
