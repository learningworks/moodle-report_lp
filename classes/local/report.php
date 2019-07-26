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

use report_lp\local\builders\item_tree;
use report_lp\local\visitors\component_item_visitor;
use stdClass;
use report_lp\local\persistents\report_configuration;

/**
 * General API methods.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report {

    /**
     * Create instance of report for course. This includes setting up learner grouping, learner and
     * learner fields.
     *
     * @param stdClass $course
     * @return report_configuration
     * @throws \ReflectionException
     * @throws \coding_exception
     * @throws \core\invalid_persistent_exception
     */
    public static function create_course_instance(stdClass $course) {
        $itemfactory = new factories\item($course, new item_type_list());
        $root = $itemfactory->get_root_grouping(true);
        $learnergrouping = $itemfactory->create_learner_information_grouping($root->get_id());
        $learneritem = $itemfactory->create_learner($learnergrouping->get_id());
        $idnumberfield = $itemfactory->create_learner_field('idnumber_learner_field', $learnergrouping->get_id());
        $idnumberfield->get_configuration()->set('islocked', true)->save();
        $coursegroupsfield = $itemfactory->create_learner_field('course_groups_learner_field', $learnergrouping->get_id());
        $coursegroupsfield->get_configuration()->set('islocked', true)->save();
        $reportconfiguration = new report_configuration();
        $reportconfiguration->set('courseid', $course->id);
        $reportconfiguration->set('enabled', true);
        $reportconfiguration->create();
        return $reportconfiguration;
    }

    /**
     * Check if instance of report for course exists.
     *
     * @param stdClass $course
     * @return bool
     */
    public static function course_instance_exists(stdClass $course) {
        return report_configuration::record_exists_select(
            "courseid = :courseid",
            ['courseid' => $course->id]
        );
    }

    /**
     * Delete instance of report for a course.
     *
     * @param stdClass $course
     * @throws \dml_exception
     */
    public static function delete_course_instance(stdClass $course) {
        global $DB;
        $DB->delete_records('report_lp', ['courseid' => $course->id]);
        $DB->delete_records('report_lp_items', ['courseid' => $course->id]);
    }

    /**
     * Clean up items attached to a deleted course module.
     *
     * @param int $courseid
     * @param string $modulename
     * @param int $instanceid
     * @throws \ReflectionException
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function handle_course_module_deletion(int $courseid, string $modulename, int $instanceid) {
        $course = get_course($courseid);
        $treebuilder = new item_tree($course);
        $root = $treebuilder->build_from_item_configurations();
        $visitor = new component_item_visitor('mod', $modulename);
        /** @var item $item */
        $items = $root->accept($visitor);
        foreach ($items as $item) {
            $extraconfiguration = $item->get_extraconfigurationdata();
            // @TODO at this stage we are assuming id isset and is id in plugins table.
            if ($extraconfiguration->id == $instanceid) {
                $item->get_configuration()->delete();
            }
        }
    }

}
