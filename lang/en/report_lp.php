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
 * Definition of language strings.
 *
 * @package   {{PLUGIN_NAME}} {@link https://docs.moodle.org/dev/Frankenstyle}
 * @copyright 2015 LearningWorks Ltd {@link http://www.learningworks.co.nz}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Learner progress report';
$string['report/learnerprogress:view'] = 'Can view report';
$string['selectgroup'] = 'Select group...';
$string['selectcategory'] = 'Select category...';
$string['coursegroup'] = 'Course group';
$string['coursecategory'] = 'Course category';
$string['configureprogresstracking'] = 'Configure learner progress tracking';
$string['configurelearnerprogressreport'] = 'Configure learner progress report';
$string['viewlearnerprogresssummary'] = 'View learner progress summary';

$string['trackassignment'] = 'Track assignment';
$string['notenrolled'] = 'Not enrolled';
$string['exportfilename'] = 'learnerprogress';
$string['configure'] = 'Configure';
$string['datalastfetched'] = 'Data last fetched at: {$a}';
$string['fetchprogessdata'] = 'Fetch progress data';
$string['lp:configure'] = 'Build report add/remove groupings and measures';
$string['lp:viewsummary'] = 'View the summary Learner progress report';
$string['lp:viewindividual'] = 'View individual Learner progress report';
$string['lp:exportsummary'] = 'Export summary to file';

$string['assignmentresubmitcount:measure:name'] = 'Assignment resubmits';
$string['assignmentresubmitcount:measure:description'] = 'The number of resubmitted assignment attempts.';
$string['assignmentresubmitcount:measure:defaultlabel'] = 'Assignment resubmits';
$string['assignmentresubmitcount:measure:configuredlabel'] = 'Resubmit total: {$a}';

$string['assignmentstatus:measure:name'] = 'Assignment status';
$string['assignmentstatus:measure:description'] = 'Uses grade display type for submitted assignments for example "Achieved" or "Not achieved". Indicates not submitted type states such as "Draft", "No submission", and "Reopened".';
$string['assignmentstatus:measure:defaultlabel'] = 'Status: Assignment name';
$string['assignmentstatus:measure:configuredlabel'] = 'Status: {$a}';

$string['attendancename'] = 'Attendance name';
$string['attendancesessionssummary:measure:name'] = 'Attendance';
$string['attendancesessionssummary:measure:description'] = 'Show how many sessions a learner has attended out of total number of sessions. For example 3/4 sessions attended.';
$string['coursegrade:measure:label'] = 'Course grade (or final grade)';
$string['coursegrade:measure:name'] = 'Course grade';
$string['coursegrade:measure:description'] = 'The course grade calculated or based on aggregated grade items from the gradebook.';

$string['coursesectionactivitycompletion:measure:name'] = 'Course section activity completion';
$string['coursesectionactivitycompletion:measure:defaultlabel'] = 'Course section activity completion';
$string['coursesectionactivitycompletion:measure:configuredlabel'] = 'Course section activity completion: {$a}';
$string['coursesectionactivitycompletion:measure:description'] = 'The percentage of activities completed by a learner that reside in a course section.';

$string['checklistcomplete:measure:name'] = 'Checklist';
$string['checklistcomplete:measure:description'] = 'Indicate if checklist is incompleted or completed.';
$string['checklistcomplete:measure:defaultlabel'] = 'Checklist name';

$string['gradecategoryactivitycompletion:measure:name'] = 'Grade category activity completion';
$string['gradecategoryactivitycompletion:measure:description'] = 'The percentage of activities completed by a learner that reside in a grade book category.';
$string['lastcourseaccess:measure:label'] = 'Last course access';
$string['lastcourseaccess:measure:name'] = 'Last course access';
$string['lastcourseaccess:measure:description'] = 'The date and time the learner last accessed this course.';
$string['grouping:name'] = 'Grouping';
$string['grouping:description'] = 'Grouping allows a set of measures to be grouped together for display purposes.';
$string['learner:name'] = 'Learner';
$string['learner:description'] = 'Standard learner information such as profile picture and fullname.';

$string['grouping'] = 'Grouping';
$string['measures'] = 'Measures';
$string['configurereportfor'] = 'Configure learner progress report for {$a}';
$string['summaryreportfor'] = 'Learner progress summary report for {$a}';

$string['usecustomlabel'] = 'Use custom label';
$string['customlabel'] = 'Custom label';
$string['defaultlabel'] = 'Default label';
$string['visibletosummary'] = 'Display in the summary report';
$string['visibletoinstance'] = 'Display in the learner instance report';
$string['visibletolearner'] = 'Display to learner';
$string['configure'] = 'Configure {$a}';

$string['defaultlabelgrouping'] = 'Grouping {$a}';


$string['defaultlabelattendancesessionssummary'] = 'Sessions attended summary';

$string['defaultlabelgradecategoryconfigured'] = 'Grade category completion: {$a}';

$string['parentgrouping'] = 'Parent grouping';
$string['generalsettings'] = 'General settings';
$string['specificsettings'] = 'Specific settings';

$string['pleasechoose'] = 'Please choose an item from the list';
$string['coursesection'] = 'Course section';

$string['dotn'] = '...n';
$string['noavailablemodules'] = 'No available {$a} modules. Either none in course or all are in use be this measure.';
$string['nogradecategories'] = 'No available grade categories. Either none in course or all are in use be this measure.';
$string['noavailablesections'] = 'No available course sections. Either none in course or all are in use be this measure.';
$string['noitemsconfigured'] = 'No items configured';
$string['noitemsconfigured:description'] = 'Use the grouping and measures controls to start configuring the learner progress report';

$string['moveup'] = 'Move item up';
$string['movedown'] = 'Move item down';
$string['deleteitem'] = 'Delete item';
$string['configureitem'] = 'Configure item';
$string['deletegroupingitem'] = '
<h5>Delete grouping {$a}</h5>
<p>Deleting a grouping will also delete any measures associated to the grouping</p>';
$string['deletemeasureitem'] = '
<h5>Delete measure {$a}</h5>';
$string['noreportconfiguration'] = 'Report configuration does not exist';
$string['instantiatenewreportquestion'] = 'Would you like to instantiate a new report configuration for {$a}?';
$string['instantiate'] = 'Instantiate...';

$string['addgrouping'] = 'Add grouping item to the report';
$string['addmeasure'] ='Add measure of item type {$a} to the report';

$string['none'] = 'None';
$string['filtergroups'] = 'Filter groups';
$string['aria:filterdropdown'] = 'Filtering dropdown';
$string['aria:none'] = 'aria:none';
$string['aria:open'] = 'aria:open';
$string['aria:closed'] = 'aria:closed';
$string['aria:controls'] = 'aria:controls';

$string['learner'] = 'Learner';
$string['idnumber'] = 'ID';
$string['coursegroups'] = 'Groups';

$string['noselectedcoursegroups'] = 'No course groups selected';
$string['noselectedcoursegroups:description'] = 'Please use the course group filter dropdown to select groups of learners to filter date on';

$string['cachedef_summarydata'] = 'The summary measure data for learners in a course';

$string['achieved'] = 'Achieved';
$string['notachieved'] = 'Not achieved';
$string['addmeasurestoreport'] = 'Add measures to report';


$string['noreportconfiguration'] = '
Learner progress report configuration does not exist for this course.
<br><br>
Would you like to initialise a new learner progress report configuration for this course?';
$string['initialise'] = 'Initialise...';
$string['coursegroups:learnerfield:description'] = 'Displays any groups memberships the learner has within the course.';
$string['coursegroups:learnerfield:name'] = 'Course groups';
$string['coursegroups:learnerfield:label'] = 'Groups';

$string['idnumber:learnerfield:description'] = 'Displays learners ID number attached to user record.';
$string['idnumber:learnerfield:name'] = 'Learner ID number';
$string['idnumber:learnerfield:label'] = 'ID';