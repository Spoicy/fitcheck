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
require_once($CFG->dirroot.'/local/fitcheck/lib.php');
require_login();
require_capability('local/fitcheck:viewallresults', context_system::instance());

$id = required_param('id', PARAM_INT);
$sort = optional_param('sort', '', PARAM_TEXT);
$dir = optional_param('dir', '', PARAM_TEXT);
$view = optional_param('view', 0, PARAM_INT);
$newtest = optional_param('newtest', false, PARAM_BOOL);
$newtestconfirm = optional_param('newtestconfirm', '', PARAM_ALPHANUM);

$PAGE->set_url(new moodle_url('/local/fitcheck/classresults.php?id=' . $id));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'local_fitcheck') . ' - ' . get_string('classresults', 'local_fitcheck'));
$PAGE->set_heading(get_string('title', 'local_fitcheck'));
$PAGE->navbar->add('FitCheck');
$PAGE->navbar->add(get_string('classresults', 'local_fitcheck'));

// Set direction for selected option.
if ($dir == 'asc') {
    $sortdir = 'desc';
} else {
    $sortdir = 'asc';
}

// Fetch class and tests from DB.
$class = $DB->get_record('local_fitcheck_classes', ['id' => $id]);
$tests = $DB->get_records('local_fitcheck_tests', ['status' => 1, 'gender' => $class->gender]);

if ($class->teacherid != $USER->id && !has_capability('local/fitcheck:deleteresults', context_system::instance())) {
    print_error('accessdenied', 'admin');
}

// Fetch students.
$students = $DB->get_records_sql('SELECT lfu.userid, u.firstname, u.lastname, lfu.id, lfu.offset, lfu.userid FROM {user} u,
    {local_fitcheck_users} lfu WHERE classid = ' . $id .
    ' AND u.id = lfu.userid');
$completecheck = 1;

// Increase the test number if new test is pressed.
if ($newtest || $newtestconfirm) {
    foreach ($tests as $test) {
        foreach ($students as $student) {
            $resulttocheck = $DB->get_record('local_fitcheck_results',
                ['testid' => $test->id, 'userid' => $student->userid, 'testnr' => $class->testnr + $student->offset]);
            if (!$resulttocheck) {
                $completecheck = 0;
            }
        }
    }
    if ($completecheck) {
        $class->testnr++;
        $DB->update_record('local_fitcheck_classes', $class);
    } else {
        if ($newtestconfirm != md5($newtest)) {
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('confirmstartnewtest', 'local_fitcheck'));
            $optionsyes = array('newtest' => $newtest, 'newtestconfirm' => md5($newtest), 'sesskey' => sesskey());
            $returnurl = new moodle_url('/local/fitcheck/classresults.php?id=' . $id);
            $deleteurl = new moodle_url($returnurl, $optionsyes);
            $deletebutton = new single_button($deleteurl, get_string('starttest', 'local_fitcheck'), 'post');

            echo $OUTPUT->confirm(get_string('confirmstartnewtestfull', 'local_fitcheck'), $deletebutton, $returnurl);
            echo $OUTPUT->footer();
            die;
        } else {
            foreach ($tests as $test) {
                foreach ($students as $student) {
                    if ($class->testnr + $student->offset != 0) {
                        $resulttocheck = $DB->get_record('local_fitcheck_results',
                            ['testid' => $test->id, 'userid' => $student->userid, 'testnr' => $class->testnr + $student->offset]);
                        if (!$resulttocheck) {
                            $newresult = new stdClass();
                            $newresult->result = null;
                            $newresult->testnr = $class->testnr + $student->offset;
                            $newresult->userid = $student->userid;
                            $newresult->testid = $test->id;
                            $DB->insert_record('local_fitcheck_results', $newresult);
                        }
                    }
                }
            }
            $class->testnr++;
            $DB->update_record('local_fitcheck_classes', $class);
        }
    }
}

