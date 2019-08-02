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

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/report/lp/lib.php');
require_once($CFG->libdir . '/grouplib.php');
require_once($CFG->libdir . "/excellib.class.php");

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
$coursecontext = context_course::instance($courseid);

require_login($course);
require_capability('report/lp:exportsummary', $coursecontext);

$summaryurl = report_lp\local\factories\url::get_summary_url($course);
$filteredcoursegroups = report_lp\local\course_group::get_active_filter($course->id);
if (empty($filteredcoursegroups)) {
    redirect($summaryurl, get_string('nogroupsselected', 'report_lp'), 1);
}
// Build data.
$summary = new report_lp\local\summary_report($course);
$data = $summary->build_data();
$time = strftime("%d-%m-%YT%H%M%S");
$workbook = new MoodleExcelWorkbook("-");
$filename =  "{$course->shortname} LPS Report {$time}" . ".xls";
// Sending HTTP headers.
$workbook->send($filename);
// Creating the first worksheet.
$sheet = $workbook->add_worksheet('Learner progress summary');
// Format types.
$formatbc = $workbook->add_format();
$formatbc->set_bold(1);

$rowindex = 0;
// Process header rows.
$theadrows = $data->thead->rows;
$theadrowcount = count($theadrows);
foreach ($theadrows as $theadrow) {
    $cells = $theadrow->cells;
    $columnindex = 0;
    foreach ($cells as $cell) {
        $text = !empty($cell->text) ? $cell->text : '';
        if ($rowindex === 0) {
            $sheet->write($rowindex, $columnindex, $text, $formatbc);
        } else {
            $sheet->write($rowindex, $columnindex, $text);
        }
        if ($cell->colspan > 1) {
            $firstcolumn = $columnindex;
            $colspan = (int) $cell->colspan;
            for ($i = 1; $i < $colspan; $i++) {
                $sheet->write($rowindex, ++$columnindex, '');
            }
            $sheet->merge_cells($rowindex, $firstcolumn, $rowindex, $columnindex);
        }
        $columnindex++;
    }
    $rowindex++;
}
// Process data rows.
$tbodyrows = $data->tbody->rows;
$tbodyrowcount = count($theadrows);
$rowindex = $theadrowcount;
foreach ($tbodyrows as $tbodyrow) {
    $cells = $tbodyrow->cells;
    $columnindex = 0;
    foreach ($cells as $cell) {
        $text = !empty($cell->plaintextcontent) ? $cell->plaintextcontent : '';
        $sheet->write($rowindex, $columnindex, $text);
        $columnindex++;
    }
    $rowindex++;
}
$workbook->close();
