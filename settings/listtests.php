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
 * FitCheck Tests list view
 *
 * @package    local_fitcheck
 * @copyright  2021 Jae Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/local/fitcheck/lib.php');
require_login();
require_capability('local/fitcheck:editclasses', context_system::instance());

$sort = optional_param('sort', '', PARAM_TEXT);
$dir = optional_param('dir', '', PARAM_TEXT);

$PAGE->set_url(new moodle_url('/local/fitcheck/settings/listtests.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'local_fitcheck'));
$PAGE->set_heading(get_string('title', 'local_fitcheck'));
$PAGE->navbar->add('FitCheck', new moodle_url('/local/fitcheck'));
$PAGE->navbar->add(get_string('settings', 'local_fitcheck'), new moodle_url('/local/fitcheck/settings'));
$PAGE->navbar->add(get_string('browselisttests', 'local_fitcheck'));

if (!has_capability('local/fitcheck:deleteusers', context_system::instance())) {
    print_error('accessdenied', 'admin');
}

$tableheaders = array('testname', 'gender', 'status');
if ($dir == 'asc') {
    $sortdir = 'desc';
} else {
    $sortdir = 'asc';
}
switch ($sort) {
    case "testname":
        $testname = html_writer::tag('a', get_string('listtestname', 'local_fitcheck'),
            ['href' => $PAGE->url . '?sort=testname&dir=' . $sortdir]) .
            $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
        unset($tableheaders[0]);
        $sqlsort = "fullname $dir";
        break;
    case "gender":
        $gender = html_writer::tag('a', get_string('listgender', 'local_fitcheck'),
            ['href' => $PAGE->url . '?sort=gender&dir=' . $sortdir]) .
            $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
        unset($tableheaders[1]);
        $sqlsort = "gender $dir";
        break;
    case "status":
        $status = html_writer::tag('a', get_string('liststatus', 'local_fitcheck'),
            ['href' => $PAGE->url . '?sort=status&dir=' . $sortdir]) .
            $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
        unset($tableheaders[2]);
        $sqlsort = "status $dir";
        break;
    default:
        $sqlsort = '';
}
foreach ($tableheaders as $tableheader) {
    $$tableheader = html_writer::tag('a', get_string('list' . $tableheader, 'local_fitcheck'),
        ['href' => $PAGE->url . '?sort=' . $tableheader . '&dir=asc']);
}

$tests = $DB->get_records('local_fitcheck_tests', null, $sqlsort);

$table = new html_table();
$table->head = array();
$table->colclasses = array();
$table->head[] = $testname;
$table->head[] = $gender;
$table->head[] = $status;
$table->head[] = get_string('edit');
$table->attributes['class'] = 'admintable generaltable table-sm';

foreach ($tests as $test) {
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
echo html_writer::tag('a', get_string('addnewtest', 'local_fitcheck'),
    ['href' => new moodle_url('/local/fitcheck/settings/edittests.php?id=-1'), 'class' => 'btn btn-secondary']);
echo $OUTPUT->footer();