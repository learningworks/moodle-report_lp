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

namespace report_lp\local\measures;

defined('MOODLE_INTERNAL') || die();

global $CFG;

use coding_exception;
use completion_info;
use html_writer;
use MoodleQuickForm;
use report_lp\local\contracts\has_own_configuration;
use report_lp\local\measure;
use report_lp\local\user_list;
use stdClass;

class course_section_activity_completion extends measure implements has_own_configuration {

    /** @var string COMPONENT_TYPE Used to identify core subsystem or plugin type. */
    public const COMPONENT_TYPE = 'core';

    /** @var string COMPONENT_NAME Used to for name of core subsystem or plugin. */
    public const COMPONENT_NAME = 'course';

    public function format_user_measure_data($data, $format = FORMAT_PLAIN) : string {}

    public function get_data_for_user(int $userid) {}

    public function get_data_for_users(user_list $userlist) : array {}

    public function get_label($format = FORMAT_PLAIN) {
        $configuration = $this->get_configuration();
        if (is_null($configuration)) {
            return get_string('defaultlabelcoursesectionactivitycompletion', 'report_lp');
        }
        $extraconfigurationdata = $configuration->get('extraconfigurationdata');
        if (empty($extraconfigurationdata)) {
            return get_string('defaultlabelcoursesectionactivitycompletion', 'report_lp');
        }
        return get_string('defaultlabelcoursesectionactivitycompletion', 'report_lp');
    }

    public function get_section_options() {
        $configuration = $this->get_configuration();
        if (is_null($configuration)) {
            throw new coding_exception('Configuration must loaded');
        }
        $course = get_course($configuration->get('courseid'));
        $modinfo = get_fast_modinfo($course);
        $options = [];
        foreach ($modinfo->get_section_info_all() as $section) {
            $options[$section->id] = $section->name;
        }
        return $options;
    }

    /**
     * Name of measure.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string('coursesectionactivitycompletion:measure:name', 'report_lp');
    }

    /**
     * Description of what data/information this measure displays.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_description(): string {
        return get_string('coursesectionactivitycompletion:measure:description', 'report_lp');
    }

    public function moodlequickform_extend(MoodleQuickForm &$mform) {
        $sections = $this->get_section_options();
        if (empty($sections)) {
            $mform->addElement(
                'warning',
                'nosectionswarning',
                null,
                get_string('noavailablesections', 'report_lp')
            );
            $mform->addElement('hidden', 'nosections');
            $mform->setType('nosections', PARAM_INT);
            $mform->setDefault('nosections', 1);
            $mform->disabledIf('submitbutton', 'nosections', 'eq', 1);
            $mform->removeElement('specific');
        } else {
            $options = [0 => get_string('choose')] +  $sections;
            $mform->addElement('select', 'coursesection',
                get_string('coursesection', 'report_lp'), $options);
        }
    }

    public function moodlequickform_get_extra_configuration_defaults() : array {
        $configuration = $this->get_configuration();
        $extraconfigurationdata = $configuration->get('extraconfigurationdata');
        $defaults = [];
        if (empty($extraconfigurationdata)) {
            $defaults['coursesection'] = 0;
        } else {
            $defaults['coursesection'] = $extraconfigurationdata->id;
        }
        return $defaults;
    }

    public function moodlequickform_get_extra_configuration_data($data) : stdClass {
        if (empty($data['coursesection'])) {
            throw new coding_exception('Something went horribly wrong');
        }
        $object = new stdClass();
        $object->id = $data['coursesection'];
        return $object;
    }

    public function moodlequickform_validation($data, $files) : array {
        $errors = [];
        if ($data['coursesection'] == 0) {
            $errors['coursesection'] = get_string('pleasechoose', 'report_lp');
        }
        return $errors;
    }
}
