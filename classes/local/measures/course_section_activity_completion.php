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
require_once($CFG->libdir . '/completionlib.php');

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

    /** @var completion_info $coursecompletion Hold reference to completion info class. */
    private $coursecompletion;

    /**
     * @var array $sectionactivities Modules in a course section with activity completion.
     */
    private $sectionactivities;

    /**
     * @param $data
     * @param string $format
     * @return string
     */
    public function format_user_measure_data($data, $format = FORMAT_PLAIN) : string {
        if (empty($data)) {
            $label = ' - ';
        } else {
            $percentage = ($data->completed / $data->count) * 100;
            $percentage = floor($percentage) . '%';;
            $outof = "({$data->completed}/{$data->count})";
            $label = "$percentage $outof";
        }
        $class = "measure";
        if ($format == FORMAT_HTML) {
            return html_writer::span($label, $class);
        }
        return $label;
    }

    /**
     * @param int $userid
     * @return mixed|object|stdClass|null
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws coding_exception
     */
    public function get_data_for_user(int $userid) {
        $this->load_instance_data();
        $sectionactivities = $this->sectionactivities;
        $count = count($sectionactivities);
        if (!$count) {
            return null;
        }
        // Get the number of modules that have been completed.
        $completed = 0;
        foreach ($sectionactivities as $activity) {
            $data = $this->coursecompletion->get_data($activity, true, $userid);
            $completed += $data->completionstate == COMPLETION_INCOMPLETE ? 0 : 1;
        }
        $data = new stdClass();
        $data->completed = $completed;
        $data->count = $count;
        return $data;
    }

    /**
     * @param user_list $userlist
     * @return array
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws coding_exception
     */
    public function get_data_for_users(user_list $userlist) : array {
        $data = [];
        foreach ($userlist as $user) {
            $data[$user->id] = $this->get_data_for_user($user->id);
        }
        return $data;
    }

    /**
     * Generate label.
     *
     * @param string $format
     * @return string
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws coding_exception
     */
    public function get_label($format = FORMAT_PLAIN) {
        $defaultlabel = get_string(
            'coursesectionactivitycompletion:measure:label',
            'report_lp'
        );
        $configuration = $this->get_configuration();
        if (is_null($configuration)) {
            return $defaultlabel;
        }
        $extraconfigurationdata = $configuration->get('extraconfigurationdata');
        if (empty($extraconfigurationdata)) {
            return $defaultlabel;
        }
        if ($configuration->get('usecustomlabel')) {
            $name = $configuration->get('customlabel');
        } else {
            $course = get_course($configuration->get('courseid'));
            $modinfo = get_fast_modinfo($course);
            $sectionname = '';
            foreach ($modinfo->get_section_info_all() as $section) {
                if ($section->id == $extraconfigurationdata->id) {
                    $sectionname = $section->name;
                    break;
                }
            }
            $name = get_string(
                'coursesectionactivitycompletion:measure:label:configured',
                'report_lp',
                $sectionname
            );
        }
        return format_text($name, $format);
    }

    /**
     * Options for form.
     *
     * @todo remove in use sections.
     *
     * @return array
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws coding_exception
     */
    public function get_section_options() {
        $course = $this->get_course();
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

    /**
     * Is core so enabled.
     *
     * @return bool|null
     */
    public function is_enabled() {
        return true;
    }

    /**
     * Load completion info and modules from a course section. Attach to class properties as likely to
     * be used multiple times over scope of call.
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws coding_exception
     */
    protected function load_instance_data() {
        $course = $this->get_course();
        if (is_null($this->coursecompletion)) {
            $this->coursecompletion = new completion_info($course);
        }
        if (is_null($this->sectionactivities)) {
            $modinfo = get_fast_modinfo($course);
            $sectionactivities = [];
            $extraconfigurationdata = $this->get_configuration()->get('extraconfigurationdata');
            foreach ($modinfo->get_cms() as $cm) {
                $correctsection = ($cm->section == $extraconfigurationdata->id);
                $hascompletion = ($cm->completion != COMPLETION_TRACKING_NONE);
                if ($correctsection && $hascompletion && !$cm->deletioninprogress) {
                    $sectionactivities[$cm->id] = $cm;
                }
            }
            $this->sectionactivities = $sectionactivities;
        }
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

    /**
     * @return array
     * @throws coding_exception
     */
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

    /**
     * @param $data
     * @return stdClass
     * @throws coding_exception
     */
    public function moodlequickform_get_extra_configuration_data($data) : stdClass {
        if (empty($data['coursesection'])) {
            throw new coding_exception('Something went horribly wrong');
        }
        $object = new stdClass();
        $object->id = $data['coursesection'];
        return $object;
    }

    /**
     * @param $data
     * @param $files
     * @return array
     * @throws coding_exception
     */
    public function moodlequickform_validation($data, $files) : array {
        $errors = [];
        if ($data['coursesection'] == 0) {
            $errors['coursesection'] = get_string('pleasechoose', 'report_lp');
        }
        return $errors;
    }
}
