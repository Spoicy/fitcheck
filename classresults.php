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
$sort = optional_param('sort', '', PARAM_TEXT);
$dir = optional_param('dir', '', PARAM_TEXT);
$view = optional_param('view', 0, PARAM_INT);

$PAGE->set_url(new moodle_url('/local/fitcheck/classresults.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'local_fitcheck') . ' - ' . get_string('classresults', 'local_fitcheck'));
$PAGE->set_heading(get_string('title', 'local_fitcheck'));
$PAGE->navbar->add('FitCheck');
$PAGE->navbar->add(get_string('classresults', 'local_fitcheck'));

if ($dir == 'asc') {
    $sortdir = 'desc';
} else {
    $sortdir = 'asc';
}

$class = $DB->get_record('local_fitcheck_classes', ['id' => $id]);
$tests = $DB->get_records('local_fitcheck_tests', ['status' => 1, 'gender' => $class->gender]);
if ($view == 0) {
    $selectoptions = html_writer::tag('option', get_string('average', 'local_fitcheck'), ['value' => 'average', 'selected' => '']);
} else {
    $selectoptions = html_writer::tag('option', get_string('average', 'local_fitcheck'), ['value' => 'average']);
}
foreach ($tests as $test) {
    if ($view == $test->id) {
        $selectoptions .= html_writer::tag('option', $test->shortname, ['value' => $test->id, 'selected' => '']);
    } else {
        $selectoptions .= html_writer::tag('option', $test->shortname, ['value' => $test->id]);
    }
}
$select = html_writer::tag('select', $selectoptions,
    ['name' => 'view', 'id' => 'view', 'class' => 'select custom-select mb-3', 'onchange' => 'selectForm.submit();']);

$table = new html_table();
$table->head = array();
$table->colclasses = array();

if ($view != 0) {
    $tableheaders = array('studentfirstname', 'studentlastname', 'result', 'grade');
    switch ($sort) {
        case "studentfirstname":
            $studentfirstname = html_writer::tag('a', get_string('liststudentfirstname', 'local_fitcheck'),
                ['href' => $PAGE->url . '?id=' . $id . '&view=' . $view . '&sort=studentfirstname&dir=' . $sortdir]) .
                $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
            unset($tableheaders[0]);
            $sqlsort = "fullname $dir";
            break;
        case "studentlastname":
            $studentlastname = html_writer::tag('a', get_string('liststudentlastname', 'local_fitcheck'),
                ['href' => $PAGE->url . '?id=' . $id . '&view=' . $view . '&sort=studentlastname&dir=' . $sortdir]) .
                $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
            unset($tableheaders[1]);
            $sqlsort = "gender $dir";
            break;
        case "result":
            $result = html_writer::tag('a', get_string('listresult', 'local_fitcheck'),
                ['href' => $PAGE->url . '?id=' . $id . '&view=' . $view . '&sort=result&dir=' . $sortdir]) .
                $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
            unset($tableheaders[2]);
            $sqlsort = "result $dir";
            break;
        case "grade":
            $grade = html_writer::tag('a', get_string('listgrade', 'local_fitcheck'),
                ['href' => $PAGE->url . '?id=' . $id . '&view=' . $view . '&sort=grade&dir=' . $sortdir]) .
                $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
            unset($tableheaders[2]);
            $sqlsort = "grade $dir";
            break;
        default:
            $sqlsort = '';
    }
    foreach ($tableheaders as $tableheader) {
        $$tableheader = html_writer::tag('a', get_string('list' . $tableheader, 'local_fitcheck'),
            ['href' => $PAGE->url . '?id=' . $id . '&view=' . $view . '&sort=' . $tableheader . '&dir=asc']);
    }
    $table->head[] = $studentfirstname . ' / ' . $studentlastname;
    $table->head[] = $result;
    $table->head[] = $grade;
} else {
    $tableheaders = array('studentfirstname', 'studentlastname', 'grade');
    switch ($sort) {
        case "studentfirstname":
            $studentfirstname = html_writer::tag('a', get_string('liststudentfirstname', 'local_fitcheck'),
                ['href' => $PAGE->url . '?id=' . $id . '&view=' . $view . '&sort=studentfirstname&dir=' . $sortdir]) .
                $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
            unset($tableheaders[0]);
            $sqlsort = "fullname $dir";
            break;
        case "studentlastname":
            $studentlastname = html_writer::tag('a', get_string('liststudentlastname', 'local_fitcheck'),
                ['href' => $PAGE->url . '?id=' . $id . '&view=' . $view . '&sort=studentlastname&dir=' . $sortdir]) .
                $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
            unset($tableheaders[1]);
            $sqlsort = "gender $dir";
            break;
        case "grade":
            $grade = html_writer::tag('a', get_string('listgrade', 'local_fitcheck'),
                ['href' => $PAGE->url . '?id=' . $id . '&view=' . $view . '&sort=grade&dir=' . $sortdir]) .
                $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
            unset($tableheaders[2]);
            $sqlsort = "grade $dir";
            break;
        default:
            $sqlsort = '';
    }
    foreach ($tableheaders as $tableheader) {
        $$tableheader = html_writer::tag('a', get_string('list' . $tableheader, 'local_fitcheck'),
            ['href' => $PAGE->url . '?id=' . $id . '&view=' . $view . '&sort=' . $tableheader . '&dir=asc']);
    }
    $table->head[] = $studentfirstname . ' / ' . $studentlastname;
    $table->head[] = $grade;
}

$students = $DB->get_records_sql('SELECT u.firstname, u.lastname, lfu.id FROM {user} u, {local_fitcheck_users} lfu
    WHERE classid = ' . $id .
    ' AND u.id = lfu.userid');

$table->head[] = get_string('edit');
$table->attributes['class'] = 'admintable generaltable table-sm';

foreach ($students as $student) {
    $row = array();
    $row[] = "$student->firstname $student->lastname";
    if (isset($result)) {
        $row[] = 'result';
    }
    $row[] = 'grade';
    $row[] = html_writer::link(new moodle_url('/local/fitcheck/settings/editresults.php?id=' . $student->id),
        $OUTPUT->pix_icon('t/edit', get_string('edit'))) .
        html_writer::link(new moodle_url('/local/fitcheck/results.php?id=' . $student->id),
        $OUTPUT->pix_icon('t/hide', get_string('viewstudentresults', 'local_fitcheck')));
    $table->data[] = $row;
}

$extras = '';
if ($sort) {
    $extras .= '&sort=' . $sort;
}
if ($dir) {
    $extras .= '&dir=' . $dir;
}

$hidinputs = html_writer::tag('input', '', ['value' => $sort, 'name' => 'sort', 'id' => 'sort', 'hidden' => '']) .
    html_writer::tag('input', '', ['value' => $dir, 'name' => 'dir', 'id' => 'dir', 'hidden' => '']) .
    html_writer::tag('input', '', ['value' => $id, 'name' => 'id', 'id' => 'id', 'hidden' => '']);

echo $OUTPUT->header();
echo html_writer::tag('form', $select . $hidinputs, ['method' => 'get', 'action' => '#', 'name' => 'selectForm']);
echo html_writer::table($table);
echo $OUTPUT->footer();