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
 * FitCheck class results view
 *
 * @package    local_fitcheck
 * @copyright  2021 Jae Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_login();
require_capability('local/fitcheck:viewallresults', context_system::instance());

$id = required_param('id', PARAM_INT);

$PAGE->set_url(new moodle_url('/local/fitcheck/'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'local_fitcheck') . ' - ' . get_string('classresults', 'local_fitcheck'));
$PAGE->set_heading(get_string('title', 'local_fitcheck'));
$PAGE->navbar->add('FitCheck');
$PAGE->navbar->add(get_string('classresults', 'local_fitcheck'));

$tableheaders = array('studentfirstname', 'studentlastname',  'result', 'grade');
if ($dir == 'asc') {
    $sortdir = 'desc';
} else {
    $sortdir = 'asc';
}
switch ($sort) {
    case "firstname":
        $studentfirstname = html_writer::tag('a', get_string('liststudentfirstname', 'local_fitcheck'),
            ['href' => $PAGE->url . '?sort=studentfirstname&dir=' . $sortdir]) .
            $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
        unset($tableheaders[0]);
        $sqlsort = "fullname $dir";
        break;
    case "studentlastname":
        $studentlastname = html_writer::tag('a', get_string('liststudentlastname', 'local_fitcheck'),
            ['href' => $PAGE->url . '?sort=studentlastname&dir=' . $sortdir]) .
            $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
        unset($tableheaders[1]);
        $sqlsort = "gender $dir";
        break;
    case "result":
        $status = html_writer::tag('a', get_string('listresult', 'local_fitcheck'),
            ['href' => $PAGE->url . '?sort=result&dir=' . $sortdir]) .
            $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
        unset($tableheaders[2]);
        $sqlsort = "result $dir";
        break;
    case "grade":
        $status = html_writer::tag('a', get_string('listgrade', 'local_fitcheck'),
            ['href' => $PAGE->url . '?sort=grade&dir=' . $sortdir]) .
            $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
        unset($tableheaders[2]);
        $sqlsort = "grade $dir";
        break;
    default:
        $sqlsort = '';
}
foreach ($tableheaders as $tableheader) {
    $$tableheader = html_writer::tag('a', get_string('list' . $tableheader, 'local_fitcheck'),
        ['href' => $PAGE->url . '?sort=' . $tableheader . '&dir=asc']);
}

$class = $DB->get_record('local_fitcheck_classes', ['id' => $id]);
$students = $DB->get_records('local_fitcheck_users', ['classid' => $id]);

$table = new html_table();
$table->head = array();
$table->colclasses = array();
$table->head[] = $testname;
$table->head[] = $gender;
$table->head[] = $status;
$table->head[] = get_string('edit');
$table->attributes['class'] = 'admintable generaltable table-sm';

foreach ($students as $student) {
    $row = array();
    $row[] = $test->fullname;
    if ($test->gender) {
        $row[] = get_string('female', 'local_fitcheck');
    } else {
        $row[] = get_string('maleunisex', 'local_fitcheck');
    }
    if ($test->status) {
        $row[] = get_string('active', 'local_fitcheck');
    } else {
        $row[] = get_string('inactive', 'local_fitcheck');
    }
    $row[] = html_writer::link(new moodle_url('/local/fitcheck/settings/edittests.php?id=' . $test->id),
        $OUTPUT->pix_icon('t/edit', get_string('edit'))) . 
        html_writer::link(new moodle_url('/local/fitcheck/test.php?id=' . $test->id),
        $OUTPUT->pix_icon('t/hide', get_string('viewtest', 'local_fitcheck')));
    $table->data[] = $row;
}

echo $OUTPUT->header();
echo html_writer::table($table);
echo $OUTPUT->footer();