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

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->libdir.'/cronlib.php');
require_once($CFG->dirroot.'/report/lp/lib.php');

// We may need a lot of memory here.
@set_time_limit(0);
raise_memory_limit(MEMORY_HUGE);

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    [
        'instanceid'        => false,
        'non-interactive'   => false,
        'verbose'           => false,
        'help'              => false
    ]
    ,
    [
        'v' => 'verbose',
        'h' => 'help'
    ]

);

$help = "
Deletes Learner Progress Report for a course

Options:
--instanceid          Course idenfifier number (required)
--non-interactive     No interactive questions or confirmations
-v, --verbose         Print verbose progess information
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php admin/cli/delete_users_from_file.php
";

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}
if ($options['help'] || empty($options['instanceid'])) {
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
$instanceid = $options['instanceid'];
if ($instanceid <= 1) {
    $trace->output("Invalid instanceid");
    exit(1);
}
try {
    $course = get_course($instanceid);
    if ($interactive) {
        cli_writeln("Delete Learner Progress Report for course {$course->shortname}?");
        $prompt = get_string('cliyesnoprompt', 'admin');
        $input = cli_input(
            $prompt,
            '',
            [get_string('clianswerno', 'admin'), get_string('cliansweryes', 'admin')]
        );
        if ($input == get_string('clianswerno', 'admin')) {
            exit(0);
        }
    }
    report_lp\local\report::delete_course_instance($course->id);
    $trace->output("Report deleted");
} catch (Exception $ex) {
    $trace->output($ex->getMessage());
    exit(1);
}
exit(0);
