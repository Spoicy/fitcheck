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
 * FitCheck Library
 *
 * @package    local_fitcheck
 * @copyright  2021 Jae Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Insert a new test into the database
 *
 * @param stdClass $test
 */
function local_fitcheck_create_test($test) {
    global $DB, $CFG;

    $context = context_system::instance();
    $editoroptions = array(
        'maxfiles'   => EDITOR_UNLIMITED_FILES,
        'maxbytes'   => $CFG->maxbytes,
        'trusttext'  => false,
        'forcehttps' => false,
        'context'    => $context
    );
    $manageroptions = array(
        'maxfiles' => 2,
        'accepted_types' => array('.mp4', '.mov', '.jpg', '.png')
    );

    if (!is_object($test)) {
        $test = (object) $test;
    }

    $test->fullname = trim($test->fullname);
    $test->shortname = trim($test->shortname);
    $test->resulttype1 = trim($test->resulttype1);
    $test->resulttype2 = trim($test->resulttype2);

    $testid = $DB->insert_record('local_fitcheck_tests', $test);
    $test->id = $testid;
    $test = file_postupdate_standard_editor($test, 'description', $editoroptions, $context,
        'local_fitcheck', 'attachment', $test->id * 10 + 1);
    $test = file_postupdate_standard_filemanager($test, 'video', $manageroptions, $context,
        'local_fitcheck', 'attachment', $test->id * 10 + 2);
    $DB->update_record('local_fitcheck_tests', $test);
}

/**
 * Update an existing test
 *
 * @param stdClass $test
 */
function local_fitcheck_update_test($test) {
    global $DB, $CFG;

    $context = context_system::instance();
    $editoroptions = array(
        'maxfiles'   => EDITOR_UNLIMITED_FILES,
        'maxbytes'   => $CFG->maxbytes,
        'trusttext'  => false,
        'forcehttps' => false,
        'context'    => $context
    );

    $manageroptions = array(
        'maxfiles' => 2,
        'accepted_types' => array('.mp4', '.mov', '.jpg', '.png')
    );

    if (!is_object($test)) {
        $test = (object) $test;
    }

    $test->fullname = trim($test->fullname);
    $test->shortname = trim($test->shortname);
    $test->resulttype1 = trim($test->resulttype1);
    $test->resulttype2 = trim($test->resulttype2);
    $test = file_postupdate_standard_editor($test, 'description', $editoroptions, $context,
        'local_fitcheck', 'attachment', $test->id * 10 + 1);
    $test = file_postupdate_standard_filemanager($test, 'video', $manageroptions, $context,
        'local_fitcheck', 'attachment', $test->id * 10 + 2);
    $DB->update_record('local_fitcheck_tests', $test);
}

/**
 * Retrieve an uploaded FitCheck file
 * Based on other _pluginfile functions.
 *
 * @category  files
 * @param stdClass $course course object
 * @param stdClass $cm block instance record
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool
 */
function local_fitcheck_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $DB, $CFG, $USER;

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        send_file_not_found();
    }
    require_login();

    if ($filearea !== 'attachment') {
        send_file_not_found();
    }

    $fs = get_file_storage();

    $filename = array_pop($args);
    $filepath = $args ? '/'.implode('/', $args).'/' : '/';

    if (!$file = $fs->get_file($context->id, 'local_fitcheck', 'attachment', $args[0], '/', $filename) or $file->is_directory()) {
        send_file_not_found();
    }

    \core\session\manager::write_close();
    send_stored_file($file, null, 0, $forcedownload, $options);
}

/**
 * Load the FitCheck class form
 *
 * @param stdClass $class fitcheck class object
 */
