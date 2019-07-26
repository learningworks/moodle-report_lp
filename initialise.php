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

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
$context = context_course::instance($courseid);

require_login($course);
require_capability('report/lp:configure', $context);

$pageurl = report_lp\local\factories\url::get_initialise_url($course->id);
$redirecturl = report_lp\local\factories\url::get_config_url($course);

$PAGE->set_url($pageurl);
$PAGE->set_context($context);

if (!report_lp\local\report::course_instance_exists($course)) {
    report_lp\local\report::create_course_instance($course);
}
redirect($redirecturl);
