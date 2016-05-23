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

namespace report_learnerprogress\output;

class select implements \renderable, \templatable {
    public $name;
    public $url;
    public $options;
    public $method = 'get';
    public $label = '';
    public $selected;
    public $disabled;
    public $class = 'moodle-actionmenu';
    public $tooltip;


    public function __construct($name, \moodle_url $url, array $options) {
        $this->name     = $name;
        $this->url      = $url;
        $this->options($options);
    }

    public function options(array $options = null) {
        $options = (array) $options;
        foreach ($options as $key => $value) {
            if (!empty($value)) {
                $this->options[$key] = (string) $value;
            }
        }
        return $this->options;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output) {
        $data = new \stdClass();
        $data->name = $this->name;

        $options = array();
        foreach ($this->options as $key => $name) {
            $selected = '';
            if (isset($this->selected) and $key == $this->selected) {
                $selected = 'selected';
            }
            $options[] = (object) array('name' => $name, 'value' => $key, 'selected' => $selected);
        }
        $data->options = $options;

        // Setup form information.
        if ($this->method != 'get' or $this->method != 'post') {
            $this->method = 'get'; // Get by default.
        }
        $data->method = $this->method;
        $data->action = $this->url->out_omit_querystring();

        $params = $this->url->params();
        if ($this->method == 'post') {
            $params['sesskey'] = sesskey();
        }
        unset($params[$this->name]); // Unset element name if present.
        // Build hidden field objects.
        $hidden = array();
        foreach ($params as $name => $value) {
            $hidden[] = (object) array('name' => $name, 'value' => $value);
        }
        // Add hidden field data.
        $data->hidden = $hidden;

        $data->label = $this->label;
        if ($this->disabled) {
            $data->disabled = 'disabled';
        }

        return $data;
    }

}