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

    if ($oldversion < 2021012500) {

        // Define field teacherid to be added to local_fitcheck_classes.
        $table = new xmldb_table('local_fitcheck_classes');
        $field = new xmldb_field('teacherid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'gender');

        // Conditionally launch add field teacherid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define key teacherid (foreign) to be added to local_fitcheck_classes.
        $table = new xmldb_table('local_fitcheck_classes');
        $key = new xmldb_key('teacherid', XMLDB_KEY_FOREIGN, ['teacherid'], 'user', ['id']);

        // Launch add key teacherid.
        $dbman->add_key($table, $key);

        // Fitcheck savepoint reached.
        upgrade_plugin_savepoint(true, 2021012500, 'local', 'fitcheck');
    }
}