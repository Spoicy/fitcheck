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
 * FitCheck index page for settings
 *
 * @package    local_fitcheck
 * @copyright  2021 Jae Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/local/fitcheck/lib.php');
require_login();
require_capability('local/fitcheck:editclasses', context_system::instance());

$PAGE->set_url(new moodle_url('/local/fitcheck/settings/index.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('title', 'local_fitcheck'));
$PAGE->set_heading(get_string('title', 'local_fitcheck'));
$PAGE->navbar->add('FitCheck', new moodle_url('/local/fitcheck'));
$PAGE->navbar->add(get_string('settings', 'local_fitcheck'));

// Display settings based on what capabilities are enabled.
$li = '';
if (has_capability('local/fitcheck:edittests', $PAGE->context)) {
    $li .= html_writer::tag('li',
        html_writer::tag('a', html_writer::tag('h5', get_string('browselisttests', 'local_fitcheck')),
            ['href' => new moodle_url('/local/fitcheck/settings/listtests.php')])) .
        html_writer::tag('li',
            html_writer::tag('a', html_writer::tag('h5', get_string('createnewtest', 'local_fitcheck')),
            ['href' => new moodle_url('/local/fitcheck/settings/edittests.php?id=-1')])
        );
}
if (has_capability('local/fitcheck:editclasses', $PAGE->context)) {
    $li .= html_writer::tag('li',
        html_writer::tag('a', html_writer::tag('h5', get_string('browselistclasses', 'local_fitcheck')),
            ['href' => new moodle_url('/local/fitcheck/settings/listclasses.php')])) .
        html_writer::tag('li',
            html_writer::tag('a', html_writer::tag('h5', get_string('createnewclass', 'local_fitcheck')),
            ['href' => new moodle_url('/local/fitcheck/settings/editclass.php?id=-1')])
        );
}
if (has_capability('local/fitcheck:deleteusers', $PAGE->context)) {
    $li .= html_writer::tag('li',
        html_writer::tag('a', html_writer::tag('h5', get_string('deleteresults', 'local_fitcheck')),
            ['href' => new moodle_url('/local/fitcheck/settings/deleteresults.php')]));
}

// Output HTML.
echo $OUTPUT->header();
echo html_writer::tag('h2', get_string('settings')) .
    html_writer::tag('ul',
        $li, ['class' => 'list-unstyled ml-0 pt-2']
    );
echo $OUTPUT->footer();