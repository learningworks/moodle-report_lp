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
     * Build header cell.
     *
     * @todo maybe depth work.
     *
     * @param int|null $depth
     * @return mixed|cell
     */
    public function build_header_cell(int $depth = null) {
        if ($depth == 1 && $this->get_depth() == 1) {
            return new cell();
        }
        return parent::build_header_cell($depth);
    }

}
