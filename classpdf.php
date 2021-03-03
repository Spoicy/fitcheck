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
 * Create pdf of class results
 *
 * @copyright 2021 Jae Funke
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_fitcheck
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/tcpdf/tcpdf.php');
require_once($CFG->dirroot.'/local/fitcheck/lib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/fitcheck/ajax/classpdf.php');

// Check access.
require_login();

// Get the search parameter.
$classid = required_param('classid', PARAM_INT);
$testnr = required_param('testnr', PARAM_INT);

// Fetch class and tests from DB.
$class = $DB->get_record('local_fitcheck_classes', ['id' => $classid]);
$tests = $DB->get_records('local_fitcheck_tests', ['status' => 1, 'gender' => $class->gender]);

// Fetch students.
$students = $DB->get_records_sql('SELECT u.firstname, u.lastname, lfu.id, lfu.offset, lfu.userid FROM {user} u,
    {local_fitcheck_users} lfu WHERE classid = ' . $classid .
    ' AND u.id = lfu.userid');

$pdf = new TCPDF("L", PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set document information.
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Kanti');
$pdf->AddPage();

$table = new html_table();
$table->head = array();
$table->colclasses = array();
$table->attributes['class'] = 'printresulttable';
// Cellpadding is deprecated but needed.
$table->cellpadding = 5;

$table->head['borderbottom-thick-0'] = ' ';
$i = 1;
foreach ($tests as $test) {
    $table->head['borderbottom-thick-' . $i] = trim(mb_substr($test->shortname, 0, 5, 'utf-8'));
    $i++;
}
$table->head['borderbottom-thick-' . $i] = get_string('average', 'local_fitcheck');

foreach ($students as $student) {
    $row = array();
    $row[] = "$student->firstname $student->lastname";
    $averagetotal = 0;
    $averagecount = 0;
    foreach ($tests as $test) {
        $result = $DB->get_record('local_fitcheck_results',
            ['userid' => $student->userid, 'testid' => $test->id, 'testnr' => $testnr + $student->offset]);
        if ($result != false && $result->result != null) {
            $grade = local_fitcheck_calc_grade($test, $result->result);
            $row[] = local_fitcheck_calc_grade($test, $result->result);
            $averagetotal += $grade;
            $averagecount++;
        } else {
            $row[] = '-';
        }
    }
    if ($averagecount) {
        $row[] = html_writer::tag('b', round($averagetotal / $averagecount, 2));
    } else {
        $row[] = html_writer::tag('b', '-');
    }
    $table->data[] = $row;
}

$html = html_writer::start_tag('link', ['rel' => 'stylesheet', 'type' => 'text/css',
    'href' => new moodle_url('/local/fitcheck/styles.css')]) .
    html_writer::tag('h2', get_string('class', 'local_fitcheck') . ': ' . $class->name) .
    html_writer::tag('h4', get_string('test', 'local_fitcheck') . ' #' . $testnr) .
    html_writer::table($table);

$pdf->writeHTML($html);

$pdf->Output('resultate_' . $class->name . '_test' . $testnr . '.pdf', 'I');
