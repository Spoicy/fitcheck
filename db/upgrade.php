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

function xmldb_local_fitcheck_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2021021000) {

        // Define field testnr to be added to local_fitcheck_classes.
        $table = new xmldb_table('local_fitcheck_classes');
        $field = new xmldb_field('testnr', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'gender');

        // Conditionally launch add field testnr.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field offset to be added to local_fitcheck_users.
        $table = new xmldb_table('local_fitcheck_users');
        $field = new xmldb_field('offset', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'id');

        // Conditionally launch add field offset.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field testnr to be added to local_fitcheck_results.
        $table = new xmldb_table('local_fitcheck_results');
        $field = new xmldb_field('testnr', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'result');

        // Conditionally launch add field testnr.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Fitcheck savepoint reached.
        upgrade_plugin_savepoint(true, 2021021000, 'local', 'fitcheck');
    }
}