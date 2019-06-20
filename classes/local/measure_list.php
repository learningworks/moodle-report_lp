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
 *
 * @package
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class measure_list implements Countable, IteratorAggregate {

    /**
     * @var array $measures Hold measures indexed on thier class names.
     */
    private $measures = [];

    /**
     * @var array $namekeys Measure referenced by human readable name.
     */
    protected $namekeys = [];

    /**
     * @var array $shortnamekeys Measure referenced by short name.
     */
    protected $shortnamekeys = [];

    /**
     * measures_list constructor.
     *
     * @param $measures
     * @throws coding_exception
     */
    public function __construct(array $measures) {
        foreach ($measures as $measure) {
            if (!is_subclass_of($measure, 'report_lp\local\measure')) {
                throw new coding_exception('Bad measure ' . get_class($measure));
            }
            $this->measures[$measure->get_class_name()] = $measure;
            $this->namekeys[$measure->get_name()] = $measure->get_class_name();
            $this->shortnamekeys[$measure->get_short_name()] = $measure->get_class_name();
        }
    }

    /**
     * Allow collection of measures to be iterated.
     *
     * @return ArrayIterator|Traversable
     */
    public function getIterator() {
        return new ArrayIterator($this->measures);
    }

    /**
     * @param $classname
     * @return measure
     * @throws coding_exception
     */
    private function get_measure_by_class($classname) : measure {
        if (!isset($this->measures[$classname])) {
            throw new coding_exception("$classname does not exist");
        }
        return $this->measures[$classname];
    }

    /**
     * @param $classname
     * @return false|int|string
     * @throws coding_exception
     */
    private function get_short_name_by_class($classname) : string {
        $key = array_search($classname, $this->shortnamekeys);
        if (!$key) {
            throw new coding_exception("Not found");
        }
        return $key;
    }


    /**
     * @return int
     */
    public function count() : int {
        return count($this->measures);
    }

    /**
     * @param string $name
     * @return measure
     * @throws coding_exception
     */
    public function find_by_name(string $name) : measure {
        if (!isset($this->namekeys[$name])) {
            throw new coding_exception("$name does not exist");
        }
        $classname = $this->namekeys[$name];
        return $this->get_measure_by_class($classname);
    }

    /**
     * @param string $shortname
     * @return measure
     * @throws coding_exception
     */
    public function find_by_short_name(string $shortname) : measure {
        if (!isset($this->shortnamekeys[$shortname])) {
            throw new coding_exception("$shortname does not exist");
        }
        $classname = $this->shortnamekeys[$shortname];
        return $this->get_measure_by_class($classname);
    }

}
