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

use moodleform;
use report_lp\local\contracts\has_own_configuration;
use report_lp\local\item;

/**
 * Assignment resubmit count of a learner for an assignment instance.
 *
 * @package
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class assignment_resubmit_count extends item implements has_own_configuration {

    protected $shortname = 'assignment_resubmit_count';

    public function get_name(): string {
        return get_string('assignment_resubmit_count:measure:name', 'report_lp');
    }

    public function get_short_name(): string {
        return $this->shortname;
    }

    public function get_description(): string {
        return get_string('assignment_resubmit_count:measure:description', 'report_lp');
    }

    public function extend_mform(moodleform $mform) : moodleform {
    }

    public function process_mform_data(moodleform $mform) : string {
    }

}
