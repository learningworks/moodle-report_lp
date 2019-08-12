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

namespace report_lp\local\fields;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use report_lp\local\learner_field;
use report_lp\local\user_list;
use report_lp\output\cell;
use stdClass;

/**
 * Email address field.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class email_learner_field extends learner_field {

    /**
     * Build table cell.
     *
     * @param $user
     * @return cell
     */
    public function build_data_cell($user) {
        $cell = new cell();
        $cell->class = "cell";
        $cell->plaintextcontent = $user->email;
        return $cell;
    }

    /**
     * Description of field displayed in configuration UI.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_description(): string {
        return get_string('email:learnerfield:description', 'report_lp');
    }

    /**
     * Default label displayed in reports.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_default_label() : string {
        return get_string('email:learnerfield:label', 'report_lp');
    }

    /**
     * Build data for user, ensure email field present in user record.
     *
     * @param stdClass $user
     * @return stdClass
     * @throws \dml_exception
     */
    public function get_data_for_user(stdClass $user) : stdClass {
        global $DB;
        if (!property_exists($user, 'email')) {
            $user->email = $DB->get_field('user', 'email', ['id' => $user->id]);
        }
        return $user;
    }

    /**
     * Build data for list of users.
     *
     * @param user_list $userlist
     * @return array
     * @throws \dml_exception
     */
    public function get_data_for_users(user_list $userlist) : array {
        $data = [];
        foreach ($userlist as $user) {
            $data[$user->id] = $this->get_data_for_user($user);
        }
        return $data;
    }

    /**
     * Simple name of field displayed in configuration UI.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string('email:learnerfield:name', 'report_lp');
    }

}

