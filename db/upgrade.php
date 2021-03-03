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
 * XMLDB upgrade instructions
 *
 * @package    local_fitcheck
 * @copyright  2021 Jae Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Update FitCheck DB tables
 * 
 * @param int $oldversion
 */
function xmldb_local_fitcheck_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2021022300) {

        // Define field id to be added to local_fitcheck_results.
        $table = new xmldb_table('local_fitcheck_results');
        $field = new xmldb_field('result', XMLDB_TYPE_NUMBER, '10, 3', null, null, null, null, 'id');

        // Launch change of type for field result.
        $dbman->change_field_type($table, $field);

        // Fitcheck savepoint reached.
        upgrade_plugin_savepoint(true, 2021022300, 'local', 'fitcheck');
    }

    if ($oldversion < 2021022301) {

        // Define field status to be added to local_fitcheck_classes.
        $table = new xmldb_table('local_fitcheck_classes');
        $statusfield = new xmldb_field('status', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'id');
        $agegroupfield = new xmldb_field('agegroup', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'testnr');
        $endyearfield = new xmldb_field('endyear', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'agegroup');

        // Conditionally launch add field status.
        if (!$dbman->field_exists($table, $statusfield)) {
            $dbman->add_field($table, $statusfield);
        }

        // Conditionally launch add field agegroup.
        if (!$dbman->field_exists($table, $agegroupfield)) {
            $dbman->add_field($table, $agegroupfield);
        }

        // Conditionally launch add field endyear.
        if (!$dbman->field_exists($table, $endyearfield)) {
            $dbman->add_field($table, $endyearfield);
        }

        // Fitcheck savepoint reached.
        upgrade_plugin_savepoint(true, 2021022301, 'local', 'fitcheck');
    }

    if ($oldversion < 2021030100) {

        // Define field thumbnail to be dropped from local_fitcheck_tests.
        $table = new xmldb_table('local_fitcheck_tests');
        $field = new xmldb_field('thumbnail');

        // Conditionally launch drop field thumbnail.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Fitcheck savepoint reached.
        upgrade_plugin_savepoint(true, 2021030100, 'local', 'fitcheck');
    }

    if ($oldversion < 2021030101) {

        // Define field step to be added to local_fitcheck_tests.
        $table = new xmldb_table('local_fitcheck_tests');
        $field = new xmldb_field('step', XMLDB_TYPE_NUMBER, '10, 3', null, null, null, null, 'minresult');

        // Conditionally launch add field step.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Fitcheck savepoint reached.
        upgrade_plugin_savepoint(true, 2021030101, 'local', 'fitcheck');
    }
}