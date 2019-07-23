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
$itemid     = optional_param('itemid', 0, PARAM_INT);
$action     = optional_param('action', '', PARAM_ALPHA);
$confirmed  = optional_param('confirmed', 0, PARAM_INT);

$course = get_course($courseid);
$coursecontext = context_course::instance($courseid);

require_login($course);
require_capability('report/lp:configure', $coursecontext);

$url = report_lp\local\factories\url::get_config_url($course);
$PAGE->set_url($url);

$css = new moodle_url('/report/lp/scss/styles.css');
$PAGE->requires->css($css);
$PAGE->requires->js_call_amd('report_lp/move', 'init', ['list-configured-items', 'move']);
$renderer = $PAGE->get_renderer('report_lp');
switch ($action) {
    case 'moveup':
        if (confirm_sesskey()) {
            if (empty($itemid)) {
                throw new moodle_exception('Empty itemid');
            }
        }
        redirect($pageurl);
        break;
    case 'movedown':
        if (confirm_sesskey()) {
            if (empty($itemid)) {
                throw new moodle_exception('Empty itemid');
            }
        }
        redirect($pageurl);
        break;
    case 'delete':
        if (confirm_sesskey()) {
            if (empty($itemid)) {
                throw new moodle_exception('Empty itemid');
            }
            $itemfactory = new report_lp\local\factories\item($course, new report_lp\local\item_type_list());
            $item = $itemfactory->get_item_from_persistent(new report_lp\local\persistents\item_configuration($itemid));
            if ($confirmed) {
                $configuration = $item->get_configuration();
                if ($item instanceof report_lp\local\grouping) {
                    $children = $configuration->get_children();
                    foreach ($children as $child) {
                        $child->delete();
                    }
                }
                $configuration->delete();
                redirect($url);
            }
            if ($item instanceof report_lp\local\grouping) {
                $message = get_string('deletegroupingitem', 'report_lp', $item->get_label());
            } else {
                $message  = get_string('deletemeasureitem', 'report_lp', $item->get_label());
            }
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('configurereportfor', 'report_lp', $course->fullname));
            $continueurl = report_lp\local\factories\url::get_item_action_url($courseid, $itemid, 'delete');
            $continueurl->param('confirmed', 1);
            $continuebutton = new single_button($continueurl, get_string('delete'), 'post');
            echo $OUTPUT->confirm($message, $continuebutton, $url);
            echo $OUTPUT->footer();
            exit;
        }
        redirect($pageurl);
        break;
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('configurereportfor', 'report_lp', $course->fullname));
echo $renderer->render(new report_lp\output\configure_report($course));
echo $OUTPUT->footer();
