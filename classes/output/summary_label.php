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
use stdClass;
use templatable;

/**
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class summary_label implements renderable, templatable {

    protected $text;
    protected $title;
    protected $url;
    protected $icon;

    public function __construct($text, $title = '', moodle_url $url = null, pix_icon $icon = null) {
        $this->text = $text;
        $this->title = $title;
        $this->url = $url;
        $this->icon = $icon;
    }

    /**
     * @param renderer_base $output
     * @return array|stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $data->text = $this->text;
        $data->title = $this->title;
        if (!is_null($this->url)) {
            $data->url = $this->url->out();
        }
        if (!is_null($this->icon)) {
            $data->icon = $this->icon->export_for_template($output);
        }
        return $data;
    }
}
