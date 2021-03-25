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
 * Result update for an individual student
 *
 * @copyright 2021 Jae Funke
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_fitcheck
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/fitcheck/ajax/updateresults.php');

echo $OUTPUT->header();

// Check access.
require_login();
require_capability('local/fitcheck:editresults', context_system::instance());

// Get the results parameter.
$data = json_decode(required_param('data', PARAM_RAW));
$changed = array();

foreach ($data as $result) {
    $resultupdate = new stdClass();
    $resultupdate->id = $result->id;
    if ($result->value == '-' || $result->value == '') {
        $resultupdate->result = null;
        $changed[] = 'td#' . $result->id;
        $DB->update_record('local_fitcheck_results', $resultupdate);
    } else if (is_numeric($result->value)) {
        $resultupdate->result = $result->value;
        $changed[] = 'td#' . $result->id;
        $DB->update_record('local_fitcheck_results', $resultupdate);
    }
}

echo json_encode($changed);