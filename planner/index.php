/*
Planner/Dayplanner


Planner v1.0.3
Klaus Buecher
   

LICENSE

=====================================

The planner module was built by Klaus Buecher and is released here
under modified BSD license (see GNU.org).

Uses jquery, jqueryui, jsgantt and fullcalendar. Please see their separate licences.
 * 
Copyright (c) 2013/2014 Klaus Buecher (Opto)

The Planner module is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
Take care and backup your database frequently.




This module is intended to give support for planning purposes.


Task date planning (IE, FF etc.)
This tab displays an interactive Gantt chart, showing task dates and their % complete. 
Tasks can be rescheduled by dragging or resizing using mouse interaction. After each edit, they are updated in the w2p database.


Dayplanner/Weekplanner/Monthplanner  (no IE currently)
They allow  to edit events by mouse interaction (shift events to other timeslot, resize events).
Also, they give a list of the user's currently active tasks, coloured by their status. These tasks can be dragged into the calendar,
where they are converted into events. So one can plan in detail when (and how often) on a day one wants to work on a specific task.
Tooltips give info on tasks and events, click, doubleclick etc allow to easily set progress, open edit, etc.



More detailed information:
1) Dayplanner/Weekplanner/Monthplanner.
It intends to help to distribute my current tasks into small workloads that I will tackle in my working day.

It includes drag and drop support, so some jobs can be done faster than calling and waiting for webpages.
Currently tested on Firefox and Google Chrome, does not work (yet?) on IE.

Layout/function:
Middle display: interactive calendar showing the day's events. Events can be dragged to other time slots, 
or they can be resized. Click into a time slot creates a new event and stores it to the database (as private event).
Doubleclick on an event changes the color to green. I use this to indicate that I have actually worked on this issue.
So at the end of the day, I can easily see what was not done or at least not started, due to other things coming up.
A tooltip shows info and description for the event.
Company must be set to ALL to display the new events (currently not attached to any company).


Left display:
This shows a list of my currently active tasks - colored by overdue, in time, not started yet, 
just as the tasks in the tasks tab are colored.
The tasks can be dragged to a timeslot on the calendar and will be converted into private events and 
stored to the database.
I usually have more tasks than I can finish in a working day - I use this to decide
which tasks I want to work on today at supposedly which time.
After storing (again as private event), they can be resized or dragged to different times.

Tooltips: they allow to view, edit, create task logs, view the parent project, all usng html links.
Holding shift/click will open a new browser window for this. Changed info is not yet propagated back to the task display.

Doubleclick on a task will open a dialog for the % progress. 100% is preset, so to finish a task, 
doubleclick + enter is sufficient.


In addition to the official company projects in w2p, I created a project for private tasks, another for the small
company tasks that need not be expanded to full fledged projects.

Adding new tasks: typically, I use project designer to add a bunch of these. Usually, I have two or three projectdesigner
windows (for different projects) open to quickly add new tasks when they come up into the appropriate projects, and then pull
them into the day_planner window by a refresh F5.


2) Taskplanner
Drag and drop tasks to other time. The time axis will scroll if the task is scrolled outside the active window.
(I think this is not functional in the version for w2p 3.1)
Resize tasks: drag the right end to another date.
Currently, changes are done by full days only, task times (hours) are left as is.


More to come:

Interactive display of project gantt.
More edit capabilities in gantt.

Interactive gantt view of full projects

Convert (Thunderbird) emails into w2p tasks.

etc.


Some bugs, clipped tooltips, etc., but it is usable and helps me to plan since a few weeks, and didn't destroy
anything inside my database (yet?). No warranty given in any case.
Don't look at the code, it is a mess.


I tested this on my notebook with a local wampserver. Response times are fine, for display and also for posting to the database.

SAFETY:  Beware: creating new events, the title is stored as is - I didn't check yet whether w2p checks elsewhere for bad html, sql
injection etc. I don't enter that on my own server, but this potential safety flaw makes the module still beta.

Nevertheless, for me, it is usable. Enjoy.
Installation: just as any custom module. See the w2p wiki. Unzip, put to w2p modules directory, in system admin, 
go to modules, install, activate and un-hide.
At the moment, day planner is the submodule to be displayed upon calling planner. All other links are still (nearly) unmodified calendar module scripts.

Klaus












*/


