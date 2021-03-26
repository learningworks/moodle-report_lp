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

use Countable;
use ArrayIterator;
use IteratorAggregate;
use Traversable;
use coding_exception;

/**
 * List of types. Built in types are loaded via get_default_types().
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_type_list implements Countable, IteratorAggregate {

    /** @var array $registereditemtypes Items indexed on class shortname. */
    private $registereditemtypes = [];

    /**
     * item_type_list constructor.
     *
     * @param array $itemtypes
     * @throws \ReflectionException
     * @throws coding_exception
     */
    public function __construct(array $itemtypes = []) {
        $this->register_types(static::get_default_types());
        $this->register_types($itemtypes);
    }

    /**
     * Count of registered item types.
     *
     * @return int
     */
    public function count() : int {
        return count($this->registereditemtypes);
    }

    /**
     * Get item based on class short name.
     *
     * @param string $shortname
     * @return item|null
     */
    public function find_by_short_name(string $shortname) : ? item {
        if (!$this->item_type_exists($shortname)) {
            return null;
        }
        return $this->registereditemtypes[$shortname];
    }

    /**
     * Get measure by short name.
     *
     * @param string $shortname
     * @return measure
     * @throws coding_exception
     */
    public function find_measure_by_short_name(string $shortname) : measure {
        $measure = $this->find_by_short_name($shortname);
        if (!($measure instanceof measure)) {
            throw new coding_exception('Item is not a measure');
        }
        return $measure;
    }

    /**
     * Build in item types.
     *
     * @return array
     */
    public static function get_default_types() {
        return [
            new grouping(),
            new learner(),
            new fields\course_groups_learner_field(),
            new fields\idnumber_learner_field(),
            new fields\email_learner_field(),
            new fields\learner_profile_field(),
            //new measures\assignment_resubmit_count(),
            new measures\assignment_status(),
            new measures\attendance_sessions_summary(),
            //new measures\checklist_complete(),
            new measures\course_grade(),
            new measures\course_section_activity_completion(),
            //new measures\grade_category_activity_completion(),
            new measures\last_course_access()
        ];
    }

    /**
     * Return standard grouping class.
     *
     * @return grouping
     * @throws \ReflectionException
     * @throws coding_exception
     */
    public function get_grouping() : grouping {
        $groupingshortname = grouping::get_short_name();
        if (!$this->item_type_exists($groupingshortname)) {
            throw new coding_exception("Grouping class does not exist");
        }
        return $this->registereditemtypes[$groupingshortname];
    }

    /**
     * Allow collection of measures to be iterated.
     *
     * @return ArrayIterator|Traversable
     */
    public function getIterator() {
        return new ArrayIterator($this->registereditemtypes);
    }

    /**
     * Return standard learner class.
     *
     * @return learner
     * @throws \ReflectionException
     * @throws coding_exception
     */
    public function get_learner() : learner {
        $learnershortname = learner::get_short_name();
        if (!$this->item_type_exists($learnershortname)) {
            throw new coding_exception("Learner class does not exist");
        }
        return $this->registereditemtypes[$learnershortname];
    }

    /**
     * Return just an array of measures.
     *
     * @return array
     */
    public function get_measures() {
        $measures = [];
        foreach ($this->registereditemtypes as $itemtype) {
            if ($itemtype instanceof measure) {
                array_push($measures, $itemtype);
            }
        }
        return $measures;
    }

    /**
     * Check if item type exists.
     *
     * @param string $shortname
     * @return bool
     */
    public function item_type_exists(string $shortname) : bool {
        if (!isset($this->registereditemtypes[$shortname])) {
            return false;
        }
        return true;
    }

    /**
     * @param item $itemtype
     * @return $this
     * @throws \ReflectionException
     * @throws coding_exception
     */
    public function register_type(item $itemtype) {
        $shortname = $itemtype::get_short_name();
        if (!isset($this->registereditemtypes[$shortname])) {
            if ($itemtype::is_enabled()) {
                $this->registereditemtypes[$shortname] = $itemtype;
            }
        }
        return $this;
    }

    /**
     * @param array $itemtypes
     * @return $this
     * @throws \ReflectionException
     * @throws coding_exception
     */
    public function register_types(array $itemtypes) {
        foreach ($itemtypes as $itemtype) {
            $this->register_type($itemtype);
        }
        return $this;
    }

}
