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

function report_lp_detect_assignments_to_track(stdClass $course = null) {
    global $DB, $SITE;
    $params = array();
    $sql = "SELECT c.*
              FROM {course} c
         LEFT JOIN {report_lp_tracked} lpt
                ON c.id = lpt.courseid AND lpt.courseid IS NULL";
    /*if (isset($course)) {
        $params = array('id' => $course->id);
        $sql = "SELECT c.*
                  FROM {course}
                 WHERE c.id = :id";
    }*/
    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $c) {
        if ($c->id == $SITE->id) {
            continue;
        }
        $assignments = $DB->get_records('assign', array('id'=>$c->id));
        if (count($assignments) == 1) {
            $a = reset($assignments);
            $track = new stdClass();
            $track->courseid = $c->id;
            $track->assignmentid = $a->id;
            $track->modified = time();
            $DB->insert_record('report_lp_tracked', $track);
        }
    }
    $rs->close();
}


function report_lp_detect_assignment_to_track(stdClass $course) {
    global $DB;
    $tracked = $DB->get_record('report_lp_tracked', array('courseid'=>$course->id));
    if (! $tracked) {
        $assignments = $DB->get_records('assign', array('course'=>$course->id));
        if (count($assignments) == 1) {
            $assignment = reset($assignments);
            $settrack = new stdClass();
            $settrack->courseid = $course->id;
            $settrack->assignmentid = $assignment->id;
            $settrack->modified = time();
            $DB->insert_record('report_lp_tracked', $settrack);
        }
    }
}

function report_lp_build_learner_progress_records(stdClass $course) {
    global $CFG, $DB;

    require_once($CFG->libdir . '/grouplib.php');
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
    $studentrole = $DB->get_record('role', array('shortname'=>'student'));
    $users = get_role_users($studentrole->id, $context->get_parent_context());
    foreach ($users as $user) {
        $submission = $assignment->get_user_submission($user->id, false);


        if (!empty($submission->latest)) {
            $record = new stdClass();
            $record->categoryid = $course->category;
            $record->courseid = $course->id;
            $record->coursegroupid = '';
            $record->assignmentid = $assignment->get_instance()->id;
            $record->submissionid = $submission->id;
            $record->submissionstatus = $submission->status;
            $usergrade = $assignment->get_user_grade($user->id, true);

            $record->gradedisplay = $display;
            $display =  grade_format_gradevalue($usergrade->grade, $gradeitem, GRADE_DISPLAY_TYPE_LETTER);
            $record->graderaw = $usergrade->grade;
            $record->gradedisplay = $display;
            $record->modified = time();



        }

    }
    

    
}