<?php /* $Id$ $URL$ */
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

// check permissions for this record
$perms = &$AppUI->acl();
$canRead = canView($m);

if (!$canRead) {
	$AppUI->redirect(ACCESS_DENIED);
}
$event_filter_list = array('my' => 'My Events', 'own' => 'Events I Created', 'all' => 'All Events');

global $tab, $locale_char_set, $date;
$AppUI->savePlace();

$company_id = $AppUI->processIntState('CalIdxCompany', $_REQUEST, 'company_id', $AppUI->user_company);

$event_filter = $AppUI->checkPrefState('CalIdxFilter', w2PgetParam($_REQUEST, 'event_filter', ''), 'EVENTFILTER', 'my');

$tab = $AppUI->processIntState('CalDayViewTab', $_GET, 'tab', (isset($tab) ? $tab : 0));

// get the prefered date format
$df = $AppUI->getPref('SHDATEFORMAT');

// get the passed timestamp (today if none)
$ctoday = new w2p_Utilities_Date();
$today = $ctoday->format(FMT_TIMESTAMP_DATE);
$date = w2PgetParam($_GET, 'date', $today);
// establish the focus 'date'
$this_day = new w2p_Utilities_Date($date);
$dd = $this_day->getDay();
$mm = $this_day->getMonth();
$yy = $this_day->getYear();

// get current week
$this_week = Date_calc::beginOfWeek($dd, $mm, $yy, FMT_TIMESTAMP_DATE, LOCALE_FIRST_DAY);

// prepare time period for 'events'
$first_time =  clone $this_day;
$first_time->setTime(0, 0, 0);

$last_time = clone $this_day;
$last_time->setTime(23, 59, 59);

$prev_day = new w2p_Utilities_Date(Date_calc::prevDay($dd, $mm, $yy, FMT_TIMESTAMP_DATE));
$next_day = new w2p_Utilities_Date(Date_calc::nextDay($dd, $mm, $yy, FMT_TIMESTAMP_DATE));

// get the list of visible companies
$company = new CCompany();
global $companies;
$companies = $company->getAllowedRecords($AppUI->user_id, 'company_id,company_name', 'company_name');
$companies = arrayMerge(array('0' => $AppUI->_('All')), $companies);

// setup the title block
$titleBlock = new w2p_Theme_TitleBlock('Planner', 'myevo-appointments.png', $m, $m.'.'.$a);
/*
$titleBlock->addCrumb('?m=planner&a=year_view&date=' . $this_day->format(FMT_TIMESTAMP_DATE), 'year view');
$titleBlock->addCrumb('?m=planner&a=month_view&date=' . $this_day->format(FMT_TIMESTAMP_DATE), 'month view');
$titleBlock->addCrumb('?m=planner&a=week_view&date=' . $this_week, 'week view');
$titleBlock->addCrumb('?m=planner&date=' . $this_day->format(FMT_TIMESTAMP_DATE), 'day view');
*/
$titleBlock->addCell($AppUI->_('Company') . ':');
$titleBlock->addCell(arraySelect($companies, 'company_id', 'onChange="document.pickCompany.submit()" class="text"', $company_id), '', '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post" name="pickCompany" accept-charset="utf-8">', '</form>');
$titleBlock->addCell('<input type="submit" class="button" value="' . $AppUI->_('new event') . '">', '', '<form action="?m=planner&a=addedit&date=' . $this_day->format(FMT_TIMESTAMP_DATE) . '" method="post" accept-charset="utf-8">', '</form>');
$titleBlock->show();
?>
<script language="javascript">
function clickDay( idate, fdate ) {
        window.location = './index.php?m=planner&a=day_view&date='+idate+'&tab=0';
}
</script>
<!---  -->
<table class="std view" width="100%" cellspacing="0" cellpadding="4">
    <tr>
        <td valign="top">
            <table border="0" cellspacing="1" cellpadding="2" width="100%" class="motitle">
                <tr>
                    <th width="100%">
                        <?php /*  echo $AppUI->_(htmlspecialchars($this_day->format('%A'), ENT_COMPAT, $locale_char_set)) . ', ' . $this_day->format($df);*/ ?>
                    </th>
                 </tr>
            </table>
