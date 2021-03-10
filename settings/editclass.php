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
 * FitCheck Edit/Add Classes form
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

$id = optional_param('id', -1, PARAM_INT);
$idcheck = optional_param('id', -1, PARAM_RAW);

$PAGE->set_url(new moodle_url('/local/fitcheck/settings/editclass.php', array('id' => $id)));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'local_fitcheck'));
$PAGE->set_heading(get_string('title', 'local_fitcheck'));
$PAGE->navbar->add('FitCheck', new moodle_url('/local/fitcheck'));
$PAGE->navbar->add(get_string('settings', 'local_fitcheck'), new moodle_url('/local/fitcheck/settings'));
$PAGE->navbar->add(get_string('listclasses', 'local_fitcheck'), new moodle_url('/local/fitcheck/settings/listclasses.php'));
$PAGE->navbar->add(get_string('editclass', 'local_fitcheck'));

if (!is_numeric($idcheck)) {
    print_error('iderror', 'local_fitcheck');
}

// Load class if exists.
if ($id != -1) {
    $class = $DB->get_record('local_fitcheck_classes', ['id' => $id]);
    if ($class->teacherid != $USER->id && !has_capability('moodle/site:config', context_system::instance())) {
        print_error('teacheriderror', 'local_fitcheck');
    }
} else {
    $class = new stdClass();
    $class->id = -1;
    $class->name = '';
    $class->agegroup = '';
    $class->endyear = '';
}

// Get params.
$classname = optional_param('classname', '', PARAM_TEXT);
$classgender = optional_param('classgender', -1, PARAM_INT);
$classagegroup = optional_param('classagegroup', '', PARAM_TEXT);
$classendyear = optional_param('classendyear', -1, PARAM_INT);
$unassignedstudentid = optional_param('unassignedselect', 0, PARAM_INT);
$assignedstudentid = optional_param('assignedselect', 0, PARAM_INT);
if (isset($class->gender)) {
    $defaultselectgender = $class->gender;
} else {
    $defaultselectgender = 0;
}

// Save class to database.
if (optional_param('saveinfo', false, PARAM_BOOL) && $classname && $classgender != -1 && confirm_sesskey()) {
    $class->name = $classname;
    $class->gender = $classgender;
    $class->agegroup = $classagegroup;
    $class->endyear = $classendyear;
    if (preg_match('/^[0-9]{4}\/[0-9]{2}$|^[0-9]{4}\/[0-9]{4}$/', $class->agegroup)) {
        if (strlen($class->agegroup) == 9) {
            $class->agegroup = substr($class->agegroup, 0, 4) . substr($class->agegroup, 7, 2);
        } else {
            $class->agegroup = str_replace('/', '', $class->agegroup);
        }
    } else {
        print_error('agegrouperror', 'local_fitcheck');
    }
    if (!$class->endyear < 9999 && !$class->endyear >= 1000) {
        print_error('endyearerror', 'local_fitcheck');
    }
    if ($class->id == -1) {
        unset($class->id);
        $class->teacherid = $USER->id;
        $class->testnr = 0;
        $class->status = 1;
        $id = $DB->insert_record('local_fitcheck_classes', $class);
        redirect('/local/fitcheck/settings/editclass.php?id=' . $id);
    } else {
        $DB->update_record('local_fitcheck_classes', $class);
    }
}

