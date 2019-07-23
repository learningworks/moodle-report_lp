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
use report_lp\local\item_tree;
use report_lp\local\factories\url;
use report_lp\local\item_type_list;
use report_lp\local\persistents\report_configuration;
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

    /** @var report_configuration  */
    private $configuration;

    /** @var  */
    private $itemtree;

    /** @var  */
    private $itemtypelist;

    /**
     * configure_report constructor.
     *
     * @param stdClass $course
     */
    public function __construct(stdClass $course) {
        $this->course = $course;
        $configuration = report_configuration::get_record(['courseid' => $course->id]);
        if (!$configuration) {
            $configuration = new report_configuration();
        }
        $this->configuration = $configuration;
    }

    /**
     * Private method to setup and return item_type_list.
     *
     * @return item_type_list
     * @throws \ReflectionException
     * @throws coding_exception
     */
    private function get_item_type_list() {
        if (is_null($this->itemtypelist)) {
            $this->itemtypelist = new item_type_list();
        }
        return $this->itemtypelist;
    }

    /**
     * Private method to setup and return item_tree.
     *
     * @return item_tree
     * @throws \ReflectionException
     * @throws coding_exception
     */
    private function get_item_tree() {
        if (is_null($this->itemtree)) {
            if (empty($this->course)) {
                throw new coding_exception("Valid course property required");
            }
            $this->itemtree = new item_tree($this->course, $this->get_item_type_list());
        }
        return $this->itemtree;
    }

    /**
     * Initial line item (li) object setup.
     *
     * @param item $item
     * @return stdClass
     * @throws \coding_exception
     */
    protected static function build_line_item(item $item) {
        $lineitem = new stdClass();
        $lineitem->id = $item->get_configuration()->get('id');
        $lineitem->isroot = $item->is_root();
        $lineitem->label = $item->get_label();
        $lineitem->depth = $item->get_depth();
        $lineitem->sortorder = $item->get_sortorder();
        $lineitem->actions = [];
        return $lineitem;
    }

    /**
     * Recursively parse groupings in tree flatting into 1 dimensional array.
     *
     * @param grouping $grouping
     * @return array
     * @throws \ReflectionException
     * @throws \coding_exception
     */
    protected static function process_grouping(grouping $grouping) {
        $lineitem = static::build_line_item($grouping);
        $lineitem->isgrouping = 1;
        if (!$lineitem->isroot) {
            $lineitem->actions = static::get_actions_for_item($grouping);
        }
        $lineitems[] = $lineitem;
        if ($grouping->has_children()) {
            foreach ($grouping->get_children() as $child) {
                // Nested grouping.
                if ($child instanceof grouping) {
                    $childlineitems = static::process_grouping($child);
                    $lineitems = array_merge($lineitems, $childlineitems);
                } else {
                    $lineitem = static::build_line_item($child);
                    $lineitem->isgrouping = 0;
                    $lineitem->actions = static::get_actions_for_item($child);
                    $lineitems[] = $lineitem;
                }
            }
        }
        return $lineitems;
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
        $data->initialised = false;
        // Need to check for a valid course and configuration.
        if ($this->course instanceof stdClass && ($this->configuration->get('id') > 0)) {
            $data->initialised = true;
            $data->enabled = $this->configuration->get('enabled');
            $data->courseid = $this->course->id;
            $data->coursestartdate = $this->course->startdate;
            $data->itemtypemenu = $this->build_item_type_menu();
            $lineitems = [];
            // Flatten the tree of items.
            foreach ($this->get_item_tree() as $item) {
                if ($item instanceof grouping) {
                    $groupinglineitems = static::process_grouping($item);
                    $lineitems = array_merge($lineitems, $groupinglineitems);
                }
            }
            $data->lineitems = $lineitems;
        } else {
            $data->initialisebutton = \report_lp\local\factories\button::create_initialise_button($this->course->id);
        }
        return $data;
    }

    /**
     * Builds data for item type menu.
     *
     * @return stdClass
     * @throws \ReflectionException
     * @throws \coding_exception
     */
    public function build_item_type_menu() {
        $data = new stdClass();
        $grouping = new grouping();
        $data->groupingname = $grouping->get_name();
        $data->groupingtitle = get_string('addgrouping', 'report_lp');
        $data->groupingdescription = $grouping->get_description();
        $creategroupingurl = url::get_create_item_url($this->course, grouping::get_short_name());
        $data->creategroupingurl = $creategroupingurl->out(false);
        $data->measuresmenu = [];
        foreach ($this->get_item_type_list()->get_measures() as $measure) {
            $item = new stdClass();
            $item->measurename = $measure->get_name();
            $item->measuretitle = get_string('addmeasure', 'report_lp', $measure->get_name());
            $item->measuredescription = $measure->get_description();
            $createmeasureurl = url::get_create_item_url($this->course, $measure::get_short_name());
            $item->createmeasureurl =  $createmeasureurl->out(false);
            $data->measuresmenu[] = $item;
        }
        return $data;
    }

    /**
     * Available button actions.
     *
     * @param item $item
     * @return stdClass
     * @throws \ReflectionException
     * @throws \coding_exception
     */
    public static function get_actions_for_item(item $item) {
        $buttons = new stdClass();
        $buttons->configure = static::get_configure_button($item);
        $buttons->delete = static::get_delete_button($item);
        return $buttons;
    }

    /**
     * Get all the bits to build a configure button. Currently dependant on FontAwesome being loaded
     * for icons.
     *
     * @param item $item
     * @return stdClass
     * @throws \ReflectionException
     * @throws \coding_exception
     */
    protected static function get_configure_button(item $item) {
        $button = new stdClass();
        $button->name = 'configure';
        $button->title = get_string('configureitem', 'report_lp');
        $button->icon = '<i class="fa fa-cog fa-fw"></i>';
        $url = url::get_item_url(null, $item->get_configuration()->get('id'));
        $button->url = $url->out(false);
        return $button;
    }

    /**
     * Get all the bits to build a delete button. Currently dependant on FontAwesome being loaded
     * for icons.
     *
     * @param item $item
     * @return stdClass
     * @throws \coding_exception
     */
    protected static function get_delete_button(item $item) {
        $button = new stdClass();
        $button->name = 'delete';
        $button->title = get_string('deleteitem', 'report_lp');
        $button->icon = '<i class="fa fa-trash-o fa-fw"></i>';
        $url = url::get_item_action_url(
            $item->get_configuration()->get('courseid'),
            $item->get_configuration()->get('id'),
            'delete'
        );
        $button->url = $url->out(false);
        return $button;
    }

    /**
     * Get all the bits to build a move up button. Currently dependant on FontAwesome being loaded
     * for icons.
     *
     * @param item $item
     * @return stdClass
     * @throws \coding_exception
     */
    protected static function get_move_up_button(item $item) {
        $button = new stdClass();
        $button->name = 'moveup';
        $button->title = get_string('moveup', 'report_lp');
        $button->icon = '<i class="fa fa-arrow-up fa-fw"></i>';
        $url = url::get_item_action_url(
            $item->get_configuration()->get('courseid'),
            $item->get_configuration()->get('id'),
            'moveup'
        );
        $button->url = $url->out(false);
        return $button;
    }

    /**
     * Get all the bits to build a move down button. Currently dependant on FontAwesome being loaded
     * for icons.
     *
     * @param item $item
     * @return stdClass
     * @throws \coding_exception
     */
    protected static function get_move_down_button(item $item) {
        $button = new stdClass();
        $button->name = 'movedown';
        $button->title = get_string('movedown', 'report_lp');
        $button->icon = '<i class="fa fa-arrow-down fa-fw"></i>';
        $url = url::get_item_action_url(
            $item->get_configuration()->get('courseid'),
            $item->get_configuration()->get('id'),
            'movedown'
        );
        $button->url = $url->out(false);
        return $button;
    }

}
