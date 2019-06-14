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
require_once($CFG->libdir . '/formslib.php');

$id = optional_param('id', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$measure = optional_param('measure', null, PARAM_ALPHAEXT);
if ($id) {
    $record = $DB->get_record('report_lp_items', ['id' => $id], '*', MUST_EXIST);
    $courseid = $record->courseid;
} else {
    $record = null;
}
if ($courseid <= 0) {
    throw new moodle_exception('Bad courseid');
}
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$systemcontext = context_system::instance();
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('configuremeasure', 'report_lp'));
echo $OUTPUT->footer($course);
