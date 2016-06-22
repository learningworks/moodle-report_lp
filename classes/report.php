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

use core\session\util;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

class report extends \table_sql {
    protected $category;
    protected $groupname;
    protected $courses;

    public function __construct($uniqueid, $groupname, $category) {
        parent::__construct($uniqueid);
        $this->groupname = $groupname;
        if (is_object($category)) {
            $this->category = $category;
        } else {
            $this->fetch_category($category);
        }

        $this->setup_table_head();
    }

    public function setup_table_head() {
        $columns = array('user');
        $headers = array('');

        $sql = "SELECT c.* 
FROM {course} c
JOIN {course_categories} cc ON cc.id = c.category
JOIN {report_lp_tracked} lpt ON lpt.courseid = c.id";

        foreach ($this->get_courses() as $course) {
            $columns[] = $course->shortname;
            $headers[] = $course->fullname;
        }
        parent::define_columns($columns);
        parent::define_headers($headers);
    }

    public function fetch_category($id) {
        global $DB;

        if (isset($this->category)) {
            return $this->category;
        }
        return $this->category = $DB->get_record('course_categories', array('id'=>$id), "id, name, idnumber");
    }

    public function get_courses() {
        global $DB;

        if (isset($this->courses)) {
            return $this->courses;
        }
        if (!isset($this->category->id)) {
            throw new \moodle_exception('No category defined.');
        }
        $params = array('category'=>$this->category->id);
        return $this->courses = $DB->get_records('course', $params, 'sortorder', "id, shortname, fullname, idnumber");



    }

    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        $userfields = \user_picture::fields('u', null, 'user_id', 'user_');

        $coursefields = utilities::alias(array('id', 'category', 'shortname', 'fullname', 'idnumber', 'visible'),
            'c', 'course_');
        
        $basesql = "FROM {report_lp_learnerprogress} lp
                    JOIN {course_categories} cc
                      ON cc.id = lp.categoryid
                    JOIN {course} c 
                      ON c.id = lp.courseid
                    JOIN {groups} g 
                      ON g.id = lp.coursegroupid
                    JOIN {user} u 
                      ON u.id = lp.userid
                   WHERE g.name = :groupname AND c.category = :category";


        $sql = "SELECT $userfields, $coursefields $basesql";
        mtrace($sql);

        print_object($this->columns);

        $params = array();
        $params['groupname'] = $this->groupname;
        $params['category'] = $this->category->id;
        $rs = $DB->get_recordset_sql($sql, $params);
        //print_object($rs);
        $rs->close();
        $this->rawdata = array();
    }
}