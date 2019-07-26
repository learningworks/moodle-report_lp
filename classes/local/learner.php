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

use report_lp\local\contracts\data_provider;
use report_lp\local\user_list;
use report_lp\output\cell;
use stdClass;
use user_picture;

/**
 * Learner class.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class learner extends item implements data_provider {

    private $renderer;

    private function get_renderer() {
        global $PAGE;
        if (is_null($this->renderer)) {
            $this->renderer = $PAGE->get_renderer('core');
        }
        return $this->renderer;
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
        $user->fullname = fullname($user);
        $profileimage = new user_picture($user);
        $profileimageeurl = $profileimage->get_url($PAGE, $this->get_renderer());
        $user->imageurl = $profileimageeurl->out();
        if (empty($user->imagealt)) {
            $user->imagealt = get_string('pictureof', '', $user->fullname);
        }
        return $user;
    }

    public function get_data_for_users(user_list $userlist) : array {

    }

    public function get_text(stdClass $data) : string {

    }
}
