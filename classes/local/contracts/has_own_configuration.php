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

namespace report_lp\local\contracts;

/**
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use stdClass;
use MoodleQuickForm;

interface has_own_configuration {

    /**
     * Extend passed in MoodleQuickForm.
     *
     * @param MoodleQuickForm $mform
     * @return mixed
     */
    public function moodlequickform_extend(MoodleQuickForm &$mform);

    /**
     * Valid data and files, called from within main item form validation.
     *
     * @param $data
     * @param $files
     * @return array
     */
    public function moodlequickform_validation($data, $files) : array;

    /**
     * Use to filter specific extra configuration data to be stored in
     * extraconfigurationdata column.
     *
     * @param $data
     * @return stdClass
     */
    public function moodlequickform_get_extra_configuration_data($data) : stdClass;

    /**
     * Get any default values.
     *
     * @return array
     */
    public function moodlequickform_get_extra_configuration_defaults() : array;

}
