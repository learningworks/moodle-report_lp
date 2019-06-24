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

/**
 * Factory for creating moodle urls the plugin requires.
 *
 * @todo Move buttons to a factory class.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_tree_configuration implements renderable, templatable {

    protected $itemtree;

    public function __construct(item_tree $itemtree) {
        $this->itemtree = $itemtree;
    }

    /**
     * Get stdClass loaded with default values.
     *
     * @return stdClass
     */
    protected static function get_base_item() {
        $item = new stdClass();
        $item->isfirst = 0;
        $item->islast = 0;
        $item->isgrouping = 0;
        $item->haschildren = 0;
        return $item;
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
        $data->items = [];
        if (count($this->itemtree) > 0) {
            foreach ($this->itemtree as $item) {
                $component = static::get_base_item();
                $component->label = $item->get_label();
                $component->actions = self::get_item_buttons($item);
                if ($item instanceof grouping) {
                    $component->isgrouping = 1;
                    if ($item->has_children()) {
                        $component->haschildren = 1;
                        $children = [];
                        foreach ($item as $childitem) {
                            $childcomponent = static::get_base_item();
                            $childcomponent->label = $childitem->get_label();
                            $childcomponent->actions = self::get_item_buttons($childitem);
                            $children[] = $childcomponent;
                        }
                        $first = array_key_first($children);
                        if (!is_null($first)) {
                            $children[$first]->isfirst = 1;
                        }
                        $last = array_key_last($children);
                        if (!is_null($last)) {
                            $children[$last]->islast = 1;
                        }
                        $component->grouping = $children;
                    }
                }
                $data->items[] = $component;
            }
            $first = array_key_first($data->items);
            if (!is_null($first)) {
                $data->items[$first]->isfirst = 1;
            }
            $last = array_key_last($data->items);
            if (!is_null($last)) {
                $data->items[$last]->islast = 1;
            }
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
    protected function get_item_buttons(item $item) {
        $buttons = new stdClass();
        $buttons->moveup = self::get_move_up_button($item);
        $buttons->movedown = self::get_move_down_button($item);
        $buttons->configure = self::get_configure_button($item);
        $buttons->delete = self::get_delete_button($item);
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
    protected function get_configure_button(item $item) {
        $button = new stdClass();
        $button->name = 'configure';
        $button->title = get_string('configureitem', 'report_lp');
        $button->icon = '<i class="fa fa-cog fa-fw"></i>';
        if ($item->get_configuration()->get('shortname') == grouping::get_short_name()) {
            $button->url = url::get_grouping_url(null, $item->get_configuration()->get('id'))->out(false);
        } else {
            $button->url = url::get_measure_url(null, $item->get_configuration()->get('id'))->out(false);
        }
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
    protected function get_delete_button(item $item) {
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
    protected function get_move_up_button(item $item) {
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
    protected function get_move_down_button(item $item) {
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
