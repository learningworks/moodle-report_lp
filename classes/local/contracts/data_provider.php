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

namespace report_lp\local\contracts;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use report_lp\output\cell;
use report_lp\local\user_list;

/**
 * Interface for items that provide data for report.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface data_provider {

    /**
     * Method to allow item to get required data that will be used for
     * a user that will be used for future calls to get_cell and get_text
     * methods.
     *
     * @param stdClass $user
     * @return stdClass
     */
    public function get_data_for_user(stdClass $user) : stdClass;

    /**
     * Method to process a list of users, allowing item to get data for
     * users that will be used for future calls to get_cell and get_text
     * methods.
     *
     * @param user_list $userlist
     * @return array
     */
    public function get_data_for_users(user_list $userlist) : array;

}
