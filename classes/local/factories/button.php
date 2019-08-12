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

namespace report_lp\local\factories;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;
use stdClass;
use report_lp\local\item;

/**
 * Factory for buttons.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class button {

    public static function create_initialise_button(int $courseid) {
        return html_writer::link(
            url::get_initialise_url($courseid),
            get_string('initialise', 'report_lp'),
            ['class' => 'btn btn-primary btn-lg', 'role' => 'button']
        );
    }

    public static function create_remove_button(int $courseid) {
        return html_writer::link(
            url::get_remove_url($courseid),
            get_string('removereport', 'report_lp'),
            ['class' => 'btn btn-outline-danger', 'role' => 'button']
        );
    }

}
