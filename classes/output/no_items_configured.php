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

namespace report_lp\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Basic notice.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class no_items_configured implements renderable, templatable {

    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $data->title = get_string('noitemsconfigured', 'report_lp');
        $data->description = get_string('noitemsconfigured:description', 'report_lp');
        return $data;
    }
}
