Learner progress report plugin
==============================
Developed specifically for LearningWorks Training Department

Overview
--------
Each Moodle course category is a "Programme" of study that contains related "Unit standards" which are Moodle courses. Moodle groups within the 
course are used to indicate separate student cohorts. Each Moodle course has a single Assignment as the "Unit standard" assessment.

This report displays summary information of students' course grades or submission statuses across a collection of courses. This gives an overall 
picture of a students engagement/progress across a programme of study. The collection is filtered on course group names and course categories. 

Statuses include:

* Not enrolled
* No submission
* Submitted for grading
* Achieved
* Not Achieved

How it works
------------
A Moodle course needs to configured to collect status information. The course MUST contain at least 1 assignment. In order for data to be collected for 
the report an assignment must be setup to be tracked. To do from with a course go to:

Course administration > Reports > Configure learner progress tracking

Select assignment and save.

Tool included that will attempt to automatically track courses with single assignments. Go to URL:

https://moodle/report/lp/findassestotrack.php

Data for the report is then collected via scheduled task as process fairly intensive.

This can also be manually updated via a cli script.

````
$ sudo -u www-data /usr/bin/php report/lp/cli/fetch.php
````


Installation
------------
The plugin is installed as any other report plugin. 

1. Unzip source to report/lp folder on your Moodle server.
2. In your Moodle site (as admin) go to Settings > Site administration > Notifications (you should, for most plugin types, get a message saying 
the plugin is installed).
