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

    /** @var array $enrolmentstatuses Cache for users enrolments. */
    private $enrolmentstatuses;

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

    public function build_header_cell(int $depth = null) {
        $cell = new cell();
        $cell->header = true;
        $cell->colspan = 2;
        return $cell;
    }

    /**
     * Get enrolment record for user. Caches records.
     *
     * @param int $userid
     * @return mixed
     * @throws \dml_exception
     * @throws coding_exception
     */
    private function get_enrolment_status(int $userid) {
        global $DB;
        if (is_null($this->enrolmentstatuses)) {
            $sql = "SELECT ua.*
                      FROM {user_enrolments} ua
                      JOIN {mdl_enrol} e ON e.id = ua.enrolid
                     WHERE e.courseid = :courseid";
            $this->enrolmentstatuses = $DB->get_records_sql($sql, ['courseid' => $this->get_courseid()]);
        }
        if (isset($this->enrolmentstatuses[$userid])) {
            throw new coding_exception('User is not enrolled in this course');
        }
        return $this->enrolmentstatuses[$userid];
    }

    public function get_description(): string {
        return get_string('learner:description', 'report_lp');
    }

    public function get_default_label() : string {
        return get_string('learner:label', 'report_lp');
    }

    public function get_name(): string {
        return get_string('learner:name', 'report_lp');
    }

    public function is_locked() {
        return true;
    }

    public function get_cell(stdClass $data) : cell {
    }

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
            $ue = $this->get_enrolment_status($user->id);
            $user->enrolmentstatus = $ue->status;
        }
        return $user;
    }

    public function get_data_for_users(user_list $userlist) : array {

    }

    public function get_text(stdClass $data) : string {

    }
}