// Create select options.
if ($view == 0) {
    $selectoptions = html_writer::tag('option', get_string('average', 'local_fitcheck'), ['value' => 'average', 'selected' => '']);
    $currenttest = new stdClass();
    $currenttest->id = 0;
    $currenttest->method = 0;
} else {
    $selectoptions = html_writer::tag('option', get_string('average', 'local_fitcheck'), ['value' => 'average']);
}
foreach ($tests as $test) {
    if ($view == $test->id) {
        $selectoptions .= html_writer::tag('option', $test->shortname, ['value' => $test->id, 'selected' => '']);
        $currenttest = $test;
    } else {
        $selectoptions .= html_writer::tag('option', $test->shortname, ['value' => $test->id]);
    }
}
$select = html_writer::tag('select', $selectoptions,
    ['name' => 'view', 'id' => 'view', 'class' => 'select custom-select mb-3', 'onchange' => 'selectForm.submit();']);

// Setup table.
$table = new html_table();
$table->head = array();
$table->colclasses = array();

// Display different table layout if view is average or test.
if ($view != 0) {
    $tableheaders = array('studentfirstname', 'studentlastname', 'result', 'grade');
    switch ($sort) {
        case "studentfirstname":
            $studentfirstname = html_writer::tag('a', get_string('liststudentfirstname', 'local_fitcheck'),
                ['href' => $PAGE->url . '?id=' . $id . '&view=' . $view . '&sort=studentfirstname&dir=' . $sortdir]) .
                $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
            unset($tableheaders[0]);
            $sqlsort = "ORDER BY u.lastname $dir";
            break;
        case "studentlastname":
            $studentlastname = html_writer::tag('a', get_string('liststudentlastname', 'local_fitcheck'),
                ['href' => $PAGE->url . '?id=' . $id . '&view=' . $view . '&sort=studentlastname&dir=' . $sortdir]) .
                $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
            unset($tableheaders[1]);
            $sqlsort = "ORDER BY u.firstname $dir";
            break;
        case "result":
            $result = html_writer::tag('a', get_string('listresult', 'local_fitcheck'),
                ['href' => $PAGE->url . '?id=' . $id . '&view=' . $view . '&sort=result&dir=' . $sortdir]) .
                $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
            unset($tableheaders[2]);
            $sqlsort = '';
            $resultsort = $dir;
            break;
        case "grade":
            $grade = html_writer::tag('a', get_string('listgrade', 'local_fitcheck'),
                ['href' => $PAGE->url . '?id=' . $id . '&view=' . $view . '&sort=grade&dir=' . $sortdir]) .
                $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
            unset($tableheaders[3]);
            $sqlsort = '';
            $gradesort = $dir;
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
            $sqlsort = "ORDER BY u.firstname $dir";
            break;
        case "studentlastname":
            $studentlastname = html_writer::tag('a', get_string('liststudentlastname', 'local_fitcheck'),
                ['href' => $PAGE->url . '?id=' . $id . '&view=' . $view . '&sort=studentlastname&dir=' . $sortdir]) .
                $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
            unset($tableheaders[1]);
            $sqlsort = "ORDER BY u.lastname $dir";
            break;
        case "grade":
            $grade = html_writer::tag('a', get_string('listgrade', 'local_fitcheck'),
                ['href' => $PAGE->url . '?id=' . $id . '&view=' . $view . '&sort=grade&dir=' . $sortdir]) .
                $OUTPUT->pix_icon('t/sort_' . $dir, get_string($dir), 'core', ['class' => 'iconsort']);
            unset($tableheaders[2]);
            $sqlsort = '';
            $gradesort = $dir;
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
$table->head[] = get_string('edit');
$table->attributes['class'] = 'admintable generaltable table-sm';

$students = $DB->get_records_sql('SELECT u.userid, u.firstname, u.lastname, lfu.id, lfu.offset, lfu.userid FROM {user} u,
    {local_fitcheck_users} lfu WHERE classid = ' . $id .
    ' AND u.id = lfu.userid ' . $sqlsort);

// Populate table with student data and set test status to incomplete if test data doesn't exist.
$complete = 1;
$classgradetotal = 0;
$classresulttotal = 0;
$classresulttotalcount = 0;
foreach ($students as $student) {
    $row = array();
    $row[] = "$student->firstname $student->lastname";

    if (isset($result)) {
        $currresult = $DB->get_record('local_fitcheck_results',
            ['userid' => $student->userid, 'testid' => $currenttest->id, 'testnr' => $class->testnr + $student->offset]);
        if ($currresult) {
            if (isset($currresult->result) && $currresult->result != null) {
                $pos = strpos($currresult->result, '.');
                if ($pos !== false) {
                    $currresult->result = rtrim(rtrim($currresult->result, '0'), '.');
                }
                $row['result'] = $currresult->result;
                $classresulttotal += $currresult->result;
            } else {
                $row['result'] = '-';
            }
        } else {
            $row['result'] = '-';
        }
    }

    if ($currenttest->id != 0 && isset($currenttest)) {
        $currresult = $DB->get_record('local_fitcheck_results',
            ['userid' => $student->userid, 'testid' => $currenttest->id, 'testnr' => $class->testnr + $student->offset]);
        if ($currresult) {
            if ($currresult->result != null) {
                $row['grade'] = local_fitcheck_calc_grade($currenttest, $currresult->result);
                $classgradetotal += local_fitcheck_calc_grade($currenttest, $currresult->result);
                $classresulttotalcount++;
            } else {
                $row['grade'] = '-';
            }
        } else {
            $row['grade'] = '-';
        }
    } else {
        $allresults = $DB->get_records('local_fitcheck_results',
            ['userid' => $student->userid, 'testnr' => $class->testnr + $student->offset]);
        $resulttotal = 0;
        $resultcount = 0;
        if (count($allresults)) {
            foreach ($allresults as $allresult) {
                if ($allresult->result != null) {
                    $currentalltest = $DB->get_record('local_fitcheck_tests', ['id' => $allresult->testid]);
                    $resulttotal += local_fitcheck_calc_grade($currentalltest, $allresult->result);
                    $resultcount++;
                }
            }
            if ($resultcount) {
                $row['grade'] = round($resulttotal / $resultcount, 2);
                $classgradetotal += round($resulttotal / $resultcount, 2);
                $classresulttotalcount++;
            } else {
                $row['grade'] = '-';
            }
        } else {
            $row['grade'] = '-';
        }
    }
    if ($complete) {
        foreach ($tests as $test) {
            $testresult = $DB->get_record('local_fitcheck_results',
                ['userid' => $student->userid, 'testid' => $test->id, 'testnr' => $class->testnr + $student->offset]);
            if (!$testresult || $testresult->result == null) {
                $complete = 0;
            }
        }
    }
    $row[] = html_writer::link(new moodle_url('/local/fitcheck/settings/editresults.php?id=' . $student->id),
        $OUTPUT->pix_icon('i/edit', get_string('edit'))) .
        html_writer::link(new moodle_url('/local/fitcheck/results.php?id=' . $student->userid),
        $OUTPUT->pix_icon('t/hide', get_string('viewstudentresults', 'local_fitcheck')));
    $table->data[] = $row;
}

if (isset($gradesort)) {
    usort($table->data, "local_fitcheck_sort_grades");
    if ($gradesort == 'desc') {
        $table->data = array_reverse($table->data);
    }
} else if (isset($resultsort)) {
    usort($table->data, "local_fitcheck_sort_results");
    if ($resultsort == 'desc') {
        $table->data = array_reverse($table->data);
    }
}

// Add class average row.
$row = array();
$row[] = html_writer::tag('b', get_string('classaverage', 'local_fitcheck'));
if (isset($result)) {
    if ($classresulttotalcount) {
        $row[] = html_writer::tag('b', round($classresulttotal / $classresulttotalcount, 2));
    } else {
        $row[] = '-';
    }
}
if ($classresulttotalcount) {
    $row[] = html_writer::tag('b', round($classgradetotal / $classresulttotalcount, 2));
} else {
    $row[] = '-';
}
$row[] = '';
$table->data[] = $row;

// Add age group average row.
$row = array();
$agresults = $DB->get_records_sql('SELECT lfr.id, lfr.testid, lfr.userid, lfr.testnr, lfr.result FROM {local_fitcheck_results} lfr
    INNER JOIN {local_fitcheck_users} lfu
    ON lfu.userid = lfr.userid
    INNER JOIN {local_fitcheck_classes} lfc
    ON lfc.id = lfu.classid
    WHERE lfc.agegroup = ' . $class->agegroup .
    ' AND lfr.testnr = ' . $class->testnr . ' + lfu.offset');
$agresulttotal = 0;
$aggradetotal = 0;
$agcount = 0;
foreach ($agresults as $agresult) {
    if (isset($result)) {
        if ($agresult->testid == $view && $agresult->result != null) {
            $agresulttotal += $agresult->result;
            $aggradetotal += local_fitcheck_calc_grade($tests[$agresult->testid], $agresult->result);
            $agcount++;
        }
    } else if ($agresult->result != null) {
        $aggradetotal += local_fitcheck_calc_grade($tests[$agresult->testid], $agresult->result);
        $agcount++;
    }
}

$row[] = html_writer::tag('b', get_string('agegroupaverage', 'local_fitcheck'));
if (isset($result)) {
    if ($agcount) {
        $row[] = html_writer::tag('b', round($agresulttotal / $agcount, 2));
    } else {
        $row[] = '-';
    }
}
if ($agcount) {
    $row[] = html_writer::tag('b', round($aggradetotal / $agcount, 2));
} else {
    $row[] = '-';
}
$row[] = ' ';
$table->data[] = $row;
$extras = '';
if ($sort) {
    $extras .= '&sort=' . $sort;
}
if ($dir) {
    $extras .= '&dir=' . $dir;
}

if ($class->testnr) {
    $testdiv = html_writer::tag('h2', get_string('testnumber', 'local_fitcheck', $class->testnr));
    if ($complete) {
        $testdiv .= html_writer::tag('h4', get_string('complete', 'local_fitcheck'));
    } else {
        $testdiv .= html_writer::tag('h4', get_string('incomplete', 'local_fitcheck'));
    }
} else {
    $testdiv = html_writer::tag('h2', get_string('testnumbernone', 'local_fitcheck'));
}

$printbutton = html_writer::tag('a', get_string('printresults', 'local_fitcheck'),
    ['name' => 'printpdf', 'id' => 'printpdf',
        'href' => new moodle_url('/local/fitcheck/classpdf.php', ['classid' => $class->id, 'testnr' => $class->testnr]),
        'class' => 'btn btn-secondary float-right mr-2']);

$newtestbutton = html_writer::tag('button', get_string('startnewtest', 'local_fitcheck'),
    ['name' => 'newtest', 'id' => 'newtest', 'type' => 'submit', 'class' => 'btn btn-secondary float-right', 'value' => 1]);

$hidinputs = html_writer::tag('input', '', ['value' => $sort, 'name' => 'sort', 'id' => 'sort', 'hidden' => '']) .
    html_writer::tag('input', '', ['value' => $dir, 'name' => 'dir', 'id' => 'dir', 'hidden' => '']) .
    html_writer::tag('input', '', ['value' => $id, 'name' => 'id', 'id' => 'id', 'hidden' => '']);

echo $OUTPUT->header();
echo $testdiv;
echo html_writer::tag('form', $select . $hidinputs . $newtestbutton . $printbutton,
    ['method' => 'get', 'action' => '#', 'name' => 'selectForm']);
echo html_writer::table($table);
echo html_writer::tag('a', get_string('gobacktoclasslistmain', 'local_fitcheck'),
    ['href' => new moodle_url ('/local/fitcheck/settings/listclasses.php'), 'class' => 'btn btn-secondary float-right']);
echo $OUTPUT->footer();