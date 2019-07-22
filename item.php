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

$id         = optional_param('id', 0, PARAM_INT);
$courseid   = optional_param('courseid', 0, PARAM_INT);
$shortname  = optional_param('shortname', null, PARAM_ALPHAEXT);
if ($id) {
    $itemconfiguration = new report_lp\local\persistents\item_configuration($id);
    $courseid = $itemconfiguration->get('courseid');
} else {
    if ($courseid <= 0) {
        throw new moodle_exception('Parameter courseid is required');
    }
    if (empty($shortname)) {
        throw new moodle_exception('Parameter shortname is required');
    }
}
$course = get_course($courseid);
$systemcontext = context_system::instance();
$itemtypelist = new report_lp\local\item_type_list();
$itemfactory = new report_lp\local\factories\item($course, $itemtypelist);
if (isset($itemconfiguration)) {
    $item = $itemfactory->get_item($itemconfiguration);
} else if (!is_null($shortname)){
    if (!$itemtypelist->item_type_exists($shortname)) {
        throw new moodle_exception("{$shortname} is not a reqistered item type");
    }
    $item = $itemfactory->get_from_shortname($shortname);
} else {
    throw new moodle_exception("Could not load item");
}
$configurl = report_lp\local\factories\url::get_config_url($course);
$pageurl = report_lp\local\factories\url::get_item_url($course, $id, $shortname);
require_capability('report/lp:configure', $systemcontext);
$PAGE->set_context($systemcontext);
$PAGE->set_url($pageurl);
$mform = new report_lp\local\forms\item(
    null,
    [
        'course' => $course,
        'item' => $item
    ]
);
$renderer = $PAGE->get_renderer('report_lp');
if ($mform->is_cancelled()) {
    redirect($configurl);
}
if ($mform->is_submitted()) {
    $data = $mform->get_data();
    if ($data) {
        $item->get_configuration()->set('usecustomlabel', $data->usecustomlabel);
        $item->get_configuration()->set('customlabel', isset($data->customlabel) ? $data->customlabel : '');
        $item->get_configuration()->set('parentitemid', $data->parentitemid);
        $item->get_configuration()->set('visibletosummary', $data->visibletosummary);
        $item->get_configuration()->set('visibletoinstance', $data->visibletoinstance);
        $item->get_configuration()->set('visibletolearner', $data->visibletolearner);
        $extradata = $mform->get_extra_configuration_data();
        $item->get_configuration()->set('extraconfigurationdata', $extradata);
        $item->get_configuration()->save();
        redirect($configurl);
    }
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('configure', 'report_lp', $item->get_name()));
$mform->display();
echo $OUTPUT->footer($course);
