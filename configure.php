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
$params = ['id' => $courseid];
$course = $DB->get_record('course', $params, '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);
$PAGE->set_context($context);
require_login($course);
$url = new moodle_url('/report/lp/configure.php', ['courseid' => $courseid]);
$PAGE->set_url($url);

$measures = report_lp_get_supported_measures();
$measurelist = new report_lp\local\measurelist($measures);
$learnerprogress = new report_lp\local\learner_progress($course, $measurelist);


$renderer = $PAGE->get_renderer('report_lp');
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('configurereportfor', 'report_lp', $course->fullname));
echo $renderer->render(new report_lp\output\add_item_menu($course, $measurelist));
$tree = $learnerprogress->build_item_tree();
echo "<br>";
foreach ($tree as $item) {
    echo html_writer::tag('strong', $item->get_label()) . '<br>';
    if ($item instanceof report_lp\local\grouping) {
        if ($item->has_children()) {
            foreach ($item->get_children() as $childitem) {
                echo html_writer::span($childitem->get_label()) . '<br>';
            }
        }
    }
}
echo $OUTPUT->footer();
