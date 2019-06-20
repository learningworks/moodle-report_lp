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
$measurelist = new report_lp\local\measure_list(report_lp_get_supported_measures());
$itemfactory = new report_lp\local\factories\item($course, $measurelist);
$grouping = $itemfactory->create_grouping($id, $record);
$pageurl = report_lp\local\factories\url::get_grouping_url($course, $id);
$configurl = report_lp\local\factories\url::get_config_url($course);
require_capability('report/lp:configure', $systemcontext);
$PAGE->set_context($systemcontext);
$PAGE->set_url($pageurl);
$mform = new report_lp\local\forms\item(
    null,
    [
        'course' => $course,
        'item' => $grouping
    ]
);
$renderer = $PAGE->get_renderer('report_lp');
if ($mform->is_cancelled()) {
    redirect($configurl);
}
if ($mform->is_submitted()) {
    $data = $mform->get_data();
    if ($data) {
        $grouping->get_configuration()->set('usecustomlabel', $data->usecustomlabel);
        $grouping->get_configuration()->set('customlabel', ($data->usecustomlabel) ? $data->customlabel : '');
        $grouping->get_configuration()->set('visibletosummary', $data->visibletosummary);
        $grouping->get_configuration()->set('visibletoinstance', $data->visibletoinstance);
        $grouping->get_configuration()->set('visibletolearner', $data->visibletolearner);
        $grouping->get_configuration()->save();
        redirect($configurl);
    }
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('configuregrouping', 'report_lp'));
$mform->display();
echo $OUTPUT->footer($course);
