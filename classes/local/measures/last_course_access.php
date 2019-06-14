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

use report_lp\local\measure;

/**
 * The date and time learner last accessed a course instance.
 *
 * @package
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class last_course_access extends measure {

    protected $shortname = 'last_course_access';

    public function get_default_label(): ?string {
        return null;
    }

    public function get_name(): string {
        return get_string('last_course_access:measure:name', 'report_lp');
    }

    public function get_description(): string {
        return get_string('last_course_access:measure:description', 'report_lp');
    }
}
