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

use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * The structure of table/sheet cell.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class cell implements renderable, templatable {

    /** @var string Value to use for the id attribute of the cell. */
    public $id;

    /** @var string The content of cell cell in text or HTML*/
    public $content;

    /** @var stdClass The templatable content. Requires template to render. May include other HTML tags, images, URL's etc. */
    public $templatablecontent;

    /** @var string The content of cell in HTML.  */
    public $htmlcontent;

    /** @var string The content of cell in plain text. */
    public $plaintextcontent;

    /** @var int Number of columns this cell should span. */
    public $colspan;

    /** @var int Number of rows this cell should span. */
    public $rowspan;

    /** @var string Defines a way to associate header cells and data cells in a table. */
    public $scope;

    /** @var bool Whether or not this cell is a header cell. */
    public $header;

    /** @var array Attributes of additional HTML attributes for the <td> element */
    public $attributes = [];

    public $class = 'cell';

    /** @var string $template The template used to render... */
    public $template;

    /**
     * @param renderer_base $output
     * @return array|stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        return $data;
    }
}
