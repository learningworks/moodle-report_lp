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
 * Plugin upgrade steps are defined here.
 *
 * @package     report_lp
 * @category    upgrade
 * @copyright   2019 Troy Williams <troy.williams@learningworks.co.nz>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute report_lp upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_report_lp_upgrade($oldversion) {
    global $DB;

    $dbmanager = $DB->get_manager();

    // For further information please read the Upgrade API documentation:
    // https://docs.moodle.org/dev/Upgrade_API
    //
    // You will also have to create the db/install.xml file by using the XMLDB Editor.
    // Documentation for the XMLDB Editor can be found at:
    // https://docs.moodle.org/dev/XMLDB_editor

    // Dropping all old tables and install all new using xml file.
    if ($oldversion < 2019060700) {

        // Define table report_lp_tracked to be dropped.
        $table = new xmldb_table('report_lp_tracked');

        // Conditionally launch drop table for report_lp_tracked.
        if ($dbmanager->table_exists($table)) {
            $dbmanager->drop_table($table);
        }

        // Define table report_lp_learnerprogress to be dropped.
        $table = new xmldb_table('report_lp_learnerprogress');

        // Conditionally launch drop table for report_lp_learnerprogress.
        if ($dbmanager->table_exists($table)) {
            $dbmanager->drop_table($table);
        }

        // Install new tables.
        $dbmanager->install_from_xmldb_file(__dir__ . '/install.xml');

        // Lp savepoint reached.
        upgrade_plugin_savepoint(true, 2019060700, 'report', 'lp');
    }

    // Ammendments to items table.
    if ($oldversion < 2019072200) {

        // Changing the default of field depth on table report_lp_items to 0.
        $table = new xmldb_table('report_lp_items');
        $field = new xmldb_field('depth', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Launch change of default for field depth.
        $dbmanager->change_field_default($table, $field);

        // Define field islocked to be added to report_lp_items.
        $table = new xmldb_table('report_lp_items');
        $field = new xmldb_field('islocked', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'sortorder');

        // Conditionally launch add field islocked.
        if (!$dbmanager->field_exists($table, $field)) {
            $dbmanager->add_field($table, $field);
        }

        // Define field isgrouping to be dropped from report_lp_items.
        $table = new xmldb_table('report_lp_items');
        $field = new xmldb_field('isgrouping');

        // Conditionally launch drop field isgrouping.
        if ($dbmanager->field_exists($table, $field)) {
            $dbmanager->drop_field($table, $field);
        }

        // Lp savepoint reached.
        upgrade_plugin_savepoint(true, 2019072200, 'report', 'lp');
    }

    return true;
}
