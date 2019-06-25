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
        $this->itemtypelist = $itemtypelist;
    }

    public function get_item_factory() {
        if (is_null($this->itemtypelist)) {
            throw new coding_exception('Variable $itemtypelist item_type_list must be set');
        }
        return new item_factory($this->course, $this->itemtypelist);
    }

    public static function report_configuration_exists(int $courseid) {
        return report_configuration::get_record(['courseid' => $courseid]);
    }

    public function setup_report_configuration() : void {
        $configuration = report_configuration::get_record(['courseid' => $this->course->id]);
        if (!$configuration) {
            $configuration = new report_configuration();
            $configuration->set('courseid', $this->course->id);
            $configuration->save();
        }
        // Setup the root grouping item.
        $rootgrouping = $this->get_item_factory()->get_root_grouping();
        $rootgrouping->get_configuration()->save();
        $this->configuration = $configuration;
        $this->rootgrouping = $rootgrouping;
    }

}
