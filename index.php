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

/**
 *  DESCRIPTION
 *
 * @package   {{PLUGIN_NAME}} {@link https://docs.moodle.org/dev/Frankenstyle}
 * @copyright 2015 LearningWorks Ltd {@link http://www.learningworks.co.nz}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
//require_once(__DIR__ . '/locallib.php');

require_login();

$context = context_system::instance();
require_capability('report/learnerprogress:view', $context);
$PAGE->set_context($context);

$url = new moodle_url('/report/learnerprogress/index.php');
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');



echo $OUTPUT->header();

$groupnames = \report_learnerprogress\learnerprogress::get_distinct_course_groupnames();
$renderer = $PAGE->get_renderer('report_learnerprogress');

//$sectionoptions = array('0' => get_string('selectchapter', 'gradereport_biozone'));
$groupselect = new \report_learnerprogress\output\select('groups', $url, $groupnames);
$groupselect->options($groupnames);
//$sectionselect->label = get_string('chapter', 'gradereport_biozone');
//$sectionselect->selected = $sectionid;
echo $renderer->render($groupselect);

echo $OUTPUT->footer();
