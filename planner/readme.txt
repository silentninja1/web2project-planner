Planner/Dayplanner


Planner v1.0.6
Klaus Buecher
   

LICENSE

=====================================

The planner module was built by Klaus Buecher and is released here
under modified BSD license (see GNU.org).

Uses jquery, jqueryui, jsgantt and fullcalendar. Please see their separate licences.
 * 
Copyright (c) 2013/2014 Klaus Buecher (Opto)






This module is intended to give support for planning purposes.

Project tools:
show table of tasks. They can directly be edited inside table. Aloo displays search fields.


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







