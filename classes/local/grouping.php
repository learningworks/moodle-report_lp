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

class grouping extends item {

    protected $shortname = 'grouping';

    public function get_name(): string {
        return get_string('grouping:measure:name', 'report_lp');
    }

    public function get_short_name(): string {
        return $this->shortname;
    }

    public function get_description(): string {
        return get_string('grouping:measure:description', 'report_lp');
    }
}