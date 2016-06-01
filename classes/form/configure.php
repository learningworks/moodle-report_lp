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

namespace report_learnerprogress\form;

defined('MOODLE_INTERNAL') || die();

// Load repository library, will load filelib and formslib.
require_once($CFG->dirroot . '/repository/lib.php');
class configure extends \moodleform {
    protected function definition() {
        global $DB;
        $form = $this->_form;
        $course = $this->_customdata['course'];
        $assignmentid = isset($this->_customdata['assignmentid']) ? $this->_customdata['assignmentid'] : 0;

        // Build menu of assignments.
        $assignments = $DB->get_records_menu('assign', ['course' => $course->id], null, 'id, name');
        $menu = ['0' => get_string('none')] + $assignments;
        $form->addElement('select','assignmentid', get_string('trackassignment', 'report_learnerprogress'), $menu);
        $form->setType('assignmentid', PARAM_INT);
        $form->setDefault('assignmentid', $assignmentid);
        $form->addElement('hidden', 'id');
        $form->setType('id', PARAM_INT);
        $form->setDefault('id', $course->id);

        $this->add_action_buttons();
    }
}