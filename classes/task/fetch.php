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

namespace report_lp\task;

/**
 * Simple task to fetch learner progress data.
 */
class fetch extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('fetchprogessdata', 'report_lp');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/report/lp/locallib.php');

        if (isset($CFG->report_lp_disabled)) {
            mtrace('Learner progress fetch data is disabled in config!');
            return true;
        }

        $now = time();
        $lastprocessed = (int) get_config('lastprocessed', 'report_lp');

        $sql = "SELECT c.*
                  FROM {course} c
                  JOIN {report_lp_tracked} lp
                    ON lp.courseid = c.id";

        $rs = $DB->get_records_sql($sql);
        foreach ($rs as $course) {
            report_lp_build_learner_progress_records($course, new \null_progress_trace());
        }

        set_config('lastprocessed', $now, 'report_lp');
    }

}