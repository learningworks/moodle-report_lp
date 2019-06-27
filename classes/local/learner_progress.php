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

use coding_exception;
use report_lp\local\persistents\item_configuration;
use report_lp\local\persistents\report_configuration;
use stdClass;
use report_lp\local\factories\item as item_factory;

/**
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class learner_progress {

    /**
     * @var stdClass
     */
    protected $course;

    protected $itemfactory;

    protected $itemtypelist;

    protected $configuration;

    protected $rootgrouping;

    public function __construct(stdClass $course, item_type_list $itemtypelist = null) {
        $this->course = $course;
        $this->set_item_type_list($itemtypelist);
        $this->initialise();
    }

    public function get_item_factory() {
        if (is_null($this->itemtypelist)) {
            throw new coding_exception('Parameter $itemtypelist of item_type_list must be set');
        }
        if (is_null($this->itemfactory)) {
            $this->itemfactory = new item_factory($this->course, $this->itemtypelist);
        }
        return $this->itemfactory;
    }

    public function get_item_type_list() {
        return $this->itemtypelist;
    }

    public function set_item_type_list(item_type_list $itemtypelist) {
        $this->itemtypelist = $itemtypelist;
        return $this;
    }

    public static function configuration_exists(int $courseid) {
        return report_configuration::get_record(
            ['courseid' => $courseid]
        );
    }

    public static function root_grouping_exists(int $courseid) {
        return item_configuration::get_record(
            ['courseid' => $courseid, 'parentitemid' => 0]
        );
    }

    protected function initialise(bool $enabled = false) : void {
        $configuration = static::configuration_exists($this->course->id);
        if (!$configuration) {
            $configuration = new report_configuration();
            $configuration->set('courseid', $this->course->id);
            $configuration->set('enabled', (int) $enabled);
            $configuration->save();
        }
        $this->configuration = $configuration;
        $rootgrouping = $this->get_item_factory()->get_root_grouping();
        $this->rootgrouping = $rootgrouping;
    }

}
