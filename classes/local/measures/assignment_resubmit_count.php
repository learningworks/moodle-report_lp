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
require_once($CFG->dirroot . '/mod/assign/lib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

use assign;
use coding_exception;
use context_module;
use core_text;
use html_writer;
use moodle_url;
use MoodleQuickForm;
use pix_icon;
use report_lp\local\contracts\extra_configuration;
use report_lp\local\measure;
use report_lp\local\persistents\item_configuration;
use report_lp\local\user_list;
use stdClass;
use core_plugin_manager;

/**
 * Assignment resubmit count of a learner for an assignment instance.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignment_resubmit_count extends measure implements extra_configuration {

    /** @var string COMPONENT_TYPE Used to identify core or plugin type. Moodle frankenstyle. */
    public const COMPONENT_TYPE = 'mod';

    /** @var string COMPONENT_NAME Used to for name of core subsystem or plugin. Moodle frankenstyle. */
    public const COMPONENT_NAME = 'assign';

    /** @var assign $assignment Associated instance of assign based on configuration. */
    protected $assignment;

    /**
     * Format measure data for cell.
     *
     * @param $data
     * @param string $format
     * @return string
     */
    public function format_user_measure_data($data, $format = FORMAT_PLAIN) : string {
        $label = ' - ';
        if ($data->submissionattemptnumber) {
            $label = $data->submissionattemptnumber;
        }
        $class = "measure";
        if ($format == FORMAT_HTML) {
            return html_writer::span($label, $class);
        }
        return $label;
    }

    /**
     * Build data for user. Uses the assign and gradeitem API classes.
     *
     * @param stdClass $user
     * @return stdClass
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function get_data_for_user(stdClass $user) : stdClass {
        $assignment = $this->get_assignment();
        $submission = $assignment->get_user_submission($user->id, true);
        // Payload.
        $data = new stdClass();
        $data->userid = $user->id;
        $data->assignmentid = $assignment->get_instance()->id;
        $data->submissionid = $submission->id;
        $data->submissionattemptnumber = $submission->attemptnumber;
        return $data;
    }

    /**
     * Get learner data keyed up on user identifiers. For now we have to iterate list and call
     * get_data_for_user.
     *
     * @param user_list $userlist
     * @return array|null
     * @throws \dml_exception
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
     * Default label for assignment resubmit count.
     *
     * @return string
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function get_default_label(): string {
        if (is_null( $this->get_id()) || $this->get_id() <= 0) {
            return get_string('assignmentresubmitcount:measure:defaultlabel', 'report_lp');
        }
        $assignment = $this->get_assignment();
        $assignmentname = $assignment->get_course_module()->name;
        $labelconfigured = get_string(
            'assignmentresubmitcount:measure:configuredlabel',
            'report_lp',
            $assignmentname
        );
        return format_text($labelconfigured, FORMAT_PLAIN);
    }

    /**
     * Name of measure.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string('assignmentresubmitcount:measure:name', 'report_lp');
    }

    /**
     * Description of what data/information this measure displays.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_description(): string {
        return get_string('assignmentresubmitcount:measure:description', 'report_lp');
    }

    /**
     * Get assignments already used in this course for this measure.
     *
     * @return array
     * @throws \ReflectionException
     * @throws coding_exception
     */
    protected function get_excluded_assignments() {
        $excludes = [];
        $configurations = item_configuration::get_records_select(
            "id <> :id AND courseid = :courseid AND shortname = :shortname",
            [
                'id' => $this->get_configuration()->get('id'),
                'courseid' => $this->get_configuration()->get('courseid'),
                'shortname' => static::get_short_name()
            ]
        );
        foreach ($configurations as $configuration) {
            $extraconfigurationdata = $configuration->get('extraconfigurationdata');
            if (isset($extraconfigurationdata->id)) {
                $excludes[$extraconfigurationdata->id] = $extraconfigurationdata->id;
            }
        }
        return $excludes;
    }

    /**
     * Get associated instance of assignment class.
     *
     * @return mixed
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function get_assignment() {
        if (is_null($this->assignment)) {
            $this->load_assignment();
        }
        return $this->assignment;
    }

    /**
     * Get available assignments in this course to choose from. Only one assignment
     * per measure.
     *
     * @return array
     * @throws \ReflectionException
     * @throws \dml_exception
     * @throws coding_exception
     */
    protected function get_assignment_options() {
        global $DB;
        $excludes = $this->get_excluded_assignments();
        $params = ['course' => $this->get_configuration()->get('courseid')];
        $select = "course = :course";
        if ($excludes) {
            [$notinsql, $notinparams] = $DB->get_in_or_equal(
                $excludes,
                SQL_PARAMS_NAMED,
                'a',
                false
            );
            $params = array_merge($notinparams, $params);
            $select = "course = :course AND id $notinsql";
        }
        $options = $DB->get_records_select_menu(
            static::COMPONENT_NAME,
            $select,
            $params,
            null,
            'id, name'
        );
        return $options;
    }

    /**
     * Use assignment icon.
     *
     * @return pix_icon|null
     * @throws coding_exception
     */
    public function get_icon() : ? pix_icon {
        return new pix_icon('icon', get_string('pluginname', 'assign'), 'mod_assign');
    }

    /**
     * Loads assignment class instance and sets against property.
     *
     * @throws \dml_exception
     * @throws coding_exception
     */
    protected function load_assignment() {
        global $DB;
        $configuration = $this->get_configuration();
        if (is_null($configuration)) {
            throw new coding_exception('Configuration must loaded');
        }
        $extraconfigurationdata = $configuration->get('extraconfigurationdata');
        if (!isset($extraconfigurationdata->id)) {
            throw new coding_exception('No valid extra configuration data found');
        }
        $instance = $DB->get_record(
            'assign',
            ['id' => $extraconfigurationdata->id],
            '*',
            MUST_EXIST
        );
        $cm = get_coursemodule_from_instance(
            'assign',
            $instance->id,
            $configuration->get('courseid'),
            false,
            MUST_EXIST
        );
        $modulecontext = context_module::instance($cm->id);
        $assignment = new assign($modulecontext, $cm, null);
        $assignment->set_instance($instance);
        $this->assignment = $assignment;
    }


    /**
     * Use assignment course module url.
     *
     * @return moodle_url
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function get_url() : moodle_url {
        return $this->get_assignment()->get_course_module()->url;
    }

    /**
     * Yes we do.
     *
     * @return bool
     */
    public function has_icon() : bool {
        return true;
    }

    /**
     * Yes we do.
     *
     * @return bool
     */
    public function has_url() : bool {
        return true;
    }

    /**
     * Is the assignment module available and enabled.
     *
     * @return bool|null
     */
    public function is_enabled() {
        $pluginmanager = core_plugin_manager::instance();
        $enabled = $pluginmanager->get_enabled_plugins(static::COMPONENT_TYPE);
        if (!is_array($enabled)) {
            return null;
        }
        return isset($enabled[static::COMPONENT_NAME]);
    }

    /**
     * Extend main item mform to allow choice of assignment to measure as
     * implements own configuration.
     *
     * @param MoodleQuickForm $mform
     * @return mixed|void
     * @throws \ReflectionException
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function moodlequickform_extend(MoodleQuickForm &$mform) {
        $assignments = $this->get_assignment_options();
        if (empty($assignments)) {
            $mform->addElement('warning', 'noassignmentswarning',
                null, get_string('noavailablemodules', 'report_lp', static::COMPONENT_NAME));
            $mform->addElement('hidden', 'noassignments');
            $mform->setType('noassignments', PARAM_INT);
            $mform->setDefault('noassignments', 1);
            $mform->disabledIf('submitbutton', 'noassignments', 'eq', 1);
            $mform->removeElement('specific');
        } else {
            $options = [0 => get_string('choose')] +  $assignments;
            $mform->addElement('select', 'assignment',
                get_string('assignmentname', 'mod_assign'), $options);
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
        if ($data['assignment'] == 0) {
            $errors['assignment'] = get_string('pleasechoose', 'report_lp');
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
        if (empty($data['assignment'])) {
            throw new coding_exception('Something went horribly wrong');
        }
        $object = new stdClass();
        $object->id = $data['assignment'];
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
            $defaults['assignment'] = 0;
        } else {
            $defaults['assignment'] = $extraconfigurationdata->id;
        }
        return $defaults;
    }

}