function local_fitcheck_load_classform($class) {
    global $DB, $OUTPUT;

    $html = '';

    // Fetch loading animation.
    $loading = $OUTPUT->image_url("i/loading", "core");

    // Prepare sql query segments.
    $availablestudentssqlbase = 'FROM {user} u WHERE u.id NOT IN
        (SELECT userid FROM {local_fitcheck_users} WHERE classid IS NOT NULL)' .
        ' AND u.id != ' . $class->teacherid;
    $classstudentssqlbase = "FROM {user} u INNER JOIN {local_fitcheck_users} lfu ON lfu.userid = u.id
        INNER JOIN {local_fitcheck_classes} lfc ON lfu.classid = lfc.id WHERE lfu.classid = $class->id";

    // Prepare sql queries.
    $availablestudentssql = 'SELECT u.id, u.username, u.firstname, u.lastname ' . $availablestudentssqlbase;
    $classstudentssql = 'SELECT u.id, u.username, u.firstname, u.lastname ' . $classstudentssqlbase;
    $remainingstudentssql = "SELECT u.id, u.username, u.firstname, u.lastname FROM {user} u
        WHERE u.id NOT IN (SELECT u.id $availablestudentssqlbase)
        AND u.id NOT IN (SELECT u.id $classstudentssqlbase) AND u.id != $class->teacherid";

    // Fetch students from database.
    $availablestudents = $DB->get_records_sql($availablestudentssql . ' ORDER BY u.firstname');
    $classstudents = $DB->get_records_sql($classstudentssql . ' ORDER BY u.firstname');
    $remainingstudents = $DB->get_records_sql($remainingstudentssql . ' ORDER BY u.firstname');

    // Prepare selects.
    $availableselect = '';
    $classselect = '';
    $remainingselect = '';

    // Prepare counts.
    $availablecount = 0;
    $classcount = 0;
    $remainingcount = 0;

    foreach ($availablestudents as $student) {
        if (!has_capability('local/fitcheck:editclasses', context_system::instance(), $student->id) && !isguestuser($student->id)) {
            $availableselect .= html_writer::tag('option', $student->firstname . " " .
                $student->lastname . " (" . $student->username . ")", ['value' => $student->id]);
            $availablecount++;
        }
    }
    foreach ($classstudents as $student) {
        if (!has_capability('local/fitcheck:editclasses', context_system::instance(), $student->id) && !isguestuser($student->id)) {
            $classselect .= html_writer::tag('option', $student->firstname . " " .
                $student->lastname . " (" . $student->username . ")", ['value' => $student->id]);
            $classcount++;
        }
    }
    foreach ($remainingstudents as $student) {
        if (!has_capability('local/fitcheck:editclasses', context_system::instance(), $student->id) && !isguestuser($student->id)) {
            $remainingselect .= html_writer::tag('option', $student->firstname . " " .
                $student->lastname . " (" . $student->username . ")", ['value' => $student->id]);
            $remainingcount++;
        }
    }
    $availableoptgroup = html_writer::tag('optgroup', $availableselect,
        ['label' => get_string('unassignedcount', 'local_fitcheck', $availablecount)]);
    $classoptgroup = html_writer::tag('optgroup', $classselect,
        ['label' => get_string('assignedcount', 'local_fitcheck', $classcount)]);
    $remainingoptgroup = html_writer::tag('optgroup', $remainingselect,
        ['label' => get_string('alrassignedcount', 'local_fitcheck', $remainingcount), 'disabled' => '']);

    // Create table cells for the selects and buttons.
    $assignedtd = html_writer::tag('td',
        html_writer::tag('p',
            html_writer::label(get_string('assigned', 'local_fitcheck'), 'assignedselect', true, ['class' => 'font-weight-bold'])
        ) .
        html_writer::div(
            html_writer::tag('select', $classoptgroup, [
                'multiple' => 'multiple', 'class' => 'form-control',
                'name' => 'assignedselect', 'id' => 'assignedselect', 'size' => '20', 'onchange' => 'enableAssigned()'])
        ) .
        html_writer::div(
            html_writer::label(get_string('search', 'local_fitcheck'), 'assignedselect_searchtext', true, ['class' => 'mr-1']) .
            html_writer::tag('input', '', ['type' => 'text', 'size' => '15', 'class' => 'form-control',
                'id' => 'assignedselect_searchtext', 'name' => 'assignedselect_searchtext', 'oninput' => 'searchAssigned()']) .
            html_writer::tag('input', '', ['type' => 'button', 'value' => get_string('clear', 'local_fitcheck'),
                'id' => 'assignedselect_cleartext', 'name' => 'assignedselect_cleartext',
                'class' => 'btn btn-secondary mx-1', 'onclick' => 'clearAssigned()']),
        'form-inline classsearch my-1'), ['id' => 'assignedcell']
    );
    $buttonstd = html_writer::tag('td',
        html_writer::div(
            html_writer::tag('input', '', [
                'id' => 'add', 'name' => 'add', 'type' => 'submit',
                'value' => get_string('add', 'local_fitcheck'), 'class' => 'btn btn-secondary', 'disabled' => ''
                ]), '', ['id' => 'addcontrols']
        ) .
        html_writer::div(
            html_writer::tag('input', '', [
                'id' => 'remove', 'name' => 'remove', 'type' => 'submit',
                'value' => get_string('remove', 'local_fitcheck'), 'class' => 'btn btn-secondary', 'disabled' => ''
                ]), '', ['id' => 'removecontrols']
        ), ['id' => 'buttonscell']
    );
    $notassignedtd = html_writer::tag('td',
        html_writer::tag('p',
            html_writer::label(get_string('notassigned', 'local_fitcheck'), 'unassignedselect', true,
                ['class' => 'font-weight-bold'])
        ) .
        html_writer::div(
            html_writer::tag('select', $availableoptgroup . $remainingoptgroup, [
                'multiple' => 'multiple', 'class' => 'form-control',
                'name' => 'unassignedselect', 'id' => 'unassignedselect', 'size' => '20', 'onChange' => 'enableUnassigned()'])
        ) .
        html_writer::div(
            html_writer::label(get_string('search', 'local_fitcheck'), 'unassignedselect_searchtext', true, ['class' => 'mr-1']) .
            html_writer::tag('input', '', ['type' => 'text', 'size' => '15', 'class' => 'form-control',
                'id' => 'unassignedselect_searchtext', 'name' => 'unassignedselect_searchtext',
                'oninput' => 'searchUnassigned()']) .
            html_writer::tag('input', '', ['type' => 'button', 'value' => get_string('clear', 'local_fitcheck'),
                'id' => 'unassignedselect_cleartext', 'name' => 'unassignedselect_cleartext',
                'class' => 'btn btn-secondary mx-1', 'onclick' => 'clearUnassigned()']),
        'form-inline classsearch my-1'), ['id' => 'unassignedcell']
    );

    // Prepare HTML for output with table and search scripts.
    $html = html_writer::tag('table',
        html_writer::tag('tbody', html_writer::tag('tr', $assignedtd . $buttonstd . $notassignedtd)),
            ['class' => 'fitcheck-classtable w-100']) .
        html_writer::script('
            var timer;
            function enableAssigned() {
                document.getElementById("remove").removeAttribute("disabled", true);
                console.log("test");
            }
            function enableUnassigned() {
                document.getElementById("add").removeAttribute("disabled", true);
                console.log("test");
            }
            function searchAssigned() {
                $("#assignedselect").css("background", "url('.$loading.') center center no-repeat");
                var variables = {
                    "search": $("#assignedselect_searchtext").val(),
                    "classid": "' . $class->id . '",
                    "teacherid": "' . $class->teacherid . '",
                    "mode": 0
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
                            $("select#assignedselect").html(data);
                            $("#assignedselect").css("background", "");
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            $("#assignedselect").css("background", "");
                        }
                    })
                }, 500);

            }
            function searchUnassigned() {
                $("#unassignedselect").css("background", "url('.$loading.') center center no-repeat");
                var variables = {
                    "search": $("#unassignedselect_searchtext").val(),
                    "classid": "' . $class->id . '",
                    "teacherid": "' . $class->teacherid . '",
                    "mode": 1
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
                            $("select#unassignedselect").html(data);
                            $("#unassignedselect").css("background", "");
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            $("#unassignedselect").css("background", "");
                        }
                    })
                }, 500);
            }
            function clearAssigned() {
                if ($("#assignedselect_searchtext").val()) {
                    $("#assignedselect_searchtext").val("");
                    searchAssigned();
                }
            }
            function clearUnassigned() {
                if ($("#unassignedselect_searchtext").val()) {
                    $("#unassignedselect_searchtext").val("");
                    searchUnassigned();
                }
            }
        ');

    return $html;
}

