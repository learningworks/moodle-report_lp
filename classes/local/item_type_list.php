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
 * List of types includes grouping type default. Valid measures are loaded in.
 *
 * @package
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_type_list implements Countable, IteratorAggregate {

    /**
     * @var array $measures Hold measures indexed on thier class names.
     */
    private $itemtypes = [];

    /**
     * @var array $measurenamekeys Measure referenced by human readable name.
     */
    private $measurenamekeys = [];

    /**
     * item_type_list constructor.
     *
     * @param array|null $measures
     * @throws \ReflectionException
     * @throws coding_exception
     */
    public function __construct(array $measures = null) {
        $this->itemtypes[grouping::get_short_name()] = new grouping();
        if (is_array($measures)) {
            $this->add_measures($measures);
        }
    }

    /**
     * Add array of measures.
     *
     * @param array $measures
     * @throws \ReflectionException
     * @throws coding_exception
     */
    public function add_measures(array $measures) {
        /** @var measure $measure */
        foreach ($measures as $measure) {
            if (!($measure instanceof measure)) {
                throw new coding_exception('This is not a measure!');
            }
            if ($measure->is_enabled()) {
                $this->add_measure($measure);
            }
        }
    }

    /**
     * Add measure to list check type
     *
     * @param measure $measure
     * @return $this
     * @throws \ReflectionException
     */
    public function add_measure(measure $measure) {
        $this->itemtypes[$measure::get_short_name()] = $measure;
        $this->measurenamekeys[$measure->get_name()] = $measure::get_short_name();
        return $this;
    }

    /**
     * Count of available item types
     *
     * @return int
     */
    public function count() : int {
        return count($this->itemtypes);
    }

    /**
     * Search for measure based in human readable name.
     *
     * @param string $name
     * @return measure
     * @throws coding_exception
     */
    public function find_measure_by_name(string $name) : measure {
        if (!isset($this->measurenamekeys[$name])) {
            throw new coding_exception("Measure with {$name} does not exist");
        }
        $shortname = $this->measurenamekeys[$name];
        return $this->find_by_short_name($shortname);

    }

    /**
     * Get item based on class short name.
     *
     * @param string $shortname
     * @return item
     * @throws coding_exception
     */
    public function find_by_short_name(string $shortname) : item {
        if (!$this->item_type_exists($shortname)) {
            throw new coding_exception("Item with {$shortname} does not exist");
        }
        return $this->itemtypes[$shortname];
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
     * Check if item type exists.
     *
     * @param string $shortname
     * @return bool
     */
    public function item_type_exists(string $shortname) : bool {
        if (!isset($this->itemtypes[$shortname])) {
            return false;
        }
        return true;
    }

    /**
     * Return just an array of measures.
     *
     * @return array
     */
    public function get_measures() {
        $measures = [];
        foreach ($this->itemtypes as $itemtype) {
            if ($itemtype instanceof measure) {
                array_push($measures, $itemtype);
            }
        }
        return $measures;
    }

    /**
     * Return grouping class.
     *
     * @return grouping
     * @throws \ReflectionException
     */
    public function get_grouping() : grouping {
        return $this->itemtypes[grouping::get_short_name()];
    }


    /**
     * Allow collection of measures to be iterated.
     *
     * @return ArrayIterator|Traversable
     */
    public function getIterator() {
        return new ArrayIterator($this->itemtypes);
    }
}