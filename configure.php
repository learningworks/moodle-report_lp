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

$id = required_param('id', PARAM_INT);
$params = ['id' => $id];
$course = $DB->get_record('course', $params, '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);
$PAGE->set_context($context);
require_login($course);
$url = new moodle_url('/report/lp/configure.php', $params);
$PAGE->set_url($url);
echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
