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

/**
 *  DESCRIPTION
 *
 * @package   {{PLUGIN_NAME}} {@link https://docs.moodle.org/dev/Frankenstyle}
 * @copyright 2015 LearningWorks Ltd {@link http://www.learningworks.co.nz}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * @param stdClass $course
 * @return bool
 * @throws coding_exception
 */
function report_lp_build_learner_progress_records(stdClass $course, progress_trace $trace) {
    global $CFG, $DB;

    require_once($CFG->libdir . '/grouplib.php');
    require_once($CFG->libdir . '/gradelib.php');
    require_once($CFG->dirroot . '/grade/querylib.php');
    require_once($CFG->dirroot . '/mod/assign/locallib.php');

    $assignid = $DB->get_field('report_lp_tracked', 'assignmentid', array('courseid'=>$course->id));
    if (!$assignid) {
        return false;
    }
    $assign = $DB->get_record('assign', array('id'=>$assignid));
    $cm = get_coursemodule_from_instance('assign', $assign->id, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    $assignment = new assign($context, null, null);
    $assignment->set_instance($assign);

    $gradeitem = $assignment->get_grade_item();

    $defaultdisplaytype = isset($CFG->grade_displaytype) ?  $CFG->grade_displaytype : 0;
    $displaytype = grade_get_setting($course->id, 'displaytype', $defaultdisplaytype);

    $reportstoremove = $DB->get_records('report_lp_learnerprogress', array('courseid' => $course->id), 'userid', 'userid, courseid');

    $studentrole = $DB->get_record('role', array('shortname'=>'student'));
    $users = get_role_users($studentrole->id, $context->get_parent_context());
    foreach ($users as $user) {
        unset($reportstoremove[$user->id]);
        $submission = $assignment->get_user_submission($user->id, true);
        if (!empty($submission->latest)) {
            $record = new stdClass();
            $record->id = null;
            $record->categoryid = $course->category;
            $record->courseid = $course->id;
            $groups = groups_get_all_groups($course->id, $user->id);
            if (count($groups) == 1) {
                $group = reset($groups);
                $coursegroupid = $group->id;
            } else if (count($groups) > 1) {
                // To many!
                $coursegroupid = -1;
            } else {
                // None.
                $coursegroupid = 0;
            }
            $record->coursegroupid = $coursegroupid;
            $record->assignmentid = $assignment->get_instance()->id;
            $record->userid = $user->id;
            $record->submissionid = $submission->id;
            $record->submissionstatus = $submission->status;

            $usergrade = $assignment->get_user_grade($user->id, true);
            $record->submissiongraderaw = $usergrade->grade;

            //$gradedisplay =  grade_format_gradevalue($usergrade->grade, $gradeitem, $displaytype);
            $coursegrade = grade_get_course_grade($user->id, $course->id);
            if (isset($coursegrade->grade)) {
                $record->coursegraderaw = $coursegrade->grade;
            }
            $record->modified = time();
            $lp = $DB->get_record('report_lp_learnerprogress', array('courseid'=>$course->id, 'userid'=>$user->id));
            if ($lp) {
                $record->id = $lp->id;
                $trace->output("Updating progress record for {$user->firstname} {$user->lastname} in course {$course->fullname}");
                try {
                    $DB->update_record('report_lp_learnerprogress', $record);
                } catch (Exception $e) {
                    echo PHP_EOL . 'Message: ' . $e->getMessage() . ' while updating record:' . PHP_EOL . 'lp->id = ' . $lp->id . PHP_EOL;
                }
            } else {
                $trace->output("Adding progress record for {$user->firstname} {$user->lastname} in course {$course->fullname}");
                try {
                    $DB->insert_record('report_lp_learnerprogress', $record);
                } catch (Exception $e) {
                    echo PHP_EOL . 'Message: ' . $e->getMessage() . ' while adding record:' . PHP_EOL . 'lp->id = ' . $lp->id . PHP_EOL;
                }
            }
        }
    }
    foreach($reportstoremove as $reporttoremove) {
        $trace->output("Removing report for userid: {$reporttoremove->userid} courseid: {$reporttoremove->courseid}");
        $params = array('userid' => $reporttoremove->userid, 'courseid' => $reporttoremove->courseid);
        $DB->delete_records('report_lp_learnerprogress', $params);
    }


    return true;
}
