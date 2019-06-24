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

use coding_exception;
use MoodleQuickForm;
use report_lp\local\contracts\has_own_configuration;
use report_lp\local\measure;
use report_lp\local\persistents\item_configuration;
use report_lp\local\userlist;
use stdClass;

/**
 * Sessions attended out of total sessions.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attendance_sessions_summary extends measure implements has_own_configuration {

    /** @var string COMPONENT_TYPE Used to identify core or plugin type. Moodle frankenstyle. */
    public const COMPONENT_TYPE = 'mod';

    /** @var string COMPONENT_NAME Used to for name of core subsystem or plugin. Moodle frankenstyle. */
    public const COMPONENT_NAME = 'attendance';

    /**
     * @param userlist $userlist
     * @return array|null
     */
    public function get_data_for_users(userlist $userlist) : ? array {
        return [];
    }

    /**
     * Nothing fancy here just a language string.
     *
     * @param string $format
     * @return string
     * @throws coding_exception
     */
    public function get_default_label($format = FORMAT_PLAIN): string {
        return format_text(get_string('defaultlabelattendancesessionssummary', 'report_lp'), $format);
    }

    /**
     * Name of measure.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string('attendancesessionssummary:measure:name', 'report_lp');
    }

    /**
     * Description of what data/information this measure displays.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_description(): string {
        return get_string('attendancesessionssummary:measure:description', 'report_lp');
    }

    /**
     * Get attendances already used in this course for this measure.
     *
     * @return array
     * @throws \ReflectionException
     * @throws coding_exception
     */
    protected function get_excluded_attendances() {
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
     * Get available attendances in this course to choose from. Only one attendance
     * per measure.
     *
     * @return array
     * @throws \ReflectionException
     * @throws \dml_exception
     * @throws coding_exception
     */
    protected function get_attendance_options() {
        global $DB;
        $excludes = $this->get_excluded_attendances();
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
     * Extend main item mform to allow choice of attendance to measure as
     * implements own configuration.
     *
     * @param MoodleQuickForm $mform
     * @return mixed|void
     * @throws \ReflectionException
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function moodlequickform_extend(MoodleQuickForm &$mform) {
        $attendances = $this->get_attendance_options();
        if (empty($attendances)) {
            $mform->addElement('warning', 'noattendanceswarning',
                null, get_string('noavailablemodules', 'report_lp', static::COMPONENT_NAME));
            $mform->addElement('hidden', 'noattendances');
            $mform->setType('noattendances', PARAM_INT);
            $mform->setDefault('noattendances', 1);
            $mform->disabledIf('submitbutton', 'noattendances', 'eq', 1);
            $mform->removeElement('specific');
        } else {
            $options = [0 => get_string('choose')] +  $attendances;
            $mform->addElement('select', 'attendance',
                get_string('attendancename', 'report_lp'), $options);
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
        if ($data['attendance'] == 0) {
            $errors['attendance'] = get_string('pleasechoose', 'report_lp');
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
        if (empty($data['attendance'])) {
            throw new coding_exception('Something went horribly wrong');
        }
        $object = new stdClass();
        $object->id = $data['attendance'];
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
            $defaults['attendance'] = 0;
        } else {
            $defaults['attendance'] = $extraconfigurationdata->id;
        }
        return $defaults;
    }

}
