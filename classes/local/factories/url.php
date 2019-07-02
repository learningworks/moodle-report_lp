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

namespace report_lp\local\factories;

defined('MOODLE_INTERNAL') || die();

use moodle_url;
use stdClass;
use report_lp\local\item;

/**
 * Factory for creating moodle urls the plugin requires.
 *
 * @package     report_lp
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class url {

    public static function get_item_action_url(int $courseid, int $itemid, string $action = '') : moodle_url {
        $url =  new moodle_url('/report/lp/configure.php',
            [
                'courseid' => $courseid,
                'itemid' => $itemid,
                'sesskey' => sesskey()
            ]
        );
        if (!empty($action)) {
            $url->param('action', $action);
        }
        return $url;
    }

    public static function get_config_url(stdClass $course) : moodle_url {
        $url =  new moodle_url('/report/lp/configure.php',
            [
                'courseid' => $course->id
            ]
        );
        return $url;
    }

    public static function get_summary_url(stdClass $course) : moodle_url {
        $url =  new moodle_url('/report/lp/index.php',
            [
                'courseid' => $course->id
            ]
        );
        return $url;
    }

    public static function get_grouping_url(stdClass $course = null, int $id = 0) : moodle_url {
        $url =  new moodle_url('/report/lp/grouping.php');
        if (!is_null($course)) {
            $url->param('courseid', $course->id);
        }
        if ($id > 0) {
            $url->param('id', $id);
        }
        return $url;
    }

    public static function get_create_item_url(stdClass $course, $shortname) {
        return new moodle_url(
            '/report/lp/item.php',
            [
                'courseid' => $course->id,
                'shortname' => $shortname
            ]
        );
    }

    public static function get_item_url(stdclass $course = null, int $id = 0, string $shortname = null) : moodle_url {
        $url =  new moodle_url('/report/lp/item.php');
        if (!is_null($course)) {
            $url->param('courseid', $course->id);
        }
        if ($id > 0) {
            $url->param('id', $id);
        }
        if (!is_null($shortname)) {
            $url->param('shortname', $shortname);
        }
        return $url;
    }

    public static function get_measure_url(stdclass $course = null, int $id = 0, string $shortname = null) : moodle_url {
        $url =  new moodle_url('/report/lp/measure.php');
        if (!is_null($course)) {
            $url->param('courseid', $course->id);
        }
        if ($id > 0) {
            $url->param('id', $id);
        }
        if (!is_null($shortname)) {
            $url->param('shortname', $shortname);
        }
        return $url;
    }

    public static function get_instantiate_url(int $courseid) : moodle_url {
        return new moodle_url('/report/lp/instantiate.php', ['courseid' => $courseid]);
    }
}