// Add student to class.
if (optional_param('add', false, PARAM_BOOL) && $unassignedstudentid && confirm_sesskey()) {
    $student = $DB->get_record('local_fitcheck_users', ['userid' => $unassignedstudentid]);
    if ($student) {
        $numbertests = count($DB->get_records_sql('SELECT DISTINCT testnr FROM {local_fitcheck_results}
            WHERE userid = ' . $student->userid));
        $student->classid = $id;
        $student->offset = $numbertests - $class->testnr;
        $DB->update_record('local_fitcheck_users', $student);
    } else {
        $student = new stdClass();
        $student->classid = $id;
        $student->userid = $unassignedstudentid;
        $student->offset = -($class->testnr);
        $DB->insert_record('local_fitcheck_users', $student);
    }
}

// Remove student from class.
if (optional_param('remove', false, PARAM_BOOL) && $assignedstudentid && confirm_sesskey()) {
    $student = $DB->get_record('local_fitcheck_users', ['userid' => $assignedstudentid]);
    $student->classid = null;
    $teststocheck = $DB->get_records('local_fitcheck_tests', ['gender' => $class->gender, 'status' => 1]);
    foreach ($teststocheck as $test) {
        $resulttocheck = $DB->get_record('local_fitcheck_results',
            ['testnr' => $class->testnr + $student->offset, 'userid' => $student->userid, 'testid' => $test->id]);
        if (!$resulttocheck) {
            $newresult = new stdClass();
            $newresult->result = null;
            $newresult->testnr = $class->testnr + $student->offset;
            $newresult->testid = $test->id;
            $newresult->userid = $student->userid;
            $DB->insert_record('local_fitcheck_results', $newresult);
        }
    }
    $DB->update_record('local_fitcheck_users', $student);
}

if ($class->agegroup) {
    $agegroup = substr($class->agegroup, 0, 4) . '/' . substr($class->agegroup, 4, 2);
} else {
    $agegroup = '';
}

// Display teacher name if class exists in database.
$teacherdiv = '';
if ($class->id > 0) {
    $teacher = $DB->get_record('user', ['id' => $class->teacherid]);
    $teacherdiv = html_writer::div(
        html_writer::div(html_writer::label(get_string('classteacher', 'local_fitcheck') . ': ', 'classteacher'),
            'col-md-3 col-form-label d-flex label-classteacher') .
        html_writer::div(
            html_writer::tag('input', '', [
                'type' => 'text', 'id' => 'classteacher', 'name' => 'classteacher', 'disabled' => '',
                'class' => 'form-control', 'size' => '10', 'value' => "$teacher->firstname $teacher->lastname"
            ]),
            'col-md-9 form-inline'), 'form-group row');
}
$classinfoform = html_writer::div(
    html_writer::div(html_writer::label(get_string('classname', 'local_fitcheck') . ': ', 'classname'),
        'col-md-3 col-form-label d-flex label-classname') .
    html_writer::div(
        html_writer::tag('input', '', [
            'type' => 'text', 'id' => 'classname', 'name' => 'classname',
            'class' => 'form-control', 'size' => '10', 'required' => '', 'value' => $class->name
        ]),
        'col-md-9 form-inline'), 'form-group row') .
    html_writer::div(
        html_writer::div(html_writer::label(get_string('classgender', 'local_fitcheck') . ': ', 'classgender'),
            'col-md-3 col-form-label d-flex label-classgender') .
        html_writer::div(
            html_writer::select([
                get_string('maleunisex', 'local_fitcheck'),
                get_string('female', 'local_fitcheck')
            ], 'classgender', $defaultselectgender, '', ['id' => 'classgender', 'class' => 'form-control', 'required' => '']),
            'col-md-9 form-inline'), 'form-group row') .
    html_writer::div(
        html_writer::div(html_writer::label(get_string('classagegroup', 'local_fitcheck') . ': ', 'classagegroup'),
            'col-md-3 col-form-label d-flex label-classagegroup') .
        html_writer::div(
            html_writer::tag('input', '', [
                'type' => 'text', 'id' => 'classagegroup', 'name' => 'classagegroup',
                'class' => 'form-control', 'size' => '10', 'required' => '', 'value' => $agegroup,
                'placeholder' => get_string('classagegroupexample', 'local_fitcheck')
            ]),
            'col-md-9 form-inline'), 'form-group row') .
    html_writer::div(
        html_writer::div(html_writer::label(get_string('classendyear', 'local_fitcheck') . ': ', 'classendyear'),
            'col-md-3 col-form-label d-flex label-classendyear') .
        html_writer::div(
            html_writer::tag('input', '', [
                'type' => 'text', 'id' => 'classendyear', 'name' => 'classendyear',
                'class' => 'form-control', 'size' => '10', 'required' => '', 'value' => $class->endyear,
                'placeholder' => get_string('classendyearexample', 'local_fitcheck')
            ]),
            'col-md-9 form-inline'), 'form-group row') .
    $teacherdiv .
    html_writer::div(
        html_writer::div('', 'col-md-3 col-form-label d-flex') .
        html_writer::div(
            html_writer::tag('input', '', ['type' => 'submit', 'value' => get_string('saveclassinfo', 'local_fitcheck'),
                'class' => 'btn btn-primary mr-1', 'id' => 'saveinfo', 'name' => 'saveinfo']) .
            html_writer::tag('a', get_string('gobacktoclasslist', 'local_fitcheck'),
                ['class' => 'btn btn-secondary', 'id' => 'goback', 'name' => 'back',
                'href' => new moodle_url('/local/fitcheck/settings/listclasses.php')]),
            'col-md-9'), 'form-group row'
    );

// Load classform if class exists in database.
if ($class->id != -1) {
    $classform = local_fitcheck_load_classform($class);
} else {
    $classform = html_writer::tag('p', get_string('createclasstounlock', 'local_fitcheck'));
}

// Prepare form.
$form = html_writer::tag('form', $classinfoform . $classform,
    ['action' => new moodle_url($PAGE->url, ['sesskey' => sesskey()]), 'method' => 'post', 'class' => 'fitcheck-classform']);

echo '<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
    integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>';
echo $OUTPUT->header();
echo $form;
echo $OUTPUT->footer();