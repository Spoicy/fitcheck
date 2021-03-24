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
 * The FitCheck individual test page.
 *
 * @package    local_fitcheck
 * @copyright  2021 Jae Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filebrowser/file_browser.php');
require_once($CFG->dirroot . '/local/fitcheck/classes/edittests_form.php');
require_login();
!isguestuser($USER->id) || print_error('noguest');

$id = required_param('id', PARAM_INT);
$result = optional_param('result', -100000, PARAM_FLOAT);
$test = $DB->get_record('local_fitcheck_tests', ['id' => $id]);
if (!has_capability('local/fitcheck:edittests', context_system::instance())) {
    $student = $DB->get_record('local_fitcheck_users', ['userid' => $USER->id]);
    if ($student) {
        $class = $DB->get_record('local_fitcheck_classes', ['id' => $student->classid]);
        if (!$class) {
            print_error('notassignederror', 'local_fitcheck');
        } else if ($test->gender != $class->gender) {
            print_error('accessdenied', 'admin');
        }
    } else {
        print_error('notassignederror', 'local_fitcheck');
    }
}
$mainpage = new moodle_url('/local/fitcheck/');


// Set page values.
$PAGE->set_url(new moodle_url('/local/fitcheck/test.php?id='.$id));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'local_fitcheck') . ' - ' . $test->fullname);
$PAGE->set_heading(get_string('title', 'local_fitcheck'));
$PAGE->navbar->add('FitCheck', $mainpage);
$PAGE->navbar->add($test->shortname);

if ($test->status == 0) {
    require_capability('local/fitcheck:viewdisabledtests', $PAGE->context);
}

$returnurl = new moodle_url('/local/fitcheck');
$urlwithsess = new moodle_url($PAGE->url, ['sesskey' => sesskey()]);
$resulterror = '';

if ((($result != -100000 && $result >= 0) || ($test->method == 2 && $result != -100000)) &&
        !$DB->get_record('local_fitcheck_results', ['testid' => $id, 'userid' => $student->userid, 'testnr' => $class->testnr + $student->offset])) {
    $student = $DB->get_record('local_fitcheck_users', ['userid' => $USER->id]);
    $class = $DB->get_record('local_fitcheck_classes', ['id' => $student->classid]);
    $resulttoadd = new stdClass();
    $resulttoadd->result = $result;
    $resulttoadd->testid = $id;
    $resulttoadd->userid = $student->userid;
    $resulttoadd->testnr = $class->testnr + $student->offset;
    require_sesskey();
    $DB->insert_record('local_fitcheck_results', $resulttoadd);
    redirect($returnurl);
} else if ($result != -100000) {
    $resulterror = html_writer::tag('p', get_string('wrongresult', 'local_fitcheck'), ['class' => 'mb-1', 'style' => 'color:red;']);
}

// Prepare test html.
$html = html_writer::tag('h2', $test->fullname, ['class' => 'fitcheck-test-title', 'style' => 'font-weight: 400;']);
$html .= file_rewrite_pluginfile_urls($test->description, 'pluginfile.php', $PAGE->context->id,
    'local_fitcheck', 'attachment', $test->id * 10 + 1);
$fs = get_file_storage();
$filesmask = ['.mov', '.mp4'];
$filesitemid = $test->id * 10 + 2;
$files = $fs->get_area_files($PAGE->context->id, 'local_fitcheck', 'attachment', $filesitemid);
$video = '';

foreach ($files as $file) {
    if (in_array(strtolower(substr($file->get_filename(), -4)), $filesmask)) {
        $video = html_writer::tag('h4', get_string('videoheader', 'local_fitcheck')) .
            html_writer::tag('video',
                html_writer::start_tag('source',
                    ['src' => '/pluginfile.php/1/local_fitcheck/attachment/' . $filesitemid . '/' . $file->get_filename()]),
                ['controls' => '', 'class' => 'examplevideo w-75 mb-4 mt-2']);
    }
}

