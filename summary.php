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
require_once($CFG->libdir . '/grouplib.php');

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
$coursecontext = context_course::instance($courseid);

require_login($course);
require_capability('report/lp:viewsummary', $coursecontext);

$url = $url = new moodle_url('/report/lp/summary.php', ['courseid' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');
$css = new moodle_url('/report/lp/scss/styles.css');
$PAGE->requires->css($css);

$summary = new report_lp\local\summary($course);
$itemtypelist = new report_lp\local\item_type_list(report_lp_get_supported_measures());
$summary->add_item_type_list($itemtypelist);
$learnerlist = new report_lp\local\learner_list($course);
$filteredcoursegroups = report_lp\local\course_group::get_active_filter($course->id);
$learnerlist->add_course_groups_filter($filteredcoursegroups);
$summary->add_learner_list($learnerlist);

$renderer = $PAGE->get_renderer('report_lp');
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('summaryreportfor', 'report_lp', $course->fullname));
echo $renderer->render_group_filter($course);
echo $renderer->render(new report_lp\output\summary_report($summary));
echo $OUTPUT->footer();
