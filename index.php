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
 * The FitCheck page.
 *
 * @package    local_fitcheck
 * @copyright  2021 Jae Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_login();
!isguestuser($USER->id) || print_error('noguest');

// Set page values.
$PAGE->set_url(new moodle_url('/local/fitcheck/'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'local_fitcheck'));
$PAGE->set_heading(get_string('title', 'local_fitcheck'));
$PAGE->navbar->add('FitCheck');

// Get tests and user pref.
$pref = $DB->get_record_sql('SELECT lfc.gender FROM {local_fitcheck_classes} lfc 
    INNER JOIN  {local_fitcheck_users} lfu 
    ON lfc.id = lfu.classid
    WHERE lfu.userid = ' . $USER->id);
if (!$pref) {
    if (!has_capability('local/fitcheck:edittests', $PAGE->context)) {
        print_error('notassignederror', 'local_fitcheck');
    }
    $pref = new stdClass();
    $pref->gender = 2;
}
$testswhere = 'WHERE status = 1';
if ($pref->gender != 2) {
    $testswhere .= ' AND gender = ' . $pref->gender;
}
$tests = $DB->get_records_sql('SELECT * FROM {local_fitcheck_tests} ' . $testswhere . ' ORDER BY gender');
$html = '';
$cards = '';
$i = 0;
$fs = get_file_storage();
$filesmask = ['.png', '.jpg'];
$placeholder = $OUTPUT->image_url('i/messagecontentimage', 'core');

// Create cards for each test.
foreach ($tests as $test) {
    $files = $fs->get_area_files($PAGE->context->id, 'local_fitcheck', 'attachment', $test->id * 10 + 2);

    $thumbnail = '';
    foreach ($files as $file) {
        if (in_array(substr($file->get_filename(), -4), $filesmask)) {
            $thumbnail = $file;
        }
    }
    $fileitemid = $test->id * 10 + 2;
    $thumbnailhtml = '';
    if ($thumbnail) {
        $thumbnailhtml = html_writer::div(
            html_writer::img('/pluginfile.php/1/local_fitcheck/attachment/' . $fileitemid . '/' . $thumbnail->get_filename(), 'Thumbnail', ['width' => '100%']),
            'card-img-top thumbnail-real');
    } else {
        $thumbnailhtml = html_writer::div(html_writer::img($placeholder, 'Thumbnail'),
        'card-img-top thumbnail-empty');
    }
    $genderspan = '';
    if ($pref->gender == 2) {
        if ($test->gender) {
            $genderspan = html_writer::span('('.get_string('female', 'local_fitcheck').')');
        } else {
            $genderspan = html_writer::span('('.get_string('maleunisex', 'local_fitcheck').')');
        }
    }
    $buttons = html_writer::tag('a', get_string('gototest', 'local_fitcheck'), [
        'href' => $PAGE->url . "test.php?id=$test->id",
        'class' => 'btn btn-primary'
    ]);
    if (has_capability('local/fitcheck:edittests', $PAGE->context)) {
        $buttons .= html_writer::tag('a', get_string('gototestsettings', 'local_fitcheck'), [
            'href' => $PAGE->url . "settings/edittests.php?id=$test->id",
            'class' => 'btn btn-secondary'
        ]);
    }
    $card = html_writer::div(
        $thumbnailhtml .
        html_writer::div(
            $genderspan .
            html_writer::tag('h5', $test->fullname, ['class' => 'card-title']) .
            $buttons, 'card-body'
        ), 'card');
    $cards .= $card;
    if ($i % 2 == 0 && $i != 0) {
        $html .= html_writer::div($cards, 'card-deck mb-1');
        $cards = '';
        $i = 0;
    } else {
        $i++;
    }
}
if ($i != 0) {
    $blankwidth = (3 - $i) * 33.33;
    $cards .= html_writer::div('', 'blank-card', ['style' => 'width: ' . $blankwidth . '%;']);
}
$html .= html_writer::div($cards, 'card-deck');
$html .= html_writer::script('
    $(document).ready(function() {
        console.log("setting height");
        $(".thumbnail-empty").css("height", 0);
        $(".thumbnail-empty img").css("height", 0);
    });
    $(window).on("load", function() {
        $(".thumbnail-empty").css("height", $(".thumbnail-real").height());
        $(".thumbnail-empty img").css("height", "60%");
    });
    $(window).resize(function() {
        $(".thumbnail-empty").css("height", $(".thumbnail-real").height());
    });
');

// Output the page.
echo '<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>';
echo $OUTPUT->header();
echo $html;
echo $OUTPUT->footer();