// Prepare form, not using moodleform for custom styling.
if ($test->resulttype1 && $test->resulttype2) {
    $method = '';
    if ($test->method == 1) {
        $method = 'minusCalc()';
        $transstring = 'testresultminus';
    } else {
        $method = 'averageCalc()';
        $transstring = 'testresultaverage';
    }

    $formelements = html_writer::label($test->resulttype1 . ':', 'result1') .
        html_writer::tag('input', '', [
            'type' => 'number',
            'name' => 'result1',
            'id' => 'result1',
            'placeholder' => '0',
            'class' => 'mb-4 form-control w-25',
            'onchange' => $method,
            'onkeyup' => $method,
            'required' => '',
            'step' => $test->step
            ]) .
        html_writer::label($test->resulttype2 . ':', 'result2') .
        html_writer::tag('input', '', [
            'type' => 'number',
            'name' => 'result2',
            'id' => 'result2',
            'placeholder' => '0',
            'class' => 'mb-4 form-control w-25',
            'onchange' => $method,
            'onkeyup' => $method,
            'required' => '',
            'step' => $test->step
            ]) .
        html_writer::label(get_string($transstring, 'local_fitcheck'), 'result') .
        $resulterror .
        html_writer::tag('input', '', [
            'type' => 'number',
            'name' => 'result',
            'id' => 'result',
            'placeholder' => '0',
            'class' => 'mb-4 form-control w-25',
            'readonly' => '',
            'step' => $test->step
            ]);
} else {
    $placeholder = '0';
    if ($test->method == 2) {
        $placeholder = '+/- 0';
    }
    $formelements = html_writer::label(get_string('testresult', 'local_fitcheck'), 'result') .
        $resulterror .
        html_writer::tag('input', '', [
            'type' => 'number',
            'name' => 'result',
            'id' => 'result',
            'placeholder' => $placeholder,
            'class' => 'mb-4 form-control w-25',
            'required' => '',
            'step' => $test->step
            ]);
}
$editurl = new moodle_url('/local/fitcheck/settings/edittests.php', ['id' => $id]);
$editbutton = '';
if (has_capability('local/fitcheck:edittests', $PAGE->context)) {
    $editbutton = html_writer::tag('a', get_string('gototestsettings', 'local_fitcheck'),
        ['href' => $editurl, 'class' => 'btn btn-secondary mr-1']);
}
$formelements .= html_writer::label(get_string('checkresult', 'local_fitcheck'), 'checkresult') . '<br>' .
    html_writer::checkbox('checkresult', 1, false, '', ['class' => 'mb-4', 'required' => '']) . '<br>' .
    html_writer::tag('button', get_string('submit', 'local_fitcheck'), ['type' => 'submit', 'class' => 'btn btn-primary mr-1']) .
    $editbutton .
    html_writer::tag('a', get_string('gobacktomainpage', 'local_fitcheck'),
        ['href' => $mainpage, 'class' => 'btn btn-secondary mr-1']);
$form = html_writer::tag('form', $formelements, ['action' => $urlwithsess, 'method' => 'post', 'class' => 'fitcheck-resultform']);

// Output page.
echo $OUTPUT->header();
echo html_writer::div($html, 'fitcheck-test');
echo html_writer::script('
    var step = ' . $test->step . ';
    function averageCalc() {
        var x = Number(document.getElementById("result1").value);
        var y = Number(document.getElementById("result2").value);
        if (x != 0 && y != 0) {
            var value = (x + y) / 2;
            var stepcheck = value % step;
            if (stepcheck / step >= 0.499) {
                value = value + (step - stepcheck);
            }
            document.getElementById("result").value = value;
        }
    }

    function minusCalc() {
        var x = Number(document.getElementById("result1").value);
        var y = Number(document.getElementById("result2").value);
        if (x != 0 && y != 0) {
            document.getElementById("result").value = x - y;
        }
    }
');
if ($video) {
    echo $video;
}

$student = $DB->get_record('local_fitcheck_users', ['userid' => $USER->id]);
$resulttocheck = null;
if ($student) {
    $class = $DB->get_record('local_fitcheck_classes', ['id' => $student->classid]);
    if ($class && $class->testnr + $student->offset) {
        $resulttocheck = $DB->get_record('local_fitcheck_results',
            ['testid' => $test->id, 'userid' => $student->userid, 'testnr' => $class->testnr + $student->offset]);
    } else if ($class->testnr + $student->offset == 0) {
        $resulttocheck = true;
    }
}

if (!has_capability('local/fitcheck:edittests', context_system::instance()) && !$resulttocheck) {
    echo $form;
} else if (has_capability('local/fitcheck:edittests', context_system::instance())) {
    echo '<br>' . $editbutton .
        html_writer::tag('a', get_string('gobacktomainpage', 'local_fitcheck'),
            ['href' => $mainpage, 'class' => 'btn btn-secondary mr-1']) .
        html_writer::tag('a', get_string('gobacktotestlist', 'local_fitcheck'),
            ['href' => new moodle_url('/local/fitcheck/settings/listtests.php'), 'class' => 'btn btn-secondary mr-1']);
} else if ($resulttocheck) {
    echo html_writer::tag('p', get_string('alreadysubmittedresult', 'local_fitcheck')) .
        html_writer::tag('a', get_string('gobacktomainpage', 'local_fitcheck'),
            ['href' => $mainpage, 'class' => 'btn btn-secondary mr-1']);
}
echo $OUTPUT->footer();