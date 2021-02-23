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
 * FitCheck Classes list view
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

if (has_capability('local/fitcheck:deleteusers', context_system::instance())) {
    $conditions = ['status' => 1];
} else if (has_capability('local/fitcheck:editclasses', context_system::instance())) {
    $conditions = ['teacherid' => $USER->id, 'status' => 1];
} else {
    print_error('accessdenied', 'admin');
}

$PAGE->set_url(new moodle_url('/local/fitcheck/settings/listclasses.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'local_fitcheck') . ' - ' . get_string('classeslist', 'local_fitcheck'));
$PAGE->set_heading(get_string('title', 'local_fitcheck'));
$PAGE->navbar->add('FitCheck', new moodle_url('/local/fitcheck'));
$PAGE->navbar->add(get_string('settings', 'local_fitcheck'), new moodle_url('/local/fitcheck/settings'));
$PAGE->navbar->add(get_string('browselistclasses', 'local_fitcheck'));

// Prepare table headings with sort functionality.
$tableheaders = array('classname', 'gender', 'firstname', 'lastname');
if ($dir == 'asc') {
    $sortdir = 'desc';
} else {
    $sortdir = 'asc';
}
switch ($sort) {
    case "classname":
        $classname = html_writer::tag('a', get_string('listclassname', 'local_fitcheck'),
            ['href' => $PAGE->url . '?sort=classname&dir=' . $sortdir]) .
            $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
        unset($tableheaders[0]);
        $sqlsort = "name $dir";
        break;
    case "gender":
        $gender = html_writer::tag('a', get_string('listgender', 'local_fitcheck'),
            ['href' => $PAGE->url . '?sort=gender&dir=' . $sortdir]) .
            $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
        unset($tableheaders[1]);
        $sqlsort = "gender $dir";
        break;
    case "firstname":
        $firstname = html_writer::tag('a', get_string('listfirstname', 'local_fitcheck'),
            ['href' => $PAGE->url . '?sort=firstname&dir=' . $sortdir]) .
            $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
        unset($tableheaders[2]);
        $teachersort = $dir;
        $sortname = $sort;
        break;
    case "lastname":
        $lastname = html_writer::tag('a', get_string('listlastname', 'local_fitcheck'),
            ['href' => $PAGE->url . '?sort=lastname&dir=' . $sortdir]) .
            $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
        unset($tableheaders[3]);
        $teachersort = $dir;
        $sortname = $sort;
        break;
    default:
        $sqlsort = '';
}
// If sorting by teacher, prepare classes SQL differently.
if (isset($teachersort)) {
    if (array_key_first($conditions) != 'status') {
        $conditions = 'AND lfc.' . array_key_first($conditions) . ' = ' . $conditions[0];
    } else {
        $conditions = '';
    }
    $classes = $DB->get_records_sql('SELECT lfc.id, lfc.name, lfc.gender, lfc.teacherid, u.firstname
        FROM {local_fitcheck_classes} lfc, {user} u
        WHERE u.id = lfc.teacherid AND lfc.status = 1 ' . $conditions . '
        ORDER BY u.' . $sortname . ' ' . $dir);
} else {
    $classes = $DB->get_records('local_fitcheck_classes', $conditions, $sqlsort);
}
foreach ($tableheaders as $tableheader) {
    $$tableheader = html_writer::tag('a', get_string('list' . $tableheader, 'local_fitcheck'),
        ['href' => $PAGE->url . '?sort=' . $tableheader . '&dir=asc']);
}

// Set page heading differently if in teacher view.
if ((is_array($conditions) && array_key_first($conditions) != 'status')) {
    $heading = html_writer::tag('h2', get_string('classamountforteacher', 'local_fitcheck', count($classes)));
    $teacher = get_string('listteacher', 'local_fitcheck');
} else {
    $heading = html_writer::tag('h2', get_string('classamount', 'local_fitcheck', count($classes)));
    $teacher = $firstname . ' / ' . $lastname;
}

// Prepare table.
$table = new html_table();
$table->head = array();
$table->colclasses = array();
$table->head[] = $classname;
$table->head[] = $gender;
$table->head[] = $teacher;
$table->head[] = get_string('edit');
$table->attributes['class'] = 'listtable admintable generaltable table-sm';

// Insert class data into table.
foreach ($classes as $class) {
    $row = array();
    $teacher = $DB->get_record('user', ['id' => $class->teacherid]);
    $row[] = $class->name;
    if ($class->gender) {
        $row[] = get_string('female', 'local_fitcheck');
    } else {
        $row[] = get_string('maleunisex', 'local_fitcheck');
    }
    $row[] = "$teacher->firstname $teacher->lastname";
    $row[] = html_writer::link(new moodle_url('/local/fitcheck/settings/editclass.php?id=' . $class->id),
        $OUTPUT->pix_icon('t/edit', get_string('edit'))) .
        html_writer::link(new moodle_url('/local/fitcheck/classresults.php?id=' . $class->id),
        $OUTPUT->pix_icon('t/hide', get_string('viewclassresults', 'local_fitcheck')));
    $table->data[] = $row;
}

// Output HTML.
echo $OUTPUT->header();
echo $heading;
echo html_writer::table($table);
echo html_writer::tag('a', get_string('addnewclass', 'local_fitcheck'),
    ['href' => new moodle_url('/local/fitcheck/settings/editclass.php?id=-1'), 'class' => 'btn btn-secondary']);
echo $OUTPUT->footer();