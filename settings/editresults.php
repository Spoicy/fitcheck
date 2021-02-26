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
 * FitCheck result correction form
 *
 * @package    local_fitcheck
 * @copyright  2021 Jae Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/local/fitcheck/lib.php');
require_login();
require_capability('local/fitcheck:editresults', context_system::instance());

$id = optional_param('id', -1, PARAM_INT);
$idcheck = optional_param('id', -1, PARAM_RAW);

$PAGE->set_url(new moodle_url('/local/fitcheck/settings/editresults.php', array('id' => $id)));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'local_fitcheck'));
$PAGE->set_heading(get_string('title', 'local_fitcheck'));
$PAGE->navbar->add('FitCheck', new moodle_url('/local/fitcheck'));
$PAGE->navbar->add(get_string('settings', 'local_fitcheck'), new moodle_url('/local/fitcheck/settings'));
$PAGE->navbar->add(get_string('listclasses', 'local_fitcheck'), new moodle_url('/local/fitcheck/settings/listclasses.php'));

$student = $DB->get_record('local_fitcheck_users', ['id' => $id]);
$studentinfo = $DB->get_record('user', ['id' => $student->userid]);
$class = $DB->get_record('local_fitcheck_classes', ['id' => $student->classid]);
$tests = $DB->get_records('local_fitcheck_tests', ['gender' => $class->gender, 'status' => 1]);
//$results = $DB->get_records('local_fitcheck_results', ['userid' => $student->userid]);

$PAGE->navbar->add(fullname($studentinfo, true));
$table = new html_table();
$table->head[] = ' ';
foreach ($tests as $test) {
    $table->head[] = trim(mb_substr($test->shortname, 0, 5, 'utf-8'));
}

$testnr = $student->offset + $class->testnr;

for ($i = 1; $i <= $testnr; $i++) {
    $row = array();
    $results = $DB->get_records('local_fitcheck_results', ['userid' => $student->userid, 'testnr' => $i], 'testid ASC');
    $row[] = html_writer::tag('b', get_string('testnumber', 'local_fitcheck', $i));
    foreach ($results as $result) {
        $resultcell = new html_table_cell($result->result);
        $resultcell->attributes['contenteditable'] = "true";
        $resultcell->id = $result->testid . '_' . $i;
        $row[] = $resultcell;
    }
    $table->data[] = $row;
}

//$table->data[] = '';
echo '<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
    integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>';
echo $OUTPUT->header();
echo html_writer::table($table);
echo html_writer::script('
    $(document).ready(function() {
        $(".cell").on("input", function(event) {
            $("#" + event.target.id).addClass("editedcell");
            console.log(event.target.id);
        });
    });
');
echo $OUTPUT->footer();