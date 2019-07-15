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
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/completionlib.php');

use coding_exception;
use completion_info;
use grade_category;
use html_writer;
use MoodleQuickForm;
use report_lp\local\contracts\has_own_configuration;
use report_lp\local\measure;
use report_lp\local\user_list;
use stdClass;

/**
 * The progress percentage of activities completed in a grade book category.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grade_category_activity_completion extends measure implements has_own_configuration {

    /** @var string COMPONENT_TYPE Used to identify core subsystem or plugin type. Moodle frankenstyle. */
    public const COMPONENT_TYPE = 'core';

    /** @var string COMPONENT_NAME Used to for name of core subsystem or plugin. Moodle frankenstyle. */
    public const COMPONENT_NAME = 'grades';

    /**
     * @param $data
     * @param string $format
     * @return string
     */
    public function format_user_measure_data($data, $format = FORMAT_PLAIN) : string {
        $label = ' - ';
        if (!empty($data)) {
            $label = floor($data) . '%';
        }
        $class = "measure";
        if ($format == FORMAT_HTML) {
            return html_writer::span($label, $class);
        }
        return $label;
    }

    /**
     * @param int $userid
     * @return float|int|mixed|null
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws coding_exception
     */
    public function get_data_for_user(int $userid) {

        static $activities;

        static $gradecategory;

        static $completion;

        if (is_null($gradecategory) || is_null($completion) || is_null($activities)) {
            $configuration = $this->get_configuration();
            if (is_null($configuration)) {
                throw new coding_exception('Configuration must loaded');
            }
            $extraconfigurationdata = $configuration->get('extraconfigurationdata');
            if (!isset($extraconfigurationdata->id)) {
                throw new coding_exception('No valid extra configuration data found');
            }
            $gradecategory = grade_category::fetch(['id' => $extraconfigurationdata->id]);
            $gradecategorymods = [];
            foreach ($gradecategory->get_children(false) as $child) {
                /** @var \grade_item $child */
                $gradeitem = $child['object'];
                if (!$gradeitem->is_external_item()) {
                    continue;
                }
                $key = $gradeitem->itemmodule . ':' . $gradeitem->iteminstance;
                $gradecategorymods[$key] = $key;
            }
            $course = get_course($configuration->get('courseid'));
            $completion = new completion_info($course);
            $modinfo = get_fast_modinfo($course);
            $activities = [];
            foreach ($modinfo->get_cms() as $cm) {
                if ($cm->completion != COMPLETION_TRACKING_NONE && !$cm->deletioninprogress) {
                    $key = $cm->modname . ':' . $cm->instance;
                    if (in_array($key, $gradecategorymods)) {
                        $activities[$cm->id] = $cm;
                    }
                }
            }
        }

        $count = count($activities);
        if (!$count) {
            return null;
        }

        // Get the number of modules that have been completed.
        $completed = 0;
        foreach ($activities as $activity) {
            $data = $completion->get_data($activity, true, $userid);
            $completed += $data->completionstate == COMPLETION_INCOMPLETE ? 0 : 1;
        }

        return ($completed / $count) * 100;
    }

    /**
     * @param user_list $userlist
     *
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
     * Build label for
     *
     * @param string $format
     * @return string
     * @throws coding_exception
     */
    public function get_label($format = FORMAT_PLAIN) {

        $configuration = $this->get_configuration();
        if (is_null($configuration)) {
            return get_string('categoryname', static::COMPONENT_NAME);
        }
        $extraconfigurationdata = $configuration->get('extraconfigurationdata');
        if (empty($extraconfigurationdata)) {
            return get_string('categoryname', static::COMPONENT_NAME);
        }
        if ($configuration->get('usecustomlabel')) {
            $name = $configuration->get('customlabel');
        } else {
            $gradecategory = grade_category::fetch(
                ['id' => $extraconfigurationdata->id]
            );
            $name = get_string(
                'defaultlabelgradecategoryconfigured',
                'report_lp',
                $gradecategory->get_name());
        }
        return format_text($name, $format);
    }

    /**
     * Name of measure.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string('gradecategoryactivitycompletion:measure:name', 'report_lp');
    }

    /**
     * Description of what data/information this measure displays.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_description(): string {
        return get_string('gradecategoryactivitycompletion:measure:description', 'report_lp');
    }

    /**
     * @return array
     * @throws coding_exception
     */
    protected function get_grade_category_options() {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        $options = [];
        $categories = grade_category::fetch_all(
            ['courseid' => $this->get_configuration()->get('courseid')]
        );
        $coursecategory = null;
        /** @var grade_category $category */
        foreach ($categories as $category) {
            if ($category->is_course_category()) {
                $coursecategory = $category;
                continue;
            }
            if ($category->is_hidden()) {
                continue;
            }
            // Build a named path for option label.
            $path = explode('/', $category->path);
            $nameditems = [];
            foreach ($path as $item) {
                if (!(empty($item) || ($item == $coursecategory->id))) {
                    $nameditems[] = $categories[$item]->get_name();
                }
            }
            $namedpath = implode('/', $nameditems);
            $category->apply_forced_settings();
            $options[$category->id] = $namedpath;
        }
        asort($options);
        return $options;
    }

    /**
     * @param MoodleQuickForm $mform
     * @return mixed|void
     * @throws coding_exception
     */
    public function moodlequickform_extend(MoodleQuickForm &$mform) {
        $gradecategories = $this->get_grade_category_options();
        if (empty($gradecategories)) {
            $mform->addElement(
                'warning',
                'nogradecategorieswarning',
                null,
                get_string('noavailablegradecategories', 'report_lp')
            );
            $mform->addElement('hidden', 'nogradecategories');
            $mform->setType('nogradecategories', PARAM_INT);
            $mform->setDefault('nogradecategories', 1);
            $mform->disabledIf('submitbutton', 'nogradecategories', 'eq', 1);
            $mform->removeElement('specific');
        } else {
            $options = [0 => get_string('choose')] +  $gradecategories;
            $mform->addElement('select', 'gradecategory',
                get_string('gradecategory', static::COMPONENT_NAME), $options);
        }
    }

    /**
     * Extend validation for extra configuration.
     *
     * @param $data
     * @param $files
     * @return array
     * @throws coding_exception
     */
    public function moodlequickform_validation($data, $files) : array {
        $errors = [];
        if ($data['gradecategory'] == 0) {
            $errors['gradecategory'] = get_string('pleasechoose', 'report_lp');
        }
        return $errors;
    }

    /**
     * Format extra configuration data.
     *
     * @param $data
     * @return stdClass
     * @throws coding_exception
     */
    public function moodlequickform_get_extra_configuration_data($data) : stdClass {
        if (empty($data['gradecategory'])) {
            throw new coding_exception('Something went horribly wrong');
        }
        $object = new stdClass();
        $object->id = $data['gradecategory'];
        return $object;
    }

    /**
     * Get defaults based on extra configuration data.
     *
     * @return array
     * @throws coding_exception
     */
    public function moodlequickform_get_extra_configuration_defaults() : array {
        $configuration = $this->get_configuration();
        $extraconfigurationdata = $configuration->get('extraconfigurationdata');
        $defaults = [];
        if (empty($extraconfigurationdata)) {
            $defaults['gradecategory'] = 0;
        } else {
            $defaults['gradecategory'] = $extraconfigurationdata->id;
        }
        return $defaults;
    }
}
