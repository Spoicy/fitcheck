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
 * Student search in edit class view
 *
 * @copyright 2021 Jae Funke
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_fitcheck
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/fitcheck/ajax/search.php');

echo $OUTPUT->header();

// Check access.
require_login();

// Get the search parameter.
$data = json_decode(required_param('data', PARAM_RAW));

// Display the select differently depending on if search is empty or not.
if ($data->search != "") {
    // Differentiate between both selects.
    if ($data->mode == 0) {
        $assigned = $DB->get_records_sql($data->assignedsql . ' AND (CONCAT(u.firstname, " ", u.lastname)
             LIKE "%'.$data->search.'%" OR u.username LIKE "%'.$data->search.'%") ORDER BY u.firstname');
        $assignedselect = '';
        $assignedstringvars = new stdClass();
        $assignedstringvars->search = $data->search;
        $assignedstringvars->count = count($assigned);
        foreach ($assigned as $student) {
            $assignedselect .= html_writer::tag('option', $student->firstname . " " .
                $student->lastname . " (" . $student->username . ")", ['value' => $student->id]);
        }
        $assignedoptgroup = html_writer::tag('optgroup', $assignedselect,
            ['label' => get_string('assignedcountmatching', 'local_fitcheck', $assignedstringvars)]);
        $output = $assignedoptgroup;
    } else {
        $unassigned = $DB->get_records_sql($data->unassignedsql . ' AND (CONCAT(u.firstname, " ", u.lastname)
            LIKE "%'.$data->search.'%" OR u.username LIKE "%'.$data->search.'%") ORDER BY u.firstname');
        $alrassigned = $DB->get_records_sql($data->alrassignedsql . ' AND (CONCAT(u.firstname, " ", u.lastname)
            LIKE "%'.$data->search.'%" OR u.username LIKE "%'.$data->search.'%") ORDER BY u.firstname');
        $unassignedselect = '';
        $unassignedstringvars = new stdClass();
        $unassignedstringvars->search = $data->search;
        $unassignedstringvars->count = count($unassigned);
        foreach ($unassigned as $student) {
            $unassignedselect .= html_writer::tag('option', $student->firstname . " " .
                $student->lastname . " (" . $student->username . ")", ['value' => $student->id]);
        }
        $unassignedoptgroup = html_writer::tag('optgroup', $unassignedselect,
            ['label' => get_string('unassignedcountmatching', 'local_fitcheck', $unassignedstringvars)]);
        $alrassignedselect = '';
        $alrassignedstringvars = new stdClass();
        $alrassignedstringvars->search = $data->search;
        $alrassignedstringvars->count = count($alrassigned);
        foreach ($alrassigned as $student) {
            $alrassignedselect .= html_writer::tag('option', $student->firstname . " " .
                $student->lastname . " (" . $student->username . ")", ['value' => $student->id]);
        }
        $alrassignedoptgroup = html_writer::tag('optgroup', $alrassignedselect,
            ['label' => get_string('alrassignedcountmatching', 'local_fitcheck', $alrassignedstringvars)]);
        $output = $unassignedoptgroup . $alrassignedoptgroup;
    }
} else {
    // Differentiate between both selects.
    if ($data->mode == 0) {
        $assigned = $DB->get_records_sql($data->assignedsql . ' ORDER BY u.firstname');
        $assignedselect = '';
        foreach ($assigned as $student) {
            $assignedselect .= html_writer::tag('option', $student->firstname . " " .
                $student->lastname . " (" . $student->username . ")", ['value' => $student->id]);
        }
        $assignedoptgroup = html_writer::tag('optgroup', $assignedselect,
            ['label' => get_string('assignedcount', 'local_fitcheck', count($assigned))]);
        $output = $assignedoptgroup;
    } else {
        $unassigned = $DB->get_records_sql($data->unassignedsql . ' ORDER BY u.firstname');
        $alrassigned = $DB->get_records_sql($data->alrassignedsql . ' ORDER BY u.firstname');
        $unassignedselect = '';
        foreach ($unassigned as $student) {
            $unassignedselect .= html_writer::tag('option', $student->firstname . " " .
                $student->lastname . " (" . $student->username . ")", ['value' => $student->id]);
        }
        $unassignedoptgroup = html_writer::tag('optgroup', $unassignedselect,
            ['label' => get_string('unassignedcount', 'local_fitcheck', count($unassigned))]);
        foreach ($alrassigned as $student) {
            $alrassignedselect .= html_writer::tag('option', $student->firstname . " " .
                $student->lastname . " (" . $student->username . ")", ['value' => $student->id]);
        }
        $alrassignedoptgroup = html_writer::tag('optgroup', $alrassignedselect,
            ['label' => get_string('alrassignedcount', 'local_fitcheck', count($alrassigned))]);
        $output = $unassignedoptgroup . $alrassignedoptgroup;
    }
}

echo json_encode($output);