<!----->
            <?php
                // tabbed information boxes
                $tabBox = new CTabBox('?m=planner&date=' . $this_day->format(FMT_TIMESTAMP_DATE), W2P_BASE_DIR . '/modules/planner/', $tab);
                $tabBox->add('vw_day_planner', 'Dayplanner');
                $tabBox->add('vw_week_planner', 'Weekplanner');
                $tabBox->add('vw_month_planner', 'Monthplanner');
                $tabBox->add('vw_day_events', 'Day-Events');
                $tabBox->add('vw_day_tasks', 'Day-Tasks');
                $tabBox->add('vw_task_planner', 'Task Date Planning');
                $tabBox->show('',false, 'left',false);
            ?>
        </td>
<?php if ($w2Pconfig['cal_day_view_show_minical']) { ?>
        <td valign="top" width="175">
<?php
	$minical = new w2p_Output_MonthCalendar($this_day);
	$minical->setStyles('minititle', 'minical');
	$minical->showArrows = false;
	$minical->showWeek = false;
	$minical->clickMonth = true;
	$minical->setLinkFunctions('clickDay');

	$first_time = new w2p_Utilities_Date($minical->prev_month);
	$first_time->setDay(1);
	$first_time->setTime(0, 0, 0);
	$last_time = new w2p_Utilities_Date($minical->prev_month);
	$last_time->setDay($minical->prev_month->getDaysInMonth());
	$last_time->setTime(23, 59, 59);

	$links = array();
    require_once (W2P_BASE_DIR . '/modules/planner/links_tasks.php');
	getTaskLinks($first_time, $last_time, $links, 20, $company_id, true);

    require_once (W2P_BASE_DIR . '/modules/planner/links_events.php');
	getEventLinks($first_time, $last_time, $links, 20, true);
	$minical->setEvents($links);

	$minical->setDate($minical->prev_month);

	echo '<table cellspacing="0" cellpadding="0" border="0" width="100%"><tr>';
	echo '<td align="center" >' . $minical->show() . '</td>';
	echo '</tr></table><hr noshade size="1">';

	$first_time = new w2p_Utilities_Date($minical->next_month);
	$first_time->setDay(1);
	$first_time->setTime(0, 0, 0);
	$last_time = new w2p_Utilities_Date($minical->next_month);
	$last_time->setDay($minical->next_month->getDaysInMonth());
	$last_time->setTime(23, 59, 59);
	$links = array();
	getTaskLinks($first_time, $last_time, $links, 20, $company_id, true);
	getEventLinks($first_time, $last_time, $links, 20, true);
	$minical->setEvents($links);

	$minical->setDate($minical->next_month);

	echo '<table cellspacing="0" cellpadding="0" border="0" width="100%"><tr>';
	echo '<td align="center" >' . $minical->show() . '</td>';
	echo '</tr></table><hr noshade size="1">';

	$first_time = new w2p_Utilities_Date($minical->next_month);
	$first_time->setDay(1);
	$first_time->setTime(0, 0, 0);
	$last_time = new w2p_Utilities_Date($minical->next_month);
	$last_time->setDay($minical->next_month->getDaysInMonth());
	$last_time->setTime(23, 59, 59);
	$links = array();
	getTaskLinks($first_time, $last_time, $links, 20, $company_id, true);
	getEventLinks($first_time, $last_time, $links, 20, true);
	$minical->setEvents($links);

	$minical->setDate($minical->next_month);

	echo '<table cellspacing="0" cellpadding="0" border="0" width="100%"><tr>';
	echo '<td align="center" >' . $minical->show() . '</td>';
	echo '</tr></table>';
?>
        </td>
 <?php } ?>
</tr>
</table>