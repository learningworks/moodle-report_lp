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

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->libdir.'/cronlib.php');
require_once($CFG->dirroot.'/report/lp/lib.php');

// We may need a lot of memory here.
@set_time_limit(0);
raise_memory_limit(MEMORY_HUGE);

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'non-interactive'   => false,
        'verbose'           => false,
        'help'              => false,

    ),
    array(
        'v' => 'verbose',
        'h' => 'help'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "
Options:
--non-interactive     No interactive questions or confirmations
-v, --verbose         Print verbose progess information
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php ?.php
";
    echo $help;
    die;
}

// Ensure errors are well explained.
set_debugging(DEBUG_DEVELOPER, true);

cron_setup_user();

if (empty($options['verbose'])) {
    $trace = new null_progress_trace();
} else {
    $trace = new text_progress_trace();
}
$interactive = empty($options['non-interactive']);


$course = get_course(4);
$report = new report_lp\local\summary($course);
$report->build_data();

//report_lp\local\report::handle_course_module_deletion($course->id, 'attendance', 2);

//$factory = new report_lp\local\factories\item($course, new report_lp\local\item_type_list());
//$persistent = new report_lp\local\persistents\item_configuration(15);
//$item = $factory->get_item_from_persistent($persistent);
//print_object($item);

//$tree = new report_lp\local\builders\item_tree($course);
//$root = $tree->build_from_item_configurations();
//print_object($root->count());

exit(0);