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
 * FitCheck Delete Results page
 *
 * @package    local_fitcheck
 * @copyright  2021 Jae Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/local/fitcheck/lib.php');
require_login();
require_capability('local/fitcheck:deleteusers', context_system::instance());

$PAGE->set_url(new moodle_url('/local/fitcheck/settings/deleteresults.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'local_fitcheck'));
$PAGE->set_heading(get_string('title', 'local_fitcheck'));
$PAGE->navbar->add('FitCheck', new moodle_url('/local/fitcheck'));
$PAGE->navbar->add(get_string('settings', 'local_fitcheck'), new moodle_url('/local/fitcheck/settings'));
$PAGE->navbar->add(get_string('deleteresults', 'local_fitcheck'));

$deleteagegroup = optional_param('deleteagegroup', false, PARAM_BOOL);
$deleteagegroupconfirm = optional_param('deleteagegroupconfirm', '', PARAM_ALPHANUM);
$deleteuser = optional_param('deleteuser', false, PARAM_BOOL);
$deleteuserconfirm = optional_param('deleteuserconfirm', '', PARAM_ALPHANUM);

$studentoptions = '';
$agegroupoptions = '';

$loading = $OUTPUT->image_url("i/loading", "core");

if ($deleteagegroup || $deleteagegroupconfirm) {
    if ($deleteagegroupconfirm != md5($deleteagegroup)) {
        echo $OUTPUT->header();
        $deleteagegroupselect = required_param('deleteagegroupselect', PARAM_INT);
        echo $OUTPUT->heading(get_string('confirmdeleteagegroup', 'local_fitcheck',
            substr($deleteagegroupselect, 0, 4) . '/' . substr($deleteagegroupselect, 4, 2)));
        $optionsyes = array('deleteagegroup' => $deleteagegroup, 'deleteagegroupconfirm' => md5($deleteagegroup),
            'sesskey' => sesskey(), 'deleteagegroupselect' => $deleteagegroupselect);
        $returnurl = new moodle_url('/local/fitcheck/settings/deleteresults.php');
        $deleteurl = new moodle_url($returnurl, $optionsyes);
        $deletebutton = new single_button($deleteurl, get_string('starttest', 'local_fitcheck'), 'post');

        echo $OUTPUT->confirm(get_string('confirmdeleteagegroupfull', 'local_fitcheck',
            substr($deleteagegroupselect, 0, 4) . '/' . substr($deleteagegroupselect, 4, 2)), $deletebutton, $returnurl);
        echo $OUTPUT->footer();
        die;
    } else {
        $todelete = required_param('deleteagegroupselect', PARAM_INT);
        $deleteclasses = $DB->get_records('local_fitcheck_classes', ['agegroup' => $todelete]);
        foreach ($deleteclasses as $class) {
            $deletestudents = $DB->get_records('local_fitcheck_users', ['classid' => $class->id]);
            foreach ($deletestudents as $student) {
                $delete = $DB->delete_records('local_fitcheck_results', ['userid' => $student->userid]);
                $student->classid = null;
                $DB->update_record('local_fitcheck_users', $student);
            }
            $class->status = 0;
            $DB->update_record('local_fitcheck_classes', $class);
        }
    }
} else if ($deleteuser || $deleteuserconfirm) {
    if ($deleteuserconfirm != md5($deleteuser)) {
        echo $OUTPUT->header();
        $deleteusersselect = required_param('deleteusersselect', PARAM_INT);
        $user = $DB->get_record('user', ['id' => $deleteusersselect]);
        echo $OUTPUT->heading(get_string('confirmdeleteuser', 'local_fitcheck', fullname($user)));
        $optionsyes = array('deleteuser' => $deleteuser, 'deleteuserconfirm' => md5($deleteuser),
            'sesskey' => sesskey(), 'deleteusersselect' => $deleteusersselect);
        $returnurl = new moodle_url('/local/fitcheck/settings/deleteresults.php');
        $deleteurl = new moodle_url($returnurl, $optionsyes);
        $deletebutton = new single_button($deleteurl, get_string('starttest', 'local_fitcheck'), 'post');

        echo $OUTPUT->confirm(get_string('confirmdeleteuserfull', 'local_fitcheck', fullname($user)), $deletebutton, $returnurl);
        echo $OUTPUT->footer();
        die;
    } else {
        $todelete = required_param('deleteusersselect', PARAM_INT);
        $delete = $DB->delete_records('local_fitcheck_results', ['userid' => $todelete]);
        $deletedstudent = $DB->get_record('local_fitcheck_users', ['userid' => $todelete]);
        $deletedstudent->classid = null;
        $DB->update_record('local_fitcheck_users', $deletedstudent);
    }
}

$students = $DB->get_records_sql('SELECT u.* FROM {user} u ORDER BY u.firstname');

$agegroups = $DB->get_records_sql('SELECT DISTINCT lfc.agegroup FROM {local_fitcheck_classes} lfc
    WHERE lfc.status = 1 AND lfc.agegroup IS NOT NULL ORDER BY lfc.agegroup ASC');

foreach ($students as $student) {
    $classidcheck = $DB->get_record('local_fitcheck_users', ['userid' => $student->id]);
    if (!$classidcheck) {
        $classidcheck = null;
    } else {
        $classidcheck = $classidcheck->classid;
    }
    if (count($DB->get_records('local_fitcheck_results', ['userid' => $student->id])) || $classidcheck) {
        $studentoptions .= html_writer::tag('option', fullname($student) . " ($student->username)",
            ['value' => $student->id]);
    }
}

foreach ($agegroups as $agegroup) {
    $agegroupoptions .= html_writer::tag('option',
        substr($agegroup->agegroup, 0, 4) . '/' . substr($agegroup->agegroup, 4, 2),
        ['value' => $agegroup->agegroup]);
}

$selectagegroups = html_writer::tag('select', $agegroupoptions,
    ['class' => 'form-control w-50 mx-auto', 'id' => 'deleteagegroupselect', 'name' => 'deleteagegroupselect']);
$selectstudents = html_writer::tag('select', $studentoptions,
    ['multiple' => 'multiple', 'class' => 'form-control', 'id' => 'deleteusersselect',
    'name' => 'deleteusersselect', 'size' => '15']);

$html = html_writer::div(
    html_writer::div(html_writer::tag('h4', get_string('agegrouptitle', 'local_fitcheck'),
        ['class' => 'font-weight-bold text-center']), 'col-md-6') .
    html_writer::div(html_writer::tag('h4', get_string('studentstitle', 'local_fitcheck'),
        ['class' => 'font-weight-bold text-center']), 'col-md-6')
    , 'row d-none d-md-flex') . 
    html_writer::div(
    html_writer::div(
        html_writer::div(
            $selectagegroups .
            html_writer::tag('button', get_string('deleteagegroupresults', 'local_fitcheck'),
                ['class' => 'btn btn-danger w-50 mt-2 mx-auto d-block', 'type' => 'submit',
                    'id' => 'deleteagegroup', 'name' => 'deleteagegroup', 'value' => 1])),
        'col-md-6 my-auto pb-4') .
    html_writer::div(
        html_writer::div($selectstudents, 'background-select-container') .
        html_writer::div(
            html_writer::label(get_string('search', 'local_fitcheck'), 'deleteusers_searchtext', true, ['class' => 'mr-1']) .
            html_writer::tag('input', '', ['type' => 'text', 'size' => '15', 'class' => 'form-control',
                'id' => 'deleteusers_searchtext', 'name' => 'deleteusers_searchtext',
                'oninput' => 'searchUsers()']) .
            html_writer::tag('input', '', ['type' => 'button', 'value' => get_string('clear', 'local_fitcheck'),
                'id' => 'deleteusers_cleartext', 'name' => 'deleteusers_cleartext',
                'class' => 'btn btn-secondary mx-1', 'onclick' => 'clearUsers()']),
            'form-inline classsearch my-1') .
        html_writer::tag('button', get_string('deleteuserresults', 'local_fitcheck'),
            ['class' => 'btn btn-danger w-100', 'type' => 'submit',
                'id' => 'deleteuser', 'name' => 'deleteuser', 'value' => 1])
    , 'col-md-6 justify-content-center'), 'row');

echo $OUTPUT->header();
//echo $html;
echo html_writer::tag('form', $html, ['method' => 'get', 'action' => '#', 'name' => 'selectForm']);
echo html_writer::script('
    var timer;
    function searchUsers() {
        $("#deleteusersselect").prop("disabled", true);
        $("#deleteusersselect").css("background", "url('.$loading.') center center no-repeat");
        var variables = {
            "pagemode": 1,
            "search": $("#deleteusers_searchtext").val(),
        }
        var data = JSON.stringify(variables);
        clearTimeout(timer);
        timer = setTimeout(function() {
            $.ajax({
                url: "../ajax/search.php",
                type: "POST",
                data: {
                    "data": data
                },
                success: function(data) {
                    $("select#deleteusersselect").html(data);
                    $("#deleteusersselect").css("background", "");
                    $("#deleteusersselect").prop("disabled", false);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $("#deleteusersselect").css("background", "");
                    document.getElementById("remove").removeAttribute("disabled", true);
                    $("#deleteusersselect").prop("disabled", false);
                }
            })
        }, 500);
    }
    function clearUsers() {
        if ($("#deleteusers_searchtext").val()) {
            $("#deleteusers_searchtext").val("");
            searchUsers();
        }
    }
');
echo $OUTPUT->footer();