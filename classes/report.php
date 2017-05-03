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

namespace report_lp;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/querylib.php');

class report extends \table_sql {
    protected $category;
    protected $groupname;
    protected $courses;

    public function __construct($uniqueid, $groupname=null, $category=null, $download = '') {
        global $PAGE;
        parent::__construct($uniqueid);
        $this->set_attribute('class', 'generaltable generalbox learner-progress');
        $this->collapsible(false);
        $this->sortable(false);
        $this->pageable(false);
        $this->is_downloadable(true);
        // Set download status.
        $this->is_downloading($download, get_string('exportfilename', 'report_lp'));
        $this->baseurl      = $PAGE->url;
        $this->rawdata      = array();
        $this->groupname    = $groupname;
        $this->category     = $category;
        $this->initialize();
    }

    public function initialize() {
        global $DB;

        $columns = array('user');
        $headers = array('');

        if ($this->is_downloading()) {
            $columns = array('user', 'groupname', 'categoryname');
            $headers = array('', get_string('group'), get_string('category'));
        }

        // Category identifier, so get category record.
        if (!is_object($this->category)) {
            $this->category = $DB->get_record('course_categories', array('id' => $this->category), "id, name, idnumber");
        }
        if ($this->category) {
            // Fetch courses to build rest of columns.
            $sql = "SELECT c.id, c.shortname, c.fullname, c.idnumber 
                  FROM {course} c
                  JOIN {course_categories} cc 
                    ON cc.id = c.category
                  JOIN {report_lp_tracked} lpt 
                    ON lpt.courseid = c.id
                 WHERE c.category = :category
              ORDER BY c.sortorder";

            $params = array('category' => $this->category->id);
            $this->courses = $DB->get_recordset_sql($sql, $params);
        }
        if ($this->courses) {
            foreach ($this->courses as $course) {
                $columns[] = $course->shortname;
                $headers[] = $course->shortname;
            }
        }
        parent::define_columns($columns);
        parent::define_headers($headers);
    }


    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;
        // No params set so no need to go any further.
        if (empty($this->groupname) or empty($this->category)) {
            return false;
        }

        $userfields = \user_picture::fields('u', null, 'user_id', 'user_');

        $coursefields = utilities::alias(array('id', 'category', 'shortname', 'fullname', 'idnumber', 'visible'),
            'c', 'course_');

        $groupfields = utilities::alias(array('name'),
            'g', 'group_');

        $categoryfields = utilities::alias(array('name'),
            'cc', 'coursecategory_');

        $progressfields = utilities::alias(array('coursegroupid', 'assignmentid', 'submissionid', 'submissionstatus', 'submissiongraderaw', 'coursegraderaw'),
            'lp', 'progress_');
        
        $basesql = "FROM {report_lp_learnerprogress} lp
                    JOIN {course_categories} cc
                      ON cc.id = lp.categoryid
                    JOIN {course} c 
                      ON c.id = lp.courseid
                    JOIN {groups} g 
                      ON g.id = lp.coursegroupid
                    JOIN {user} u 
                      ON u.id = lp.userid
                   WHERE g.name = :groupname AND c.category = :category ";

        $ordersql = "ORDER BY u.firstname, u.lastname, c.sortorder";

        $sql = "SELECT $userfields, $coursefields, $groupfields, $categoryfields, $progressfields $basesql $ordersql";

        // Are we downloading.
        $downloading = $this->is_downloading();

        $collect = array();
        $params = array();
        $params['groupname'] = $this->groupname;
        $params['category'] = $this->category->id;
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $record) {
            $unaliased = utilities::unalias($record);
            $user = (object) $unaliased['user'];
            $group = (object) $unaliased['group'];
            $category = (object) $unaliased['coursecategory'];
            $course = (object) $unaliased['course'];
            $progress = (object) $unaliased['progress'];
            if (!isset($collect[$user->id])) {
                $keys = $this->columns;
                array_walk($keys, function(&$value, $key) use($downloading) {
                    global $OUTPUT;
                    if ($downloading) {
                        $value = get_string('notenrolled', 'report_lp');
                    } else {
                        $value = \html_writer::img($OUTPUT->pix_url('t/delete'),
                            get_string('notenrolled', 'report_lp'),
                            array('title' => get_string('notenrolled', 'report_lp'))) .
                            ' ' . get_string('notenrolled', 'report_lp');
                    }
                });
                $collect[$user->id] = $keys;
                $collect[$user->id]['user'] = fullname($user); // First column will always be user.
                if (!$downloading) {
                    $userlink = \html_writer::link(new \moodle_url('/user/profile.php', array('id' => $user->id)), fullname($user));
                    $collect[$user->id]['user'] = $userlink;
                }
            }

            if ($downloading) {
                $collect[$user->id]['groupname'] = $group->name;
                $collect[$user->id]['categoryname'] = $category->name;
            }

            $usergrade = grade_get_course_grade($user->id, $course->id);
            // Dirty hackery, as can't rely on grade to pass being setup each course as this stage.
            if ($usergrade->str_grade == '-') {
                $status = \core_text::strtolower($progress->submissionstatus);
                $cm = self::get_cm_by_assignment($progress->assignmentid, $course);
                $reporturl = new \moodle_url('/mod/assign/view.php', array('id'=>$cm->id, 'group'=>$progress->coursegroupid, 'action'=>'grading'));
                $label = get_string('submissionstatus_' . $status, 'assign');
                $output = \html_writer::link($reporturl, $label);
            } else {
                $reporturl = new \moodle_url('/grade/report/user/index.php', array('id'=>$course->id, 'userid'=>$user->id));
                $label = $usergrade->str_grade;
                $class = preg_replace('/\s+/', '-', \core_text::strtolower($usergrade->str_grade));
                $link = \html_writer::link($reporturl, $label);
                $output = \html_writer::div($link, $class);
            }
            if ($downloading) {
                $output = $label;
            }
            $collect[$user->id][$course->shortname] = $output;

        }
        $rs->close();

        $this->rawdata = $collect;
    }

    /**
     * Helper method to get course module for assignment.
     *
     * @param $assignmentid
     * @param $course
     * @return mixed
     * @throws \coding_exception
     */
    public function get_cm_by_assignment($assignmentid, $course) {
        static $assignments;
        if (!isset($assignments[$assignmentid])) {
            $cm = get_coursemodule_from_instance("assign", $assignmentid, $course->id);
            $assignments[$assignmentid] = $cm;
        }
        return $assignments[$assignmentid];
    }
}