/**
 * Calculate a FitCheck grade
 *
 * @param stdClass $test test db object
 * @param float $data result data
 * @return float
 */
function local_fitcheck_calc_grade($test, $data) {
    if ($data == 'null') {
        return 'null';
    }
    $maxminrange = $test->maxresult - $test->minresult;
    if ($test->method != 2) {
        if ($test->minmax) {
            $calcresult = (($maxminrange - ($data - $test->minresult)) / $maxminrange) * 5 + 1;
        } else {
            $calcresult = (($data - $test->minresult) / $maxminrange) * 5 + 1;
        }
    } else {
        if ($test->minmax) {
            $calcresult = (($maxminrange - ((0 - $test->maxresult) + $data)) / $maxminrange) * 5 + 1;
        } else {
            $calcresult = (((0 - $test->minresult) + $data) / $maxminrange) * 5 + 1;
        }
    }
    if ($calcresult > 6) {
        $calcresult = 6;
    } else if ($calcresult < 1) {
        $calcresult = 1;
    }
    return round($calcresult, 2);
}

/**
 * Sort function for grades in class results
 *
 * @param array $a
 * @param array $b
 * @return int
 */
function local_fitcheck_sort_grades($a, $b) {
    if ($a['grade'] == $b['grade']) {
        return 0;
    }
    return ($a['grade'] < $b['grade']) ? -1 : 1;
}

/**
 * Sort function for results in class results
 *
 * @param array $a
 * @param array $b
 * @return int
 */
function local_fitcheck_sort_results($a, $b) {
    if ($a['result'] == $b['result']) {
        return 0;
    }
    return ($a['result'] < $b['result']) ? -1 : 1;
}