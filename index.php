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
require_once(__DIR__ . '/locallib.php');
$groupname = optional_param('groupname', 0, PARAM_TEXT);
$categoryid = optional_param('categoryid', 0, PARAM_TEXT);

require_login();

$context = context_system::instance();
require_capability('report/lp:view', $context);
$PAGE->set_context($context);

$url = new moodle_url('/report/lp/index.php');
if ($groupname) {
    $url->param('groupname', $groupname);
}
if ($categoryid) {
    $url->param('categoryid', $categoryid);
}
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');



echo $OUTPUT->header();
$renderer = $PAGE->get_renderer('report_lp');

$groupnames = \report_lp\learnerprogress::get_course_groupnames();
$menu = [0 => get_string('selectgroup', 'report_lp')] + $groupnames;
$groupselect = new \report_lp\output\select('groupname', $url, $groupnames);
$groupselect->label = get_string('coursegroup', 'report_lp');
$groupselect->selected = $groupname;
if ($groupname) {
    $categories = \report_lp\learnerprogress::get_course_categorynames_by_group($groupname);
    $menu = [0 => get_string('selectcategory', 'report_lp')] + $categories;
    $categoryselect = new \report_lp\output\select('categoryid', $url, $menu);
    $categoryselect->label = get_string('coursecategory', 'report_lp');
    $categoryselect->selected = $categoryid;
}

echo html_writer::start_div('filter-wrapper');
echo $renderer->render($groupselect);
if (isset($categoryselect)) {
    echo $renderer->render($categoryselect);
}
echo html_writer::end_div();

echo $OUTPUT->footer();
