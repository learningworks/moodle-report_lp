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

use report_lp\output\cell;

defined('MOODLE_INTERNAL') || die();

/**
 * Learner information grouping.
 *
 * Tree structure for learner field items.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class learner_information_grouping extends grouping {

    public function build_header_cell(int $depth = null) {
        $cell = parent::build_header_cell($depth);
        $cell->colspan = $this->count();
        return $cell;
    }

    public function get_default_label() : string {
        return get_string('learnerinformationgrouping:label', 'report_lp');
    }

    public function get_description() : string {
        return get_string('learnerinformationgrouping:name', 'report_lp');
    }

    public function get_name() : string {
        return get_string('learnerinformationgrouping:description', 'report_lp');
    }

    public function is_locked() {
        return true;
    }
}
