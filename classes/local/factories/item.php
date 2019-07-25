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

use report_lp\local\learner_information_grouping;
use stdClass;
use coding_exception;
use moodle_exception;
use report_lp\local\grouping;
use report_lp\local\item_type_list;
use report_lp\local\learner;
use report_lp\local\learner_field;
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

    /** @var stdClass $course Associated course. */
    protected $course;

    /** @var item_type_list $itemtypelist */
    protected $itemtypelist;

    /**
     * item constructor.
     *
     * @param stdClass $course
     * @param item_type_list $itemtypelist
     */
    public function __construct(stdClass $course, item_type_list $itemtypelist) {
        $this->course = $course;
        $this->itemtypelist = $itemtypelist;
    }

    /**
     * Gets an item from an item persistent. Gets class from configuration then
     * sets properties from configuration.
     *
     * @param item_configuration $itemconfiguration
     * @return \report_lp\local\item
     * @throws coding_exception
     */
    public function get_item_from_persistent(item_configuration $itemconfiguration) {
        $shortname = $itemconfiguration->get('shortname');
        $item = $this->get_class_instance_from_list($shortname);
        $item->load_configuration($itemconfiguration);
        return $item;
    }

    /**
     * Find and return a clone.
     *
     * @param string $shortname
     * @return \report_lp\local\item
     * @throws coding_exception
     */
    public function get_class_instance_from_list(string $shortname) {
        $class = $this->itemtypelist->find_by_short_name($shortname);
        return clone($class);
    }

    /**
     * Get root grouping. Optional parameter to create configuration record.
     *
     * @param bool $create
     * @return grouping
     * @throws \ReflectionException
     * @throws coding_exception
     */
    public function get_root_grouping(bool $create = false) : grouping {
        $grouping = $this->get_class_instance_from_list('grouping');
        $rootconfiguration = item_configuration::get_record(
            ['courseid' => $this->course->id, 'parentitemid' => 0]
        );
        if (!$rootconfiguration) {
            $rootconfiguration = new item_configuration();
            $rootconfiguration->set('courseid', $this->course->id);
            $rootconfiguration->set('classname', $grouping::get_class_name());
            $rootconfiguration->set('shortname', $grouping::get_short_name());
            $rootconfiguration->set('usecustomlabel', 1);
            $rootconfiguration->set('customlabel', format_text($this->course->fullname, FORMAT_PLAIN));
            $rootconfiguration->set('parentitemid', 0);
            $rootconfiguration->set('depth', 0);
            $rootconfiguration->set('islocked', 1);
            if ($create) {
                $rootconfiguration->save();
            }
        }
        $grouping->load_configuration($rootconfiguration);
        return $grouping;
    }

    /**
     * Quick method of creating a grouping.
     *
     * @param string $customlabel
     * @param bool $islocked
     * @param int $parentitemid
     * @return grouping
     * @throws \ReflectionException
     * @throws \core\invalid_persistent_exception
     * @throws coding_exception
     */
    public function create_grouping(string $customlabel = '', bool $islocked = false, int $parentitemid = 0) : grouping {
        if ($parentitemid < 1) {
            throw new coding_exception("Invalid parent item id");
        }
        $grouping = $this->get_class_instance_from_list('grouping');
        $configuration = new item_configuration();
        $configuration->set('courseid', $this->course->id);
        $configuration->set('classname', $grouping::get_class_name());
        $configuration->set('shortname', $grouping::get_short_name());
        if (!empty($customlabel)) {
            $configuration->set('usecustomlabel', 1);
            $configuration->set('customlabel', format_text($customlabel, FORMAT_PLAIN));
        }
        $configuration->set('parentitemid', $parentitemid);
        $configuration->set('islocked', $islocked);
        $configuration->create();
        $grouping->load_configuration($configuration);
        return $grouping;
    }

    /**
     * Create the learner item.
     *
     * @param int $parentitemid
     * @return learner
     * @throws \ReflectionException
     * @throws \core\invalid_persistent_exception
     * @throws coding_exception
     */
    public function create_learner(int $parentitemid) : learner {
        $learner = $this->get_class_instance_from_list('learner');
        $configuration = new item_configuration();
        $configuration->set('courseid', $this->course->id);
        $configuration->set('classname', $learner::get_class_name());
        $configuration->set('shortname', $learner::get_short_name());
        $configuration->set('parentitemid', $parentitemid);
        $configuration->set('islocked', true);
        $configuration->create();
        $learner->load_configuration($configuration);
        return $learner;
    }

    /**
     * @param $shortname
     * @param int $parentitemid
     * @return learner_field
     * @throws \ReflectionException
     * @throws \core\invalid_persistent_exception
     * @throws coding_exception
     */
    public function create_learner_field($shortname, int $parentitemid) : learner_field {
        if ($parentitemid < 1) {
            throw new coding_exception("Invalid parent item id");
        }
        $learnerfield = $this->get_class_instance_from_list($shortname);
        $configuration = new item_configuration();
        $configuration->set('courseid', $this->course->id);
        $configuration->set('classname', $learnerfield::get_class_name());
        $configuration->set('shortname', $learnerfield::get_short_name());
        $configuration->set('parentitemid', $parentitemid);
        $configuration->create();
        $learnerfield->load_configuration($configuration);
        return $learnerfield;
    }

    /**
     * @param int $parentitemid
     * @return learner_information_grouping
     * @throws \ReflectionException
     * @throws \core\invalid_persistent_exception
     * @throws coding_exception
     */
    public function create_learner_information_grouping(int $parentitemid) : learner_information_grouping {
        if ($parentitemid < 1) {
            throw new coding_exception("Invalid parent item id");
        }
        $grouping = $this->get_class_instance_from_list('learner_information_grouping');
        $configuration = new item_configuration();
        $configuration->set('courseid', $this->course->id);
        $configuration->set('classname', $grouping::get_class_name());
        $configuration->set('shortname', $grouping::get_short_name());
        $configuration->set('parentitemid', $parentitemid);
        $configuration->set('islocked', true);
        $configuration->create();
        $grouping->load_configuration($configuration);
        return $grouping;
    }

    /**
     * Get a grouping based on configuration.
     *
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
        } else {
            if ($grouping::get_short_name() != $configuration->get('shortname')) {
                throw new coding_exception('Incorrect class for configuration');
            }
        }
        $grouping->load_configuration($configuration);
        return $grouping;
    }

    /**
     * Helper used to direct to correct method.
     *
     * @param item_configuration $configuration
     * @return grouping|measure
     * @throws \ReflectionException
     * @throws coding_exception
     */
    public function get_item(item_configuration $configuration) {
        if (grouping::get_short_name() == $configuration->get('shortname')) {
            return $this->get_grouping($configuration);
        }
        return $this->get_measure($configuration);
    }

    /**
     * Get a measure based on configuration. Will throw exception if no shortname set.
     *
     * @param item_configuration $configuration
     * @return measure
     * @throws \ReflectionException
     * @throws coding_exception
     */
    public function get_measure(item_configuration $configuration) : measure {
        $measure = $this->itemtypelist->find_measure_by_short_name($configuration->get('shortname'));
        if ($configuration->get('id') <= 0) {
            $configuration->set('courseid', $this->course->id);
            $configuration->set('classname', $measure::get_class_name());
            $configuration->set('shortname', $measure::get_short_name());
        }
        $measure->load_configuration($configuration);
        return $measure;
    }

    public function get_from_shortname(string $shortname) {
        if (!$this->itemtypelist->item_type_exists($shortname)) {
            throw new coding_exception("{$shortname} is not a registered item type.");
        }
        $configuration = new item_configuration();
        $configuration->set('shortname', $shortname);
        return $this->get_item($configuration);
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
        $item->load_configuration($configuration);
        return $item;
    }


    /**
     * Get all groupings for the course.
     *
     * @return array
     * @throws coding_exception
     */
    public function get_grouping_from_item_configurations() {
        $groupings = [];
        $itemconfigurations = item_configuration::get_records(
            ['courseid' => $this->course->id]
        );
        foreach ($itemconfigurations as $itemconfiguration) {
            $item = $this->get_item_from_persistent($itemconfiguration);
            // Only want groupings.
            if ($item instanceof grouping) {
                $groupings[] = $item;
            }
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
            $item->load_configuration($configuration);
            if ($keyed) {
                $items[$configuration->get('id')] = $item;
            } else {
                array_push($items, $item);
            }
        }
        return $items;
    }

}
