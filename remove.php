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

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/report/lp/lib.php');

$courseid   = required_param('courseid', PARAM_INT);
$confirmed  = optional_param('confirmed', 0, PARAM_INT);
$course     = get_course($courseid);
$context    = context_course::instance($course->id);

// Access checks.
require_login($course);
require_capability('report/lp:configure', $context);

$pageurl = new moodle_url('/report/lp/remove.php', ['courseid' => $course->id]);
$redirecturl = report_lp\local\factories\url::get_config_url($course);

$PAGE->set_url($pageurl);
$PAGE->set_context($context);

if ($confirmed && confirm_sesskey()) {
    report_lp\local\report::delete_course_instance($course->id);
    // Redirect to configure page.
    redirect($redirecturl);
}

$message = get_string('removereportfor', 'report_lp', $course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($message);
$pageurl->param('confirmed', 1);
$continuebutton = new single_button($pageurl, get_string('delete'), 'post');
echo $OUTPUT->confirm($message, $pageurl, $redirecturl);
echo $OUTPUT->footer();
