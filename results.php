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
 * The FitCheck Results page.
 *
 * @package    local_fitcheck
 * @copyright  2021 Jae Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/local/fitcheck/lib.php');
require_login();
!isguestuser($USER->id) || print_error('noguest');

// Set page values.
$PAGE->set_url(new moodle_url('/local/fitcheck/results.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'local_fitcheck'));
$PAGE->set_heading(get_string('title', 'local_fitcheck'));
$PAGE->navbar->add('FitCheck', new moodle_url('/local/fitcheck/'));
$PAGE->navbar->add(get_string('results', 'local_fitcheck'));

$userid = optional_param('id', $USER->id, PARAM_INT);
if ($userid != $USER->id) {
    require_capability('local/fitcheck:viewallresults', context_system::instance());
}

$chart = html_writer::div('', 'chart--container', ['id' => 'resultsChart', 'style' => 'height:450px;']);
$pref = 0;

// Fetch tests and user results from database.
$tests = $DB->get_records('local_fitcheck_tests', ['gender' => $pref, 'status' => 1]);
$results = $DB->get_records('local_fitcheck_results', ['userid' => $userid]);

$testnames = '';
$testids = [];
$testidslabels = [];
$testidslabels[0] = get_string('alltests', 'local_fitcheck');

// Sort tests and results into arrays for later processing.
foreach ($tests as $test) {
    $testnames .= "'" . $test->shortname . "',";
    $testids[$test->id] = [];
    $testidslabels[$test->id] = $test->shortname;
}
foreach ($results as $result) {
    if ($result->result == null) {
        array_push($testids[$result->testid], 'null');
    } else {
        array_push($testids[$result->testid], $result->result);
    }
}

// Fetch highest and lowest test count.
$highestsort = $DB->get_records_sql('SELECT DISTINCT testnr FROM {local_fitcheck_results}
    WHERE userid = ' . $userid . ' ORDER BY testnr DESC');
$highest = array_shift($highestsort)->testnr;
$lowest = 1;

// Set which tests to display, max of 3.
if ($highest > $lowest + 2) {
    $highest = $lowest + 2;
} else if ($highest - $lowest < 2) {
    $lowest - (2 - ($highest - $lowest));
    if ($lowest < 1) {
        $lowest = 1;
    }
}

$testdata = [];
$lowestarrval = 0;
if ($lowest != 0) {
    $lowestarrval = $lowest - 1;
}

// Slice arrays to get only the tests we want to display.
foreach ($testids as $key => $test) {
    $testdata[$key] = array_slice($test, $lowestarrval, $highest - $lowest + 1);
}

$gradedata = [];
$testcanvas = '';
$canvasjs = '';
$counttests = '';
$canvasoptions = "{ legend: { display: false }, scales: { yAxes: [{ ticks: { maxTicksLimit: 6 } }] } }";
// Calculate grades.
foreach ($tests as $test) {
    $newtestdata = [];
    $encodedtestdata = [];
    foreach ($testdata[$test->id] as $data) {
        $newtestdata[] = local_fitcheck_calc_grade($test, $data);
        if ($data == "null") {
            $encodedtestdata[] = null;
        } else {
            $encodedtestdata[] = $data;
        }
    }
    $gradedata[$test->id] = $newtestdata;
    $testcanvas .= html_writer::div(
        html_writer::tag('canvas', '', ['height' => '300', 'width' => '450', 'id' => 'lineChart' . $test->id]),
        'testchart-' . $test->id . ' d-none');
    $counttests .= 'var test' . $test->id . ' = {labels: [';
    for ($i = 1; $i <= count($testdata[$test->id]); $i++) {
        $counttests .= '\'' . $i . '. Test\',';
    }
    $counttests .= '],
        datasets: [
            {
                fillColor: "rgba(220,220,220,0.2)",
                strokeColor: "rgba(220,220,220,1)",
                pointColor: "rgba(220,220,220,1)",
                pointStrokeColor: "#00979f",
                pointHighlightFill: "#00979f",
                pointHighlightStroke: "rgba(220,220,220,1)",
                data: ' . json_encode($encodedtestdata) . '
            }
        ]};
        ';
    $canvasjs .= "new Chart(document.getElementById(\"lineChart" . $test->id . "\").getContext(\"2d\"), {type: 'line', data: test$test->id, options: $canvasoptions});";
}

// Prepare series string for ZingChart.
$series = '';
for ($i = 0; $i < $highest - $lowest + 1; $i++) {
    $series .= '{ values: ';
    $datastring = '[';
    foreach ($gradedata as $key => $data) {
        $datastring .= $data[$i] . ',';
    }
    $datastring .= ']';
    $series .= $datastring . ', text: \'' . get_string('testnumber', 'local_fitcheck', $lowest + $i) . '\'},';
}

// Create grade table header.
$testnamesheader = html_writer::tag('th', html_writer::div(get_string('grade', 'local_fitcheck'), 'gradetablecell'), ['class' => 'p-1 text-center']);
$testgrades = '';
$firsttestid = array_key_first($testidslabels);
foreach ($tests as $test) {
    if ($firsttestid == $test->id) {
        $testnamesheader .= html_writer::tag('td', html_writer::div($test->shortname, 'gradetablecell'), ['class' => 'p-1 text-center selectcurrent gradescale-' . $test->id]);
    } else {
        $testnamesheader .= html_writer::tag('td', html_writer::div($test->shortname, 'gradetablecell'), ['class' => 'p-1 text-center d-none gradescale-' . $test->id]);
    }
}

// Calculate grade table display values in increments of 0.5.
for ($i = 6; $i >= 1; $i = $i - 0.5) {
    $testgrades .= html_writer::start_tag('tr') .
        html_writer::tag('th', html_writer::div($i, 'gradetablecell'), ['class' => 'p-1 text-center']);
    foreach ($tests as $test) {
        if ($test->minmax) {
            if ($i == 6) {
                $calcgrade = round($test->minresult, 2);
            } else {
                $calcgrade = round(($test->maxresult - $test->minresult) * ((5 - ($i - 1)) / 5) + $test->minresult, 2);
                $stepcheck = fmod($calcgrade, $test->step);
                if ($stepcheck / $test->step >= 0.499) {
                    $calcgrade += $test->step - $stepcheck;
                } else {
                    $calcgrade -= $stepcheck;
                }
            }
        } else {
            if ($i == 1) {
                $calcgrade = round($test->minresult, 2);
            } else {
                $calcgrade = round(($test->maxresult - $test->minresult) * (($i - 1) / 5) + $test->minresult, 2);
                $stepcheck = fmod($calcgrade, $test->step);
                if ($stepcheck / $test->step >= 0.499) {
                    $calcgrade += $test->step - $stepcheck;
                } else {
                    $calcgrade -= $stepcheck;
                }
            }
        }
        if ($firsttestid == $test->id) {
            $testgrades .= html_writer::tag('td', html_writer::div($calcgrade, 'gradetablecell'), ['class' => 'p-1 text-center selectcurrent gradescale-' . $test->id]);
        } else {
            $testgrades .= html_writer::tag('td', html_writer::div($calcgrade, 'gradetablecell'), ['class' => 'p-1 text-center d-none gradescale-' . $test->id]);
        }
    }
    $testgrades .= html_writer::end_tag('tr');
}

// Create table and select.
$tablecontent = html_writer::tag('tr', $testnamesheader, ) . $testgrades;
$gradetable = html_writer::tag('table', $tablecontent, ['class' => 'gradetable']);
$gradeselect = html_writer::select(
    $testidslabels, 'gradeselect', $firsttestid, '', ['id' => 'gradeselect', 'onchange' => 'updateGradeTable(this)', 'class' => 'my-3']
);

// Output HTML and scripts.
echo $OUTPUT->header();
echo html_writer::script('', 'https://cdn.zingchart.com/zingchart.min.js');
echo html_writer::script('', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js');
echo $gradeselect;
echo $chart;
echo html_writer::script("
ZC.LICENSE = ['569d52cefae586f634c54f86dc99e6a9', 'b55b025e438fa8a98e32482b5f768ff5'];
var myConfig = {
    type: 'radar',
    gui: {
        contextMenu: {
            empty: true
        }
    },
    legend: {

    },
    plot: {
        aspect: 'area',
        animation: {
            effect: 3,
            sequence: 1,
            speed: 700
        }
    },
    scaleV: {
        values: '1:6:1'
    },
    scaleK: {
        values: '1:8:1',
        labels: [" . $testnames . "],
        item: {
            fontColor: '#607D8B',
            backgroundColor: 'white',
            borderColor: '#aeaeae',
            borderWidth: 1,
            padding: '5 10',
            borderRadius: 10
        },
        refLine: {
            lineColor: '#c10000'
        },
        tick: {
            lineColor: '#59869c',
            lineWidth: 2,
            lineStyle: 'dotted',
            size: 20
        },
        guide: {
            lineColor: '#607D8B',
            lineStyle: 'solid',
            alpha: 0.3,
            backgroundColor: '#c5c5c5 #718eb4'
        }
    },
    series: [" . $series . "],

};

zingchart.render({
    id: 'resultsChart',
    data: myConfig,
    height: '100%',
    width: '100%'
});
");
echo html_writer::div(html_writer::div($testcanvas, 'col-lg-8') .
    html_writer::div($gradetable, 'col-lg-4 gradetable-container d-none my-auto'), 'row');
echo html_writer::script('' . $counttests . $canvasjs . '' .
    'function updateGradeTable(select) {
        var value = select.value;

        if (value == 0) {
            $("#resultsChart").removeClass("d-none");
            $(".gradetable-container").addClass("d-none");
        } else {
            $("#resultsChart").addClass("d-none");
            $(".gradetable-container").removeClass("d-none");
        }
        var newSelect = ".gradescale-" + value;
        var newCanvas = ".testchart-" + value;
        $(".selectcurrent").addClass("d-none").removeClass("selectcurrent");
        $(".canvascurrent").addClass("d-none").removeClass("canvascurrent");
        $(newSelect).addClass("selectcurrent").removeClass("d-none");
        $(newCanvas).addClass("canvascurrent").removeClass("d-none");
        console.log("ye?");
        $
    }
');
echo $OUTPUT->footer();