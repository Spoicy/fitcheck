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
 * English language file.
 *
 * @package    local_fitcheck
 * @category   string
 * @copyright  2021 Jae Funke
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'FitCheck';
$string['title'] = 'FitCheck';
$string['addtests'] = 'Add FitCheck Tests';
$string['fullname'] = 'Test full name';
$string['fullname_help'] = 'The full name of the test is displayed at the top of the test page and in the list of tests.';
$string['shortname'] = 'Test short name';
$string['shortname_help'] = 'The short name of the test is displayed in the navigation.';
$string['description'] = 'Description';
$string['description_help'] = 'The description of the test is displayed at the top of the page and allows for formatting and images.';
$string['video'] = 'Video example & Thumbnail';
$string['video_help'] = 'The video example of the test is display underneath the description and above the answers. The thumbnail is displayed when viewing the mainpage.';
$string['result1'] = 'Result type';
$string['result1placeholder'] = 'e.g. &#34;Right leg&#34;';
$string['result2'] = 'Second result type';
$string['result2_help'] = 'The second result type of the test is for cases where two values are required. Specify what each of the values mean, if using this field.';
$string['result2placeholder'] = 'e.g. &#34;Left leg&#34;';
$string['maxresult'] = 'Maximum result';
$string['maxresult_help'] = 'The maximum result of the test.';
$string['savechanges'] = 'Save test';
$string['gender'] = 'Gender';
$string['gender_help'] = 'The gender value of the test determines which group of people can see it.';
$string['maleunisex'] = 'Male/Unisex';
$string['female'] = 'Female';
$string['minmax'] = 'Grading style';
$string['minmax_help'] = 'The grading style of the test determines which value will be used as the highest grade.';
$string['min'] = 'Minimum';
$string['max'] = 'Maximum';
$string['minresult'] = 'Minimum result';
$string['minresult_help'] = 'The minimum result of the test.';
$string['method'] = 'Grading method';
$string['methodaverage'] = 'Average/None';
$string['methodminus'] = 'Minus';
$string['methodplusminus'] = 'Plus/Minus';
$string['method_help'] = 'The grading method of the test calculates the submitted result(s). Leave as Average/None for default settings. Use Minus if subtracting first result with the second, or Plus/Minus (single result only) if result is expected to be + or -.';
$string['testresultminus'] = 'Actual {$a}:';
$string['thumbnail'] = 'Thumbnail';
$string['thumbnail_help'] = 'The thumbnail of the test shows up on the main FitCheck page.';
$string['status'] = 'Status';
$string['status_help'] = 'The status of the test determines whether or not students can view and take the test. Default = on';
$string['gototest'] = 'Go to test';
$string['gototestsettings'] = 'Edit';
$string['gobacktomainpage'] = 'Back to main page';
$string['testresult'] = 'Your result:';
$string['testresultaverage'] = 'Average:';
$string['testresultminus'] = 'Difference:';
$string['checkresult'] = 'Check the input and confirm:';
$string['submit'] = 'Submit result';
$string['videoheader'] = 'Video example:';
$string['settings'] = 'Settings';
$string['editclass'] = 'Edit class';
$string['newtest'] = 'New test';
$string['iderror'] = 'Provided ID was not a number.';
$string['teacheriderror'] = 'You do not have access to managing this class.';
$string['results'] = 'Results';
$string['assigned'] = 'Assigned students';
$string['notassigned'] = 'Not assigned students';
$string['assignedcount'] = 'Assigned students ({$a})';
$string['unassignedcount'] = 'Unassigned students ({$a})';
$string['alrassignedcount'] = 'Already assigned students ({$a})';
$string['assignedcountmatching'] = 'Assigned students matching \'{$a->search}\' ({$a->count})';
$string['unassignedcountmatching'] = 'Unassigned students matching \'{$a->search}\' ({$a->count})';
$string['alrassignedcountmatching'] = 'Already assigned students matching \'{$a->search}\' ({$a->count})';
$string['add'] = '◄ Add';
$string['remove'] = 'Remove ►';
$string['classname'] = 'Class name';
$string['classgender'] = 'Class gender';
$string['classagegroup'] = 'Class age group';
$string['classagegroupexample'] = 'e.g. 2017/18';
$string['classendyear'] = 'Class graduation year';
$string['classendyearexample'] = 'e.g. 2022';
$string['classteacher'] = 'Class teacher';
$string['saveclassinfo'] = 'Save info';
$string['createclasstounlock'] = 'Enter a class name and gender to create a class and to unlock managing classes.';
$string['gobacktoclasslist'] = 'Go back';
$string['search'] = 'Search';
$string['clear'] = 'Clear';
$string['notassignederror'] = 'You have not been assigned a class yet.';
$string['grade'] = 'Grade';
$string['gradetable'] = 'Grade table';
$string['testnumber'] = 'Test #{$a}';
$string['listclasses'] = 'Classes';
$string['listclassname'] = 'Class';
$string['listfirstname'] = 'Teacher first name';
$string['listlastname'] = 'last name';
$string['listgender'] = 'Gender';
$string['listteacher'] = 'Teacher';
$string['liststatus'] = 'Status';
$string['listtestname'] = 'Test';
$string['addnewclass'] = 'Add a new class';
$string['viewclassresults'] = 'View class results';
$string['viewtest'] = 'View test';
$string['browselisttests'] = 'Browse list of FitCheck tests';
$string['browselistclasses'] = 'Browse list of FitCheck classes';
$string['createnewtest'] = 'Create a new test';
$string['createnewclass'] = 'Create a new class';
$string['active'] = 'Active';
$string['inactive'] = 'Inactive';
$string['test'] = 'Test';
$string['addnewtest'] = 'Add a new test';
$string['classresults'] = 'Class results';
$string['classeslist'] = 'Classes list';
$string['classamountforteacher'] = '{$a} assigned class(es)';
$string['classamount'] = '{$a} class(es)';
$string['testamount'] = '{$a} test(s)';
$string['viewstudentresults'] = 'View student results';
$string['liststudentfirstname'] = 'Student first name';
$string['liststudentlastname'] = 'last name';
$string['listgrade'] = 'Grade';
$string['listresult'] = 'Result';
$string['average'] = 'Average';
$string['testnumbernone'] = 'No tests performed yet';
$string['testnumber'] = 'Test #{$a}';
$string['complete'] = 'Status: Complete';
$string['incomplete'] = 'Status: Incomplete';
$string['startnewtest'] = 'Start a new test';
$string['alreadysubmittedresult'] = 'You have already submitted a result for this test.';
$string['confirmstartnewtest'] = 'Start a new test?';
$string['confirmstartnewtestfull'] = 'Are you sure you want to start a new test? The current test is incomplete, and will no longer be able to accept results if a new one is started.';
$string['starttest'] = 'Start test';
$string['printresults'] = 'Print results';
$string['class'] = 'Class';
$string['alltests'] = 'All tests';
$string['agegrouperror'] = 'The age group does not follow pattern as shown by the example text.';
$string['endyearerror'] = 'The graduation year is not a valid year.';
$string['classaverage'] = 'Class average:';
$string['updateresults'] = 'Update results';

$string['fitcheck:deletetests'] = 'Delete FitCheck Tests';
$string['fitcheck:deleteusers'] = 'Delete FitCheck Users';
$string['fitcheck:editclasses'] = 'Edit FitCheck Classes';
$string['fitcheck:editresults'] = 'Edit FitCheck User Results';
$string['fitcheck:edittests'] = 'Edit FitCheck Tests';
$string['fitcheck:viewallresults'] = 'View all FitCheck results';
$string['fitcheck:viewdisabledtests'] = 'View Disabled FitCheck Tests';