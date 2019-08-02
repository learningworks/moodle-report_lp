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
use html_writer;
use MoodleQuickForm;
use pix_icon;
use moodle_url;
use report_lp\local\contracts\extra_configuration;
use report_lp\local\measure;
use report_lp\local\persistents\item_configuration;
use report_lp\local\user_list;
use report_lp\output\cell;
use stdClass;
use core_plugin_manager;

/**
 * Sessions attended out of total sessions.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attendance_sessions_summary extends measure implements extra_configuration {

    /** @var string COMPONENT_TYPE Used to identify core or plugin type. Moodle frankenstyle. */
    public const COMPONENT_TYPE = 'mod';

    /** @var string COMPONENT_NAME Used to for name of core subsystem or plugin. Moodle frankenstyle. */
    public const COMPONENT_NAME = 'attendance';

    /** @var stdClass $cm Course Module instance. */
    private $cm;

    /** @var array $groupsessioncounts Group session counts cache. */
    private $groupsessioncounts;

    /** @var array $sessionsattended Sessions attended cache. */
    private $sessionsattended;

    /**
     * @todo Remove this functionality.
     *
     * @param $data
     * @param string $format
     * @return string
     */
    public function format_user_measure_data($data, $format = FORMAT_PLAIN) : string {
        return '';
    }

    /**
     * Build cell to be used on HTML table and Excel sheet.
     *
     * @param $user
     * @return cell
     */
    public function build_data_cell($user) {
        $text = ' - ';
        if (!empty($user->data->usersessionsattended)) {
            $text = implode($user->data->usersessionsattended, " ");
        }
        $cell = new cell();
        $cell->plaintextcontent = $text;
        $cell->htmlcontent = html_writer::span(html_writer::link($user->data->reporturl, $text), "measure");
        return $cell;
    }

    /**
     * Get all data for specify user.
     *
     * @param stdClass $user
     * @return stdClass
     * @throws \moodle_exception
     */
    public function get_data_for_user(stdClass $user) : stdClass {
        $this->load_instance_data();
        $data = new stdClass();
        $groupsessioncounts = $this->groupsessioncounts;
        if (!isset($this->sessionsattended[$user->id])) {
            $data->usersessionsattended = null;
        } else {
            $usersessionsattended = $this->sessionsattended[$user->id];
            array_walk($usersessionsattended,
                function(&$value, &$key) use($groupsessioncounts) {
                    $value = '(' . $value . '/' . $groupsessioncounts[$key] . ')';
                }
            );
            $data->usersessionsattended = $usersessionsattended;
        }
        $reporturl = new moodle_url('/mod/attendance/view.php', ['id' => $this->cm->id, 'studentid' => $user->id]);
        $data->reporturl = $reporturl;
        $user->data = $data;
        return $user;
    }

    /**
     * Load all caches if they haven't already been.
     *
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function load_instance_data() {
        global $DB;
        if (is_null($this->cm)) {
            $this->cm = get_coursemodule_from_instance('attendance', $this->get_extraconfigurationdata()->id);
        }
        if (is_null($this->groupsessioncounts)) {
            $sql = "SELECT groupid, COUNT(1) AS count
                      FROM {attendance_sessions}
                     WHERE attendanceid = :attendanceid
                  GROUP BY groupid";
            $parameters = ['attendanceid' => $this->get_extraconfigurationdata()->id];
            $rs = $DB->get_recordset_sql($sql, $parameters);
            foreach ($rs as $record) {
                $this->groupsessioncounts[$record->groupid] = $record->count;
            }
            $rs->close();
        }
        if (is_null($this->sessionsattended)) {
            $acronyms = explode(',', $this->get_extraconfigurationdata()->statuseacronyms);
            [$insql, $inparameters] = $DB->get_in_or_equal($acronyms, SQL_PARAMS_NAMED);
            $sql = "SELECT log.studentid, sessions.groupid, COUNT(log.id) AS sessionsattended
                      FROM {attendance_log} log
                      JOIN {attendance_sessions} sessions ON sessions.id = log.sessionid
                      JOIN {attendance_statuses} statuses ON statuses.id = log.statusid
                     WHERE sessions.attendanceid = :attendanceid AND statuses.acronym $insql
                  GROUP BY sessions.groupid, log.studentid";
            $parameters = ['attendanceid' => $this->get_extraconfigurationdata()->id];
            $parameters = array_merge($parameters, $inparameters);
            $rs = $DB->get_recordset_sql($sql, $parameters);
            foreach ($rs as $record) {
                if (!isset($this->sessionsattended[$record->studentid])) {
                    $this->sessionsattended[$record->studentid] = [];
                }
                $this->sessionsattended[$record->studentid][$record->groupid] = $record->sessionsattended;
            }
            $rs->close();
        }

    }

    /**
     * Does what it does.
     *
     * @param user_list $userlist
     * @return array
     * @throws \moodle_exception
     */
    public function get_data_for_users(user_list $userlist) : array {
        $data = [];
        foreach ($userlist as $user) {
            $data[$user->id] = $this->get_data_for_user($user->id);
        }
        return $data;
    }

    /**
     * Nothing fancy here just a language string.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_default_label() :string {
        return format_text(get_string('defaultlabelattendancesessionssummary', 'report_lp'), FORMAT_PLAIN);
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
                'id' => $this->get_id(),
                'courseid' => $this->get_courseid(),
                'shortname' => static::get_short_name()
            ]
        );
        foreach ($configurations as $configuration) {
            $extraconfigurationdata = $configuration->get_extraconfigurationdata();
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
     * Use attendance icon.
     *
     * @return pix_icon|null
     * @throws coding_exception
     */
    public function get_icon() : ? pix_icon {
        return new pix_icon('icon', get_string('pluginname', 'attendance'), 'mod_attendance');
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
     * Is the attendance module available and enabled.
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
            $mform->addElement('text', 'statuseacronyms',
                get_string('statuseacronyms', 'report_lp'));
            $mform->setType('statuseacronyms', PARAM_TAGLIST);
            $mform->addHelpButton('statuseacronyms', 'statuseacronyms', 'report_lp');
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
        if (empty($data['statuseacronyms'])) {
            $errors['statuseacronyms'] = get_string('nostatuseacronyms', 'report_lp');
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
        $object->statuseacronyms = $data['statuseacronyms'];
        return $object;
    }

    /**
     * Get defaults based on extra configuration data.
     *
     * @return array
     */
    public function moodlequickform_get_extra_configuration_defaults() : array {
        $extraconfigurationdata = $this->get_extraconfigurationdata();
        $defaults = [];
        if (empty($extraconfigurationdata)) {
            $defaults['attendance'] = 0;
        } else {
            $defaults['attendance'] = $extraconfigurationdata->id;
            $defaults['statuseacronyms'] = $extraconfigurationdata->statuseacronyms;
        }
        return $defaults;
    }

}
