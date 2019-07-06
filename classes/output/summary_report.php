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

use moodle_url;
use pix_icon;
use renderable;
use renderer_base;
use report_lp\local\grouping;
use report_lp\local\item;
use report_lp\local\item_tree;
use report_lp\local\factories\url;
use report_lp\local\summary;
use stdClass;
use templatable;

/**
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class summary_report implements renderable, templatable {

    protected $summary;

    protected $learnercolumns = ['fullname', 'actions', 'idnumber', 'coursegroups'];

    public function __construct(summary $summary) {
        $this->summary = $summary;
    }

    /**
     *
     * @param renderer_base $output
     * @return array|stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $data->header = $this->table_header_row_at_level(2);
        return $data;
    }

    public function table_header_row_at_level($depth = 2) {
        $header = [];
        $th = new stdClass();
        $th->label = get_string('learner', 'report_lp');
        $th->colspan = count($this->learnercolumns);
        $header[] = $th;
        $itemtree = $this->summary->get_item_tree();
        $items = $itemtree->get_flattened_tree();
        foreach ($items as $item) {
            $configuration = $item->get_configuration();
            if ($configuration->get('depth') != $depth) {
                continue;
            }
            $th = new stdClass();
            $th->label = $item->get_label();
            $th->colspan = 1;
            if ($item instanceof grouping) {
                $th->colspan = $item->count();
            }
            $header[] = $th;
        }
        return $header;
    }
}