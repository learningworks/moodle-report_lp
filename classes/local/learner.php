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

namespace report_lp\local;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;
use report_lp\local\contracts\data_provider;
use report_lp\local\user_list;
use report_lp\output\cell;
use stdClass;
use user_picture;
use moodle_url;
use coding_exception;
use core_user;

/**
 * Learner class.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class learner extends item implements data_provider {

    /** @var array $allnames Required user field name columns. */
    private $allnames;

    /** @var array $userenrolments Cache for all user enrolments in associated course. */
    private $userenrolments;

    /**
     * Build cell based off user data.
     *
     * @param $user
     * @return cell
     */
    public function build_data_cell($user) {
        $contents = new stdClass();
        $contents->userid = $user->id;
        $contents->profilelinkurl = $user->profilelinkurl;
        $contents->profileimageurl = $user->profileimageurl;
        $contents->profileimagealt = $user->profileimagealt;
        $contents->fullname = $user->fullname;
        $contents->enrolmentstatus = $user->enrolmentstatus;
        $cell = new cell();
        $plaintext = $user->fullname;
        if ($user->enrolmentstatus) {
            $plaintext .= ' [w]';
        }
        $cell->plaintextcontent = $plaintext;
        $cell->class = "cell cell-lg";
        $cell->templatablecontent = $contents;
        $cell->template = 'learner_cell_contents';
        return $cell;
    }

    /**
     * Empty cell.
     *
     * @param int|null $depth
     * @return mixed|cell
     */
    public function build_header_cell(int $depth = null) {
        $cell = new cell();
        $cell->header = true;
        return $cell;
    }

    /**
     * All name fields required for building user object.
     *
     * @return array|string
     */
    private function get_all_names() {
        if (is_null($this->allnames)) {
            $this->allnames = get_all_user_name_fields();
        }
        return $this->allnames;
    }

    public function get_description(): string {
        return get_string('learner:description', 'report_lp');
    }

    public function get_default_label() : string {
        return get_string('learner:label', 'report_lp');
    }

    /**
     * Get enrolment record for user. Build a cache of all user enrolments in a
     * course.
     *
     * @param int $userid
     * @return mixed
     * @throws \dml_exception
     * @throws coding_exception
     */
    private function get_enrolment_status(int $userid) {
        if (is_null($this->userenrolments)) {
            $this->load_user_enrolments_cache();
        }
        if (!isset($this->userenrolments[$userid])) {
            throw new coding_exception('User is not enrolled in this course');
        }
        return $this->userenrolments[$userid]->status;
    }

    /**
     * Name of item.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string('learner:name', 'report_lp');
    }

    /**
     * Get add required data and cast on user object.
     *
     * @param stdClass $user
     * @return stdClass
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws coding_exception
     */
    public function get_data_for_user(stdClass $user) : stdClass {
        global $PAGE;
        if (!isset($user->id)) {
            throw new coding_exception('Invalid user record');
        }
        foreach ($this->get_all_names() as $allname) {
            if (!property_exists($user, $allname)) {
                $user = core_user::get_user($user->id);
                break;
            }
        }
        $user->fullname = fullname($user);
        $profileimage = new user_picture($user);
        $profileimageurl = $profileimage->get_url($PAGE, $this->get_renderer());
        $user->profileimageurl = $profileimageurl->out();
        if (empty($user->imagealt)) {
            $user->profileimagealt = get_string('pictureof', '', $user->fullname);
        } else {
            $user->profileimagealt = $user->imagealt;
        }
        $profilelinkurl = new moodle_url('/user/view.php', ['id' => $user->id, 'course' => $this->get_courseid()]);
        $user->profilelinkurl = $profilelinkurl->out();
        if (!isset($user->enrolmentstatus)) {
            $user->enrolmentstatus = $this->get_enrolment_status($user->id);
        }
        return $user;
    }

    public function get_data_for_users(user_list $userlist) : array {
    }

    /**
     * Lock this item so cannot be deleted in UI.
     */
    public function is_locked() {
        return true;
    }

    /**
     * Builds a cache of all user enrolments in a course.
     *
     * @throws \dml_exception
     */
    private function load_user_enrolments_cache() {
        global $DB;
        // Fetch user enrolments for course and key up in user id.
        $sql = "SELECT ua.*
                  FROM {user_enrolments} ua
                  JOIN {enrol} e ON e.id = ua.enrolid
                 WHERE e.courseid = :courseid";
        $rs = $DB->get_recordset_sql($sql, ['courseid' => $this->get_courseid()]);
        foreach ($rs as $record) {
            $this->userenrolments[$record->userid] = $record;
        }
        $rs->close();
    }

}
