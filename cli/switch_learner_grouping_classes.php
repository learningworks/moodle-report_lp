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
Switches Learner grouping class to standard grouping class in 
item configurations.

Options:
--non-interactive     No interactive questions or confirmations
-v, --verbose         Print verbose progess information
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php admin/cli/switch_learner_grouping_classes.php
";

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}
if ($options['help']) {
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
$itemconfigurations = report_lp\local\persistents\item_configuration::get_records(['shortname' => 'learner_information_grouping']);
$count = count($itemconfigurations);
try {
    if ($interactive) {
        cli_writeln("Switch {$count} instances?");
        $prompt = get_string('cliyesnoprompt', 'admin');
        $input = cli_input(
            $prompt,
            '',
            [get_string('clianswerno', 'admin'), get_string('cliansweryes', 'admin')]
        );
        if ($input == get_string('clianswerno', 'admin')) {
            exit(0);
        }
        $grouping = new report_lp\local\grouping();
        foreach($itemconfigurations as $itemconfiguration) {
            $itemconfiguration->set('shortname', $grouping::get_short_name());
            $itemconfiguration->set('classname', $grouping::get_class_name());
            $itemconfiguration->set('usecustomlabel', true);
            $itemconfiguration->set('customlabel', get_string('learnerinformation', 'report_lp'));
            $itemconfiguration->save();
        }
    }
    $trace->output("Migration complete");
} catch (Exception $ex) {
    $trace->output($ex->getMessage());
    exit(1);
}
exit(0);
