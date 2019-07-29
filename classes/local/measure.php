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
use stdClass;
use report_lp\local\contracts\data_provider;

defined('MOODLE_INTERNAL') || die();

/**
 * All measure classes must extend this class. Used to define a measure.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class measure extends item implements data_provider {

    /**
     * Formats user data from this measure.
     *
     * @param $data
     * @param string $format
     * @return string
     */
    abstract public function format_user_measure_data($data, $format = FORMAT_PLAIN) : string;

    /**
     * Fallback. Measures to extend and provide move data.
     *
     * @param bool $header
     * @return mixed|cell
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function get_cell_data(bool $header = true) {
        $cell = new cell();

        if ($header) {
            $renderer = $this->get_renderer();
            $text = $this->get_label();
            $cell->header = true;
            $cell->text = $text;
            $contents = new stdClass();
            $contents->text = $text;
            $contents->title = $text;
            if ($this->has_url()) {
                $link = new stdClass();
                $link->text = $text;
                $link->alt = $text;
                $link->src = $this->get_url()->out();
                $contents->link = $link;
            }
            if ($this->has_icon()) {
                $contents->icon =  $this->get_icon()->export_for_template($renderer);
            }
            $cell->contents = $renderer->render_from_template(
                'report_lp/cell_contents', $contents);
            return $cell;
        }
        return $cell;

    }

}
