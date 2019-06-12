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

use report_lp\local\item;

class checklist_complete extends item {

    protected $shortname = 'checklist_complete';

    public function get_default_label(): ?string {
        return null;
    }

    public function get_name(): string {
        return get_string('checklist_complete:measure:name', 'report_lp');
    }

    public function get_short_name(): string {
        return $this->shortname;
    }

    public function get_description(): string {
        return get_string('checklist_complete:measure:description', 'report_lp');
    }
}
