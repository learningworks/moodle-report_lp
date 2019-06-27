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

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;
use report_lp\local\item_tree;
use stdClass;

class renderer extends plugin_renderer_base {

    public function render_jumbotron($heading, $paragraphtop, $paragraphbottom = null, $button = null) {
        $data = new stdClass();
        $data->heading = $heading;
        $data->paragraphtop = $paragraphtop;
        $data->diaplayhr = 0;
        if (!is_null($paragraphbottom) || !is_null($button)) {
            $data->diaplayhr = 1;
        }
        $data->paragraphbottom = $paragraphbottom;
        $data->button = $button;
        return parent::render_from_template('report_lp/jumbotron', $data);
    }

    public function item_tree_configuration(item_tree $itemtree) {
        $data = $itemtree->export_for_template($this);
        return parent::render_from_template('report_lp/report_configuration', $data);
    }

    public function no_items_configured(no_items_configured $thing) {
        $data = $thing->export_for_template($this);
        return parent::render_from_template('report_lp/no_items_configured', $data);
    }
}
