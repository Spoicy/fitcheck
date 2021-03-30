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
 * FitCheck Edit/Add Tests form
 *
 * @package    local_fitcheck
 * @copyright  2021 Jae Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/local/fitcheck/classes/edittests_form.php');
require_once($CFG->dirroot.'/local/fitcheck/lib.php');
require_once($CFG->libdir.'/filelib.php');
require_login();


$id = optional_param('id', -1, PARAM_INT);
$idcheck = optional_param('id', -1, PARAM_RAW);

if (!is_numeric($idcheck)) {
    print_error('iderror', 'local_fitcheck');
}

// Set page values.
$PAGE->set_url(new moodle_url('/local/fitcheck/settings/edittests.php'), array("id" => $id));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'local_fitcheck'));
$PAGE->set_heading(get_string('title', 'local_fitcheck'));
require_capability('local/fitcheck:edittests', $PAGE->context);
$PAGE->navbar->add('FitCheck', new moodle_url('/local/fitcheck'));
$PAGE->navbar->add(get_string('settings', 'local_fitcheck'), new moodle_url('/local/fitcheck/settings'));

// Set additional values.
$editoroptions = array(
    'maxfiles'   => EDITOR_UNLIMITED_FILES,
    'maxbytes'   => $CFG->maxbytes,
    'trusttext'  => false,
    'forcehttps' => false,
    'context'    => context_system::instance()
);
$manageroptions = array(
    'maxfiles' => 2,
    'accepted_types' => array('.mp4', '.mov', '.jpg', '.png')
);
$returnurl = new moodle_url('/local/fitcheck/settings/listtests.php');

$trailingzerosfields = ['step', 'maxresult', 'minresult'];

// Check if test is new or existing.
if ($id < 1) {
    $test = new stdClass();
    $test->id = $id = -1;
    $PAGE->navbar->add(get_string('newtest', 'local_fitcheck'));
} else {
    $test = $DB->get_record('local_fitcheck_tests', array('id' => $id), '*', MUST_EXIST);
    $PAGE->navbar->add($test->shortname);
    // Remove trailing zeros from decimal fields.
    foreach ($trailingzerosfields as $field) {
        $pos = strpos($test->$field, '.');
        if ($pos !== false) {
            $test->$field = rtrim(rtrim($test->$field, '0'), '.');
        }
    }
}

// Prepare HTML-Editor.
if ($test->id !== -1) {
    $test->descriptionformat = FORMAT_HTML;
    $test = file_prepare_standard_editor($test, 'description', $editoroptions, $PAGE->context,
        'local_fitcheck', 'attachment', $test->id * 10 + 1);
    $test = file_prepare_standard_filemanager($test, 'video', $manageroptions, $PAGE->context,
        'local_fitcheck', 'attachment', $test->id * 10 + 2);
}

$testform = new local_fitcheck_edittests_form(new moodle_url($PAGE->url), array('test' => $test));

// Deal with form submission.
if ($testform->is_cancelled()) {
    redirect($returnurl);
} else if ($testnew = $testform->get_data()) {
    $testcreated = false;
    $testnew->id = $id;
    if ($testnew->id == -1) {
        unset($testnew->id);
        $testnewid = local_fitcheck_create_test($testnew);
        $testnew->id = $testnewid;
        $testsave = file_save_draft_area_files($testnew->video_filemanager, $PAGE->context->id,
            'local_fitcheck', 'attachment', $testnew->id * 10 + 2,
            array('maxbytes' => $CFG->maxbytes, 'maxfiles' => 2));
        local_fitcheck_update_test($testnew);
    } else {
        $testsave = file_save_draft_area_files($testnew->video_filemanager, $PAGE->context->id,
            'local_fitcheck', 'attachment', $testnew->id * 10 + 2,
            array('maxbytes' => $CFG->maxbytes, 'maxfiles' => 2));
        local_fitcheck_update_test($testnew);
    }
    redirect($returnurl);
}

// Output HTML.
echo $OUTPUT->header();
$testform->display();
echo $OUTPUT->footer();