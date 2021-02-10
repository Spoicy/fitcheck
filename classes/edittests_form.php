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
 * Form for editing a FitCheck test
 *
 * @copyright 2021 Jae Funke
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_fitcheck
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/user/lib.php');

/**
 * Class local_fitcheck_edittests_form.
 *
 * @copyright 2021 Jae Funke
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_fitcheck_edittests_form extends moodleform {
    public function definition() {
        global $USER, $CFG;

        $mform = $this->_form;

        if (!is_array($this->_customdata)) {
            throw new coding_exception('invalid custom data for test_edit_form');
        }

        $test = $this->_customdata['test'];
        $testid = $test->id;

        // Accessibility: "Required" is bad legend text.
        $strgeneral  = get_string('general');
        $strrequired = get_string('required');

        // Set up form.
        $mform->addElement('advcheckbox', 'status', get_string('status', 'local_fitcheck'), ' ', 'checked');
        $mform->addHelpButton('status', 'status', 'local_fitcheck');
        $mform->setType('status', PARAM_BOOL);

        $mform->addElement('text', 'fullname', get_string('fullname', 'local_fitcheck'), 'size="45"');
        $mform->addHelpButton('fullname', 'fullname', 'local_fitcheck');
        $mform->setType('fullname', PARAM_TEXT);
        $mform->addRule('fullname', $strrequired, 'required', null, 'client');

        $mform->addElement('text', 'shortname', get_string('shortname', 'local_fitcheck'), 'size="45"');
        $mform->addHelpButton('shortname', 'shortname', 'local_fitcheck');
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addRule('shortname', $strrequired, 'required', null, 'client');

        $mform->addElement('editor', 'description_editor', get_string('description', 'local_fitcheck'), null, array(
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'noclean' => true,
            'context' => context_system::instance(),
            'subdirs' => true));
        $mform->addHelpButton('description_editor', 'description', 'local_fitcheck');
        $mform->setType('description_editor', PARAM_RAW);
        $mform->addRule('description_editor', $strrequired, 'required', null, 'client');

        $mform->addElement('filemanager', 'video_filemanager', get_string('video', 'local_fitcheck'), null, [
            'maxfiles' => 2,
            'accepted_types' => array('.mp4', '.mov', '.jpg', '.png'),
        ]);
        $mform->addHelpButton('video_filemanager', 'video', 'local_fitcheck');
        $mform->setType('video_filemanager', PARAM_FILE);

        $mform->addElement('select', 'gender', get_string('gender', 'local_fitcheck'), array(
            get_string('maleunisex', 'local_fitcheck'),
            get_string('female', 'local_fitcheck')
        ));
        $mform->addRule('gender', $strrequired, 'required', null, 'client');

        $mform->addElement('text', 'resulttype1', get_string('result1', 'local_fitcheck'),
            'size="45" placeholder="' . get_string('result1placeholder', 'local_fitcheck') . '"');
        $mform->setType('resulttype1', PARAM_TEXT);

        $mform->addElement('text', 'resulttype2', get_string('result2', 'local_fitcheck'),
            'size="45" placeholder="' . get_string('result2placeholder', 'local_fitcheck') . '"');
        $mform->addHelpButton('resulttype2', 'result2', 'local_fitcheck');
        $mform->setType('resulttype2', PARAM_TEXT);

        $mform->addElement('select', 'minmax', get_string('minmax', 'local_fitcheck'), array(
            get_string('max', 'local_fitcheck'),
            get_string('min', 'local_fitcheck')
        ));
        $mform->addHelpButton('minmax', 'minmax', 'local_fitcheck');
        $mform->addRule('minmax', $strrequired, 'required', null, 'client');

        $mform->addElement('select', 'method', get_string('method', 'local_fitcheck'), array(
            get_string('methodaverage', 'local_fitcheck'),
            get_string('methodminus', 'local_fitcheck'),
            get_string('methodplusminus', 'local_fitcheck')
        ));
        $mform->addHelpButton('method', 'method', 'local_fitcheck');
        $mform->addRule('minmax', $strrequired, 'required', null, 'client');

        $mform->addElement('text', 'maxresult', get_string('maxresult', 'local_fitcheck'), 'size="20"');
        $mform->addHelpButton('maxresult', 'maxresult', 'local_fitcheck');
        $mform->setType('maxresult', PARAM_LOCALISEDFLOAT);
        $mform->addRule('maxresult', $strrequired, 'required', null, 'client');

        $mform->addElement('text', 'minresult', get_string('minresult', 'local_fitcheck'), 'size="20"');
        $mform->addHelpButton('minresult', 'minresult', 'local_fitcheck');
        $mform->setType('minresult', PARAM_LOCALISEDFLOAT);
        $mform->addRule('minresult', $strrequired, 'required', null, 'client');

        $this->add_action_buttons();

        if ($testid == -1 && !isset($test->status)) {
            $test->status = 1;
        }

        $this->set_data($test);
    }
}