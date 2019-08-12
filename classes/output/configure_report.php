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

use coding_exception;
use moodle_url;
use pix_icon;
use renderable;
use renderer_base;
use report_lp\local\grouping;
use report_lp\local\item;
use report_lp\local\builders\item_tree;
use report_lp\local\factories\url;
use report_lp\local\factories\button;
use report_lp\local\item_type_list;
use report_lp\local\persistents\item_configuration;
use report_lp\local\visitors\pre_order_visitor;
use stdClass;
use templatable;

/**
 * Main report configuration output exporter class.
 *
 * @todo Move buttons to a factory class.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class configure_report implements renderable, templatable {

    /** @var mixed  */
    private $course;

    /** @var  */
    private $itemtypelist;

    /**
     * configure_report constructor.
     *
     * @param stdClass $course
     * @param item_type_list $itemtypelist
     */
    public function __construct(stdClass $course, item_type_list $itemtypelist) {
        $this->course = $course;
        $this->itemtypelist = $itemtypelist;
    }

    /**
     * Iterates ordered item list making structure a mustache template can understand.
     *
     * @param renderer_base $output
     * @return array|stdClass
     * @throws \ReflectionException
     * @throws \coding_exception
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $data->courseid = $this->course->id;
        $data->coursestartdate = $this->course->startdate;
        $data->listitems = [];
        $tree = new item_tree($this->course, $this->itemtypelist);
        $itemconfigurations = item_configuration::get_records(['courseid' => $this->course->id]);
        $root = $tree->build_from_item_configurations($itemconfigurations);
        if ($root === null) {
            $data->initialisebutton = button::create_initialise_button($this->course->id);
            return $data;
        }
        $data->removebutton = button::create_remove_button($this->course->id);
        $visitor = new pre_order_visitor();
        $items = $root->accept($visitor);
        $data->initialised = true;
        foreach ($items as $item) {
            /** @var item $item */
            if ($item->is_root()) {
                $lineitem           = new stdClass();
                $lineitem->label    = $item->get_label();
                $data->root         = $lineitem;
                continue;
            }
            $lineitem               = new stdClass();
            $lineitem->id           = $item->get_id();
            $lineitem->shortname    = $item::get_short_name();
            $lineitem->label        = $item->get_label();
            $lineitem->depth        = $item->get_depth();
            $lineitem->sortorder    = $item->get_sortorder();
            $actions = [];
            $button = new stdClass();
            $button->name = 'configure';
            $button->title = get_string('configureitem', 'report_lp');
            $button->icon = '<i class="fa fa-cog fa-fw"></i>';
            $url = url::get_item_url(null, $item->get_id());
            $button->url = $url->out(false);
            $actions[] = $button;
            if (!$item->is_locked()) {
                $button = new stdClass();
                $button->name = 'delete';
                $button->title = get_string('deleteitem', 'report_lp');
                $button->icon = '<i class="fa fa-trash-o fa-fw"></i>';
                $url = url::get_item_action_url(
                    $item->get_courseid(),
                    $item->get_id(),
                    'delete'
                );
                $button->url = $url->out(false);
                $actions[] = $button;
            }
            $lineitem->actions = $actions;
            $data->lineitems[] = $lineitem;
        }
        $menu = [];
        foreach ($this->itemtypelist as $itemtype) {
            $menuitem = new stdClass();
            $menuitem->measurename = $itemtype->get_name();
            $menuitem->measuretitle = get_string('addmeasure', 'report_lp', $itemtype->get_name());
            $menuitem->measuredescription = $itemtype->get_description();
            $createmeasureurl = url::get_create_item_url($this->course, $itemtype::get_short_name());
            $menuitem->createmeasureurl =  $createmeasureurl->out(false);
            $menu[] = $menuitem;
        }
        $itemtypemenu = new stdClass();
        $itemtypemenu->menu = $menu;
        $data->itemtypemenu = $itemtypemenu;
        return $data;
    }

}
