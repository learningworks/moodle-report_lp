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

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

$id = required_param('id', PARAM_INT);
$params = ['id' => $id];
$course = $DB->get_record('course', $params, '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

require_login($course);

$url = new moodle_url('/report/lp/configure.php', $params);
$PAGE->set_url($url);

// Setup return url.
$returnurl = new moodle_url('/course/view.php', array('id' => $PAGE->course->id));

$record = $DB->get_record('report_lp_tracked', ['courseid' => $course->id]);
$assignmentid = isset($record->assignmentid) ? $record->assignmentid : 0;
$form = new \report_lp\form\configure(null, array('course' => $course, 'assignmentid' => $assignmentid));
if ($form->is_cancelled()) {
    redirect($returnurl);
}
if ($form->is_submitted()) {
    $data = $form->get_data();
    if ($record and !$data->assignmentid) { // Delete.
        $DB->delete_records('report_lp_tracked', ['courseid' => $course->id]);
    } else if ($record and $data->assignmentid) { // Update.
        $record->assignmentid = $data->assignmentid;
        $DB->update_record('report_lp_tracked');
    } else if ($data->assignmentid) { // Insert.
        $record = new stdClass();
        $record->courseid = $course->id;
        $record->assignmentid = $data->assignmentid;
        $DB->insert_record('report_lp_tracked', $record);
    }
    redirect($returnurl);
}
echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
