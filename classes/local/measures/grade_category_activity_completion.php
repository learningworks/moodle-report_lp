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

/**
 *
 * @package
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_lp\local\measures;

defined('MOODLE_INTERNAL') || die();

use moodleform;
use report_lp\local\contracts\has_own_configuration;
use report_lp\local\measure;
use report_lp\local\userlist;

class grade_category_activity_completion extends measure implements has_own_configuration {

    protected $shortname = 'grade_category_activity_completion';

    public function get_data_for_users(userlist $userlist) : ? array {
        return [];
    }

    public function get_default_label(): ? string {
        return null;
    }

    public function get_name(): string {
        return get_string('grade_category_activity_completion:measure:name', 'report_lp');
    }

    public function get_description(): string {
        return get_string('grade_category_activity_completion:measure:description', 'report_lp');
    }

    public function extend_mform(moodleform $mform) : moodleform {

    }

    public function process_mform_data(moodleform $mform) : string {

    }
}