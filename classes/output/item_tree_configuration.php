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
use stdClass;
use templatable;

class item_tree_configuration implements renderable, templatable {

    protected $itemtree;

    public function __construct(item_tree $itemtree) {
        $this->itemtree = $itemtree;
    }

    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $data->items = [];
        if (count($this->itemtree) > 0) {
            foreach ($this->itemtree as $item) {
                $component = new stdClass();
                $component->isgrouping = 0;
                $component->label = $item->get_label();
                $component->editaction = $this->get_edit_action($item);
                if ($item instanceof grouping) {
                    $component->haschildren = 0;
                    if ($item->has_children()) {
                        $component->haschildren = 1;
                        $children = [];
                        foreach ($item as $childitem) {
                            $childcomponent = new stdClass();
                            $childcomponent->label = $childitem->get_label();
                            $children[] = $childcomponent;
                        }
                        $component->isgrouping = 1;
                        $component->grouping = $children;
                    }
                }
                $data->items[] = $component;
            }
        }
        return $data;
    }

    protected function get_edit_action(item $item) {
        global $OUTPUT;
        $action = new stdClass();
        $id = $item->get_configuration()->get('id');
        $action->url = new moodle_url('/');
        $pixicon = new pix_icon('icons/sliders-h', '', 'report_lp');
        $action->icon = $OUTPUT->render($pixicon);
        return $action;

    }

    protected function get_delete_action(item $item) {
        global $OUTPUT;
        $action = new stdClass();
        $id = $item->get_configuration()->get('id');
        $action->url = url::get_delete_item_url($id);
        $pixicon = new pix_icon('icons/trash-alt', '', 'report_lp');
        $action->icon = $OUTPUT->render($pixicon);
        return $action;
    }
}
