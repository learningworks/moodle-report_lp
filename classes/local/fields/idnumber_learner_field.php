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

use report_lp\local\learner_field;
use report_lp\local\user_list;
use report_lp\output\cell;
use stdClass;

class idnumber_learner_field extends learner_field {

    public function build_data_cell($user) {
        $cell = new cell();
        $cell->class = "cell";
        $cell->plaintextcontent = $user->idnumber;
        return $cell;
    }

    public function get_description(): string {
        return get_string('idnumber:learnerfield:description', 'report_lp');
    }

    public function get_default_label() : string {
        return get_string('idnumber:learnerfield:label', 'report_lp');
    }

    public function get_name(): string {
        return get_string('idnumber:learnerfield:name', 'report_lp');
    }

    public function get_data_for_user(stdClass $user) : stdClass {
        global $DB;
        if (!property_exists($user, 'idnumber')) {
            $user->idnumber = $DB->get_field('user', 'idnumber', ['id' => $user->id]);
        }
        return $user;
    }

    public function get_data_for_users(user_list $userlist) : array {
        $data = [];
        foreach ($userlist as $user) {
            $data[$user->id] = $this->get_data_for_user($user);
        }
        return $data;
    }

}
