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

namespace report_learnerprogress;

defined('MOODLE_INTERNAL') || die();

class learnerprogress {

    public static function get_course_groupnames() {
        global $DB;

        $sql = "SELECT DISTINCT (g.name)
                           FROM {groups} g
                           JOIN {report_learnerprogress} rlp
                             ON rlp.courseid = g.courseid
                       GROUP BY g.name
                       ORDER BY g.name";

        $names = [];
        $records = $DB->get_records_sql($sql);
        if ($records) {
           foreach($records as $record) {
               $names[$record->name] = $record->name;
           }
        }
        return $names;
    }

    public static function get_course_categorynames_by_group($groupname) {
        global $DB;

        $sql = "SELECT DISTINCT(cc.id) AS categoryid, cc.name as categoryname
                           FROM mdl_course_categories cc
                           JOIN mdl_course c
                             ON c.category = cc.id
                           JOIN mdl_groups g
                             ON g.courseid = c.id
                          WHERE g.name = ?
                       GROUP BY cc.id, cc.name
                       ORDER BY cc.name";

        $categories = [];
        $records = $DB->get_records_sql($sql, [$groupname]);
        if ($records) {
            foreach($records as $record) {
                $categories[$record->categoryid] = $record->categoryname;
            }
        }
        return $categories;
    }

}