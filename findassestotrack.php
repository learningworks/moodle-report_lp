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

$run = optional_param('run', 0, PARAM_INT);

require_login();

$context = context_system::instance();
require_capability('report/lp:configure', $context);
$PAGE->set_context($context);
$pageurl = new moodle_url('/report/lp/findassestotrack.php');
$PAGE->set_url($pageurl);

echo $OUTPUT->header();

$runurl = clone($pageurl);
$runurl->param('run', 1);
echo $OUTPUT->render(new action_link($runurl, 'Run'));

if ($run) {
    $trace = new html_list_progress_trace();

    $sql = "SELECT c.*
              FROM {course} c
         LEFT JOIN {report_lp_tracked} lp
                ON lp.courseid = c.id
             WHERE c.id <> :siteid AND lp.id IS NULL";

    $courses = $DB->get_records_sql($sql, array('siteid'=>SITEID));
    if (empty($courses)) {
        $trace->output('No courses with assignments left to track!');
    } else {
        foreach ($courses as $course) {
            $assignments = $DB->get_records('assign', array('course' => $course->id));
            $trace->output('Looking at course >> ' . $course->fullname);
            if (count($assignments) == 1) {

                $assignment = reset($assignments);

                $trace->output('Found 1 assignment: ' . $assignment->name, 1);
                $settrack = new stdClass();
                $settrack->courseid = $course->id;
                $settrack->assignmentid = $assignment->id;
                $settrack->modified = time();
                if (!$DB->record_exists('assign', array('course' => $course->id))) {
                    $DB->insert_record('report_lp_tracked', $settrack);
                    $trace->output('Now tracking', 2);
                }
            } else {
                $count = count($assignments);
                $trace->output("Found $count assignments, skipping...", 1);
            }
        }
    }
}

echo $OUTPUT->footer();
