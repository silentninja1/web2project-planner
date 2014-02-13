<?php /* $Id$ $URL$ */
/*
Dayplanner v1.0.3
Klaus Buecher
   

LICENSE

=====================================

The Dayplanner module was built by Klaus Buecher and is released here
under modified BSD license (see GNU.org).

Uses jquery, jqueryui and fullcalendar. Please see their separate licences.
 * 
Copyright (c) 2013 Klaus Buecher (Opto)

No warranty whatsoever is given - use at your own risk. See index.php
 * 

*/
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

global $this_day, $first_time, $last_time, $company_id, $event_filter, $event_filter_list, $AppUI;

// load the event types
$types = w2PgetSysVal('EventType');
$links = array();

$perms = &$AppUI->acl();
$user_id = $AppUI->user_id;
$other_users = false;
$no_modify = false;

if (canView('admin')) {
	$other_users = true;
	if (($show_uid = w2PgetParam($_REQUEST, 'show_user_events', 0)) != 0) {
		$user_id = $show_uid;
		$no_modify = true;
		$AppUI->setState('event_user_id', $user_id);
	}
}

class CTask_ex extends CTask
{
    public function getAllTasksForPeriod($start_date, $end_date, $company_id = 0, $user_id = null)
    {
        global $AppUI;
        $q = new w2p_Database_Query();

        // convert to default db time stamp
        $db_start = $start_date->format(FMT_DATETIME_MYSQL);
        $db_end = $end_date->format(FMT_DATETIME_MYSQL);

        // Allow for possible passing of user_id 0 to stop user filtering
        if (!isset($user_id)) {
            $user_id = $AppUI->user_id;
        }

        // check permissions on projects
        $proj = new CProject();
        $task_filter_where = $proj->getAllowedSQL($AppUI->user_id, 't.task_project');
        // exclude read denied projects
        $deny = $proj->getDeniedRecords($AppUI->user_id);
        // check permissions on tasks
        $obj = new CTask();
        $allow = $obj->getAllowedSQL($AppUI->user_id, 't.task_id');

        $q->addTable('tasks', 't');
        if ($user_id) {
            $q->innerJoin('user_tasks', 'ut', 't.task_id=ut.task_id');
        }
        $q->innerJoin('projects', 'projects', 't.task_project = projects.project_id');
        $q->innerJoin('companies', 'companies', 'projects.project_company = companies.company_id');
        $q->leftJoin('project_departments', '', 'projects.project_id = project_departments.project_id');
        $q->leftJoin('departments', '', 'departments.dept_id = project_departments.department_id');

        $q->addQuery('DISTINCT t.task_id, t.task_name, t.task_start_date, t.task_end_date, t.task_percent_complete, t.task_duration' . ', t.task_duration_type, projects.project_color_identifier AS color, projects.project_name, t.task_milestone, task_description, task_type, company_name, task_access, task_owner');
        $q->addWhere('task_status > -1' . ' AND (task_start_date <= \'' . $db_end . '\'  AND t.task_percent_complete<100  OR task_end_date = \'0000-00-00 00:00:00\' OR task_end_date = NULL )');
        $q->addWhere('project_active = 1');
        if (($template_status = w2PgetConfig('template_projects_status_id')) != '') {
            $q->addWhere('project_status <> ' . $template_status);
        }
        if ($user_id) {
            $q->addWhere('ut.user_id = ' . (int) $user_id);
        }

        if ($company_id) {
            $q->addWhere('projects.project_company = ' . (int) $company_id);
        }
        if (count($task_filter_where) > 0) {
            $q->addWhere('(' . implode(' AND ', $task_filter_where) . ')');
        }
        if (count($deny) > 0) {
            $q->addWhere('(t.task_project NOT IN (' . implode(', ', $deny) . '))');
        }
        if (count($allow) > 0) {
            $q->addWhere('(' . implode(' AND ', $allow) . ')');
        }
        $q->addOrder('t.task_start_date');

        // assemble query
        $tasks = $q->loadList(-1, 'task_id');

        // check tasks access
        $result = array();
        foreach ($tasks as $key => $row) {
            $obj->load($row['task_id']);
            $canAccess = $obj->canAccess();
            if (!$canAccess) {
                continue;
            }
            $result[$key] = $row;
        }
        // execute and return
        return $result;
    }

}


//set the dates for week view
// get the passed timestamp (today if none)
$date = w2PgetParam($_GET, 'date', null);

$today = new w2p_Utilities_Date();
$today = $today->format(FMT_TIMESTAMP_DATE);

// establish the focus 'date'
$this_week = new w2p_Utilities_Date($date);
$dd = $this_week->getDay();
$mm = $this_week->getMonth();
$yy = $this_week->getYear();

// prepare time period for 'events'
$first_time = new w2p_Utilities_Date(Date_Calc::beginOfWeek($dd, $mm, $yy, FMT_TIMESTAMP_DATE, LOCALE_FIRST_DAY));
$first_time->setTime(0, 0, 0);
$last_time = new w2p_Utilities_Date(Date_Calc::endOfWeek($dd, $mm, $yy, FMT_TIMESTAMP_DATE, LOCALE_FIRST_DAY));
$last_time->setTime(23, 59, 59);

$prev_week = new w2p_Utilities_Date(Date_Calc::beginOfPrevWeek($dd, $mm, $yy, FMT_TIMESTAMP_DATE, LOCALE_FIRST_DAY));
$next_week = new w2p_Utilities_Date(Date_Calc::beginOfNextWeek($dd, $mm, $yy, FMT_TIMESTAMP_DATE, LOCALE_FIRST_DAY));
// assemble the links for the events
$events = CEvent::getEventsForPeriod($first_time, $last_time, $event_filter, $user_id);
$events2 = array();
$tasks = CTask_ex::getAllTasksForPeriod($first_time, $last_time, $company_id);


$start_hour = w2PgetConfig('cal_day_start');
$end_hour = w2PgetConfig('cal_day_end');
foreach ($events as $row) {
    $start = new w2p_Utilities_Date($row['event_start_date']);
	$end = new w2p_Utilities_Date($row['event_end_date']);
	$events2[$start->format('%H%M%S')][] = $row;

	if ($start_hour > $start->format('%H')) {
		$start_hour = $start->format('%H');
	}
	if ($end_hour < $end->format('%H')) {
		$end_hour = $end->format('%H');
	}
}

$tf = $AppUI->getPref('TIMEFORMAT');

$dayStamp = $this_day->format(FMT_TIMESTAMP_DATE);

$start = $start_hour;
$end = $end_hour;
$inc = w2PgetConfig('cal_day_increment');

if ($start === null)
	$start = 8;
if ($end === null)
	$end = 17;
if ($inc === null)
	$inc = 15;

$this_day->setTime($start, 0, 0);

?>

<table border="0" cellspacing="0" cellpadding="2" width="100%" class="motitle">
<tr>
	<td>
		<a href="<?php echo '?m=planner&tab=1&date=' . $prev_week->format(FMT_TIMESTAMP_DATE); ?>"><img src="<?php echo w2PfindImage('prev.gif'); ?>" width="16" height="16" alt="pre" border="0"></a>
	</td>
	<th width="100%">
		<span style="font-size:12pt"><?php echo $AppUI->_('Week') . ' ' . $first_time->format('%U - %Y') . ' - ' . $AppUI->_($first_time->format('%B')); ?></span>
	</th>
	<td>
		<a href="<?php echo '?m=planner&tab=1&date=' . $next_week->format(FMT_TIMESTAMP_DATE); ?>"><img src="<?php echo w2PfindImage('next.gif'); ?>" width="16" height="16" alt="next" border="0"></a>
	</td>
</tr>
</table>

<?php

$html = '
<form action="'.$_SERVER['REQUEST_URI'].'" method="post" name="pickFilter" accept-charset="utf-8">';
$html .= $AppUI->_('Event Filter') . ':' . arraySelect($event_filter_list, 'event_filter', 'onChange="document.pickFilter.submit()" class="text"', $event_filter, true);
if ($other_users) {
	$html .= $AppUI->_('Show Events for') . ':' . '<select name="show_user_events" onchange="document.pickFilter.submit()" class="text">';

	if (($rows = w2PgetUsersList())) {
		foreach ($rows as $row) {
			if ($user_id == $row['user_id'])
				$html .= '<option value="' . $row['user_id'] . '" selected="selected">' . $row['contact_first_name'] . ' ' . $row['contact_last_name'];
			else
				$html .= '<option value="' . $row['user_id'] . '">' . $row['contact_first_name'] . ' ' . $row['contact_last_name'];
		}
	}
	$html .= '</select>';

}
$w2p_base_url=W2P_BASE_URL;
require_once (W2P_BASE_DIR . '/modules/events/links_events.php');
//<script type='text/javascript' src='../jquery/jquery-1.8.1.min.js'></script>

$html .= '</form>';

$html.='<br>';
$html .="<link rel='stylesheet' type='text/css' href='$w2p_base_url/modules/planner/fullcalendar/fullcalendar.css' />";
$html .="<link rel='stylesheet' type='text/css' href='$w2p_base_url/modules/planner/fullcalendar/fullcalendar.print.css' media='print' />";
/*$html .="<script type='text/javascript' src='../jquery/jquery-ui-1.8.23.custom.min.js'></script>";
$html .="<script type='text/javascript' src='../fullcalendar/fullcalendar.min.js'></script>";
*/




$html.="<script type='text/javascript'>

	$(document).ready(function() {
	
		var date = new Date();
		var d = date.getDate();
		var m = date.getMonth();
		var y = date.getFullYear();
// have function to post data
function post_to_url(path, params, method) {
  method = method || 'post'; // Set method to post by default, if not specified.

  // The rest of this code assumes you are not using a library.
  // It can be made less wordy if you use one.
  var form = document.createElement('form');
  form.setAttribute('method', method);
  form.setAttribute('action', path);

  for(var key in params) {
    var hiddenField = document.createElement('input');
    hiddenField.setAttribute('type', 'hidden');
    hiddenField.setAttribute('name', key);
    hiddenField.setAttribute('value', params[key]);

    form.appendChild(hiddenField);
  }

  document.body.appendChild(form);    // Not entirely sure if this is necessary
  form.submit();
}

/* initialize the external events
		-----------------------------------------------------------------*/
/*	
		$('#external-events div.external-event').each(function() {
		
			// create an Event Object (http://arshaw.com/fullcalendar/docs/event_data/Event_Object/)
			// it doesn't need to have a start or end
			var eventObject = {
				title: $.trim($(this).text()) // use the element's text as the event title
			};
			
			// store the Event Object in the DOM element so we can get to it later
			$(this).data('eventObject', eventObject);
			
			// make the event draggable using jQuery UI
			$(this).draggable({
				zIndex: 999,
				revert: true,      // will cause the event to go back to its
				revertDuration: 0  //  original position after the drag
			});
			
		});
 
*/		
		$('#calendar').fullCalendar({
			header: {
				left: '',//prev,next today',
				center: 'title',
				right: ''//month,agendaWeek,agendaDay'
			},
                        year: $first_time->year,
                        month: $first_time->month -1,
                        date : $first_time->day,
			defaultView: 'agendaWeek',
			editable: true,
                        slotMinutes:15,
                        defaultEventMinutes:15, 
                        selectable: true,
			selectHelper: true,

			selectable: true,
			selectHelper: true,
                        select: function(starttime, endtime, evallDay) {
				var evtitle = prompt('Event Title:');
				if (evtitle) {
                                var eventobj={title:evtitle, start:starttime, end:endtime, allDay:evallDay,id:0};
     
/**/
        $.post('$w2p_base_url/index.php?m=planner&a=testev&suppressHeaders=true', { id: 0  , event_name: evtitle, private: 1,
           starttime: $.fullCalendar.formatDate( eventobj.start, 'yyyy-MM-dd HH:mm:ss' ),
           endtime:  $.fullCalendar.formatDate( eventobj.end, 'yyyy-MM-dd HH:mm:ss' )}, function(data) {
 //      alert('Data Loaded: ' + data);
        if (data=='0') {
        alert('Could not save to web2project. Can not display event at this time');
        }
       else {
         evid=Number(data);
 //       alert(data);
 //       alert('w2p-id:'+ evid);
        eventobj.id=evid;
//RENDER

// render the event on the calendar
				// the last `true` argument determines if the event sticks (http://arshaw.com/fullcalendar/docs/event_rendering/renderEvent/)
				$('#calendar').fullCalendar('renderEvent', eventobj, true);




//END RENDER        
}
}   
       



);


				}
				$('#calendar').fullCalendar('unselect');
			},
                 
eventRender: function(event, element) {    


//ref 1

     element.bind('dblclick',{elem:element}, function(jevent) {
//         alert('double click!');
     jevent.data.elem.find('.fc-event-skin').css('background-color', 'green');
      });

        element.qtip({ content: event.description,
        hide: { when: 'mouseout', fixed: true },
   //     delay: 400,
         position: {
            corner: {
             target: 'bottomLeft',
             tooltip: 'topLeft'
                    }
                   }
});
	element.find('span.fc-event-title').html(element.find('span.fc-event-title').text());					  
//             element.find('.fc-event-title').append('<br/>' + event.description);       //display description inside event
},
			events: [";

foreach ($events as $row)
{
        $start = new w2p_Utilities_Date($row['event_start_date']);
	$end = new w2p_Utilities_Date($row['event_end_date']);
 //       if ($row[event_description])  $descrpt= row[event_description] else $descrpt= ''; 
 			$href = '?m=events&a=view&event_id=' . $row['event_id'];
 			$href_edit = '?m=events&a=addedit&event_id=' . $row['event_id'];
      $descript="Links:  ";
 			$descript .= $href ? '<a href="' . $href . '" class="event">' : '';
			$descript .= "<b>View</b>";//$row['event_name'];
			$descript .= $href ? '</a>' : '';
 			$descript .= $href_edit ? '<a href="' . $href_edit . '" class="event">' : '';
			$descript .= "<b>         Edit   </b>";//$row['event_name'];
			$descript .= $href_edit ? '</a>' : '';
			if ($row['event_project'])  {
       			$href_proj = '?m=projects&a=view&project_id=' . $row['event_project'];
 			$descript .= $href_proj ? '<a href="' . $href_proj . '" class="event">' : '';
			$descript .= "<b>             View_parent_project</b>";//$row['event_name'];
			$descript .= $href_proj ? '</a>' : '<br><br>';
};
			if ($row['event_task'])  {
 			$href_task = '?m=tasks&a=view&task_id=' . $row['event_task'];
 			$descript .= $href_task ? '<a href="' . $href_task . '" class="event">' : '';
			$descript .= "<b>         View parent task</b>";//$row['event_name'];
			$descript .= $href_task ? '</a>' : '';
      };
      if ($href | $href_edit  |$href_task |$href_proj ) $descript.="<br><br>";
//      $descript.="<br>".w2PshowImage('event' . $row['event_type'] . '.png', 16, 16, '', '', 'calendar'). $AppUI->_($types[$row['event_type']])."<br>";
        $descript.=getEventTooltip($row['event_id']);
      
        //Javascript date January is 0, Dec. is 11
        $html.="{title: '$row[event_name]',
            start: new Date(Number($start->year), Number($start->month-1),Number($start->day),Number($start->hour),Number($start->minute)),
            end: new Date(Number($end->year), Number($end->month-1),($end->day),Number($end->hour),Number($end->minute)),
 					allDay: false,
// 					color:'green',
                                        id: Number($row[event_id]),
                                            description: '$descript',
                                        edited: false    
               },
        ";

};

$html.="	
    
               
			],

			droppable: true, // this allows things to be dropped onto the calendar !!!
    eventResize: function(event,dayDelta,minuteDelta,revertFunc) {

        event.edited=true;
        $.post('$w2p_base_url/index.php?m=planner&a=testev&suppressHeaders=true', { id: event.id, 
           starttime: $.fullCalendar.formatDate( event.start, 'yyyy-MM-dd HH:mm:ss' ),endtime:  $.fullCalendar.formatDate( event.end, 'yyyy-MM-dd HH:mm:ss' ) }, function(data) {
 //       alert('Data Loaded: ' + data);
        if (data=='0') {
        alert('could not save to w2p. Will revert to original event data');
         revertFunc();};
   });
   if (event.edited==true) edittxt='yes' ; else edittxt='no'
 /*
 alert(
            'The end date of ' + event.title + '  id:' + event.id + '  has been moved ' +
            dayDelta + ' days and ' +
            minuteDelta + ' minutes.'
            + 'edited='+ edittxt
        );

        if (!confirm('is this okay?')) {
            revertFunc();
        }
        else
        {
        }
*/
    },

    eventDrop: function(event,dayDelta,minuteDelta,allDay,revertFunc) {

                event.edited=true;
        $.post('$w2p_base_url/index.php?m=planner&a=testev&suppressHeaders=true&user_id=1', { id: event.id, 
           starttime: $.fullCalendar.formatDate( event.start, 'yyyy-MM-dd HH:mm:ss' ),endtime:  $.fullCalendar.formatDate( event.end, 'yyyy-MM-dd HH:mm:ss' ) }, function(data) {
 //       alert('Data Loaded: ' + data);
        if (data=='0') {
        alert('could not save to web2project. Will revert to original event data');
         revertFunc();};
   });
 /*
 if (event.edited==true) edittxt='yes' ; else edittxt='no';
alert(
            event.title + ' was moved ' +
            dayDelta + ' days and ' +
            minuteDelta + ' minutes.'
        );

        if (allDay) {
            alert('Event is now all-day');
        }else{
            alert('Event has a time-of-day');
        }

        if (!confirm('Are you sure about this change?')) {
            revertFunc();
        }
*/
    },



drop: function(date, allDay) { // this function is called when something is dropped
			
				// retrieve the dropped element's stored Event Object
				var originalEventObject = $(this).data('eventObject');
				
				// we need to copy it, so that multiple events don't have a reference to the same object
				var copiedEventObject = $.extend({}, originalEventObject);
				                        //
				// assign it the date that was reported
				copiedEventObject.start = date;
				copiedEventObject.allDay = false; //allDay;
				copiedEventObject.end =new Date(date.getTime()+900000);//.setTime( (date.getTime() + 900000)); 
//                                alert(date.getTime());alert(copiedEventObject.end);

//      $(this).qtip({ content: 'Event will be displayed after insert into to database',
//});

//        $(this).qtip('show');

        $.post('$w2p_base_url/index.php?m=planner&a=testev&suppressHeaders=true', { id: 0, event_name: copiedEventObject.title, private: 1, event_description: copiedEventObject.description,
           starttime: $.fullCalendar.formatDate( copiedEventObject.start, 'yyyy-MM-dd HH:mm:ss' ),endtime:  $.fullCalendar.formatDate( copiedEventObject.end, 'yyyy-MM-dd HH:mm:ss' ) }, function(data) {
  //      alert('Data Loaded: ' + data);
//        $(this).qtip('hide');
        if (data=='0') {
        alert('Could not save to web2project. Can not display event at this time');
        }
        else {
         copiedEventObject.id=Number(data);
 //       alert(data);
 //       alert( copiedEventObject.id);
       
//RENDER

// render the event on the calendar
				// the last `true` argument determines if the event sticks (http://arshaw.com/fullcalendar/docs/event_rendering/renderEvent/)
				$('#calendar').fullCalendar('renderEvent', copiedEventObject, true);
				
				// is the remove after drop checkbox checked?
				if ($('#drop-remove').is(':checked')) {
					// if so, remove the element from the Draggable Events list
					$(this).remove();
				}
//END RENDER
        };
   });



				
			}

});
		
	});

</script>";





/*

$html .= '<table cellspacing="1" cellpadding="2" border="0" width="100%" class="tbl">';
$rows = 0;
for ($i = 0, $n = ($end - $start) * 60 / $inc; $i < $n; $i++) {
	$html .= '<tr>';

	$tm = $this_day->format($tf);
	$html .= '<td width="1%" align="right" nowrap="nowrap">' . ($this_day->getMinute() ? $tm : '<b>' . $tm . '</b>') . '</td>';

	$timeStamp = $this_day->format('%H%M%S');
	if (isset($events2[$timeStamp])) {
		$count = count($events2[$timeStamp]);
		for ($j = 0; $j < $count; $j++) {
			$row = $events2[$timeStamp][$j];

			$et = new w2p_Utilities_Date($row['event_end_date']);
			$rows = (($et->getHour() * 60 + $et->getMinute()) - ($this_day->getHour() * 60 + $this_day->getMinute())) / $inc;

			$href = '?m=events&a=view&event_id=' . $row['event_id'];
			$alt = $row['event_description'];

			$html .= '<td class="event" rowspan="' . $rows . '" valign="top">';

			$html .= '<table cellspacing="0" cellpadding="0" border="0"><tr>';
			$html .= '<td>' . w2PshowImage('event' . $row['event_type'] . '.png', 16, 16, '', '', 'calendar');
			$html .= '</td><td>&nbsp;<b>' . $AppUI->_($types[$row['event_type']]) . '</b></td></tr></table>';
			$html .= w2PtoolTip($row['event_name'], getEventTooltip($row['event_id']), true);
			$html .= $href ? '<a href="' . $href . '" class="event">' : '';
			$html .= $row['event_name'];
			$html .= $href ? '</a>' : '';
			$html .= w2PendTip();
			$html .= '</td>';
		}
	} else {
		if (--$rows <= 0) {
			$html .= '<td></td>';
		}
	}

	$html .= '</tr>';

	$this_day->addSeconds(60 * $inc);
}

$html .= '</table>';  */
/*
 * $html.="<style type='text/css'>

	body {
		margin-top: 40px;
		text-align: center;
		font-size: 14px;
		font-family: 'Lucida Grande',Helvetica,Arial,Verdana,sans-serif;
		}

	#calendar {
		width: 900px;
		margin: 0 auto;
		}

</style>";
*/
$html.="<style type='text/css'>

	body {
		margin-top: 40px;
		text-align: center;
		font-size: 14px;
		}
		
	#wrap {
		width: 1100px;
		margin: 0 auto;
		}
		
	#external-events {
		float: left;
		width: 150px;
                height: 700px;
                overflow: scroll;
		padding: 0 10px;
		border: 1px solid #ccc;
		background: #eee;
		text-align: left;
		}
		
	#external-events h4 {
		font-size: 16px;
		margin-top: 0;
		padding-top: 1em;
		}
		
	.external-event { /* try to mimick the look of a real event */
		margin: 10px 0;
		border: 1px solid #000000;
		padding: 2px 4px;
		background: #3366CC;
		color: #000000;//#fff;
		font-size: .95em;
		cursor: pointer;
                word-wrap: break-word; /* Firefox & IE */
//                word-break: break-all; /* Chrome */                }
}



	.external-eventoverdue { /* try to mimick the look of a real event */
		margin: 10px 0;
		border: 1px solid #000000;
		padding: 2px 4px;
		background: #CC6666;
		color: #fff;
		font-size: .85em;
		cursor: pointer;
                word-wrap: break-word; /* Firefox & IE */
//                word-break: break-all; /* Chrome */                }
}


	.external-event-notstarted { /* try to mimick the look of a real event */
		margin: 10px 0;
		border: 1px solid #000000;
		padding: 2px 4px;
		background: #ffeebb;
		color: #fff;
		font-size: .85em;
		cursor: pointer;
                word-wrap: break-word; /* Firefox & IE */
//                word-break: break-all; /* Chrome */                }
}




	.external-event-ontime { /* try to mimick the look of a real event */
		margin: 10px 0;
		border: 1px solid #000000;
		padding: 2px 4px;
		background: #e6eedd;
		color: #fff;
		font-size: .85em;
		cursor: pointer;
                word-wrap: break-word; /* Firefox & IE */
//                word-break: break-all; /* Chrome */                }
}


		
	#external-events p {
		margin: 1.5em 0;
		font-size: 11px;
		color: #666;
		}
		
	#external-events p input {
		margin: 0;
		vertical-align: middle;
		}

	#calendar {
		float: right;
		width: 900px;
		}

</style>";
//$html.="<div id='calendar'></div>";
$event1="Event1";

/*<div class='external-event'>My Event 1</div>
<div class='external-event'>$event1</div>
*/

$html.="<div id='wrap'>

<div id='external-events'>
<h3>Drag Tasks into Calendar</h3>
<p>doubleclick task to set task progress<p>
<!---<div class='external-event'>My Event 3</div>-->
";
  //id='".$task['task_id']."
foreach ($tasks as $task) {
	   $taskclass = w2pFindTaskComplete($task['task_start_date'], $task['task_end_date'], $task['task_percent_complete']);
    $tname=$task[task_name]."...".$task[project_name];
    $task_descr=  $task['task_description'] ;
 			$href = '?m=tasks&a=view&task_id=' . $task['task_id'];
 			$href_edit = '?m=tasks&a=addedit&task_id=' . $task['task_id'];
 			$href_task_log = '?m=tasks&a=view&tab=1&task_id=' . $task['task_id'];
     $descript="Links:  ";
			$descript .= $href ? '<a href="' . $href . '" class="event">' : '';
			$descript .= "<b>View</b>";//$row['event_name'];
			$descript .= $href ? '</a>' : '';
 			$descript .= $href_edit ? '<a href="' . $href_edit . '" class="event">' : '';
			$descript .= "<b>         Edit   </b>";//$row['event_name'];
			$descript .= $href_edit ? '</a>' : '';

 			$href_proj = '?m=projects&a=view&project_id=' . $task['task_project'];
 			$descript .= $href_proj ? '<a href="' . $href_proj . '" class="event">' : '';
			$descript .= "<b>             View_parent_project</b>";//$row['event_name'];
			$descript .= $href_proj ? '</a>' : '<br><br>';
 			$descript .= $href_task_log ? '<a href="' . $href_task_log . '" class="event">' : '';
			$descript .= "<b>     Create_Task_log</b>";
			$descript .= $href_task_log ? '</a><br><br>' : '';
// 			$descript .= '<p><span  id="tttt'.$task['task_id'].'">set to 100%</span></p>';

// 			$descript .= '<a href="Javascript:void();"  onClick="$(this).alert("ww");return false;">set to 100%</a>';
 /*
			if ($row['event_task'])  {
 			$href_task = '?m=tasks&a=view&task_id=' . $row['event_task'];
 			$descript .= $href_task ? '<a href="' . $href_task . '" class="event">' : '';
			$descript .= "<b>         View parent task</b>";//$row['event_name'];
			$descript .= $href_task ? '</a>' : '';
      };
      if ($href | $href_edit  |$href_task |$href_proj ) $descript.="<br><br>";
*/    
      $descript.="<br><br>";
      $descript.=$task_descr;
    $html.="<div id='".$task['task_id']."'  class='external-event'>$tname</div>
            ";
   $taskoverdue=strcmp($taskclass,'late');
   $taskontime=strcmp($taskclass,'active');
   $tasknotstarted=strcmp($taskclass,'notstarted');
   $html.="<script type='text/javascript'>
 /*   */
 



      var calelem=$('#".$task['task_id']."');
   calelem.dblclick(function(){ var perc=prompt('set task progress in : ', '100');
   if (perc!=null && perc!='') {
   
   
         $.post('$w2p_base_url/index.php?m=planner&a=test&suppressHeaders=true&user_id=1', { task_id: ".$task['task_id'].", 
           perc_complete: perc }, function(data) {
//        alert('Data Loaded: ' + data);
        if (data==0) {
        alert('could not save task progress to web2project. ');}
        else
 {
 
      if (perc==100) $('#".$task['task_id']."').hide();

 };
/**/
   });
  
   
   } });
// $('#tttt14').click(function(){ alert('hhh'); });

  		var eventObject = {
				title: '$tname', // use the element's text as the event title
				description: '$task_descr',  //$.trim($(".$task['task_description'].")
				task_id: '$task[task_id]',
				project_id: '$task[task_project]',
				qtip:'$descript'
			};
 			// store the Event Object in the DOM element so we can get to it later
			calelem.data('eventObject', eventObject);  

      if ($taskontime===0) calelem.css('background-color', '#e6eedd');
      if ($tasknotstarted===0) calelem.css('background-color', '#ffeebb');
      if ($taskoverdue===0) calelem.css('background-color', '#CC6666');
 

			calelem.draggable({
				zIndex: 999,
				revert: true,      // will cause the event to go back to its
				revertDuration: 0  //  original position after the drag
			});

       calelem.qtip({ content: eventObject.qtip,
        hide: { when: 'mouseout', fixed: true },
   //     delay: 400,
         position: {
            corner: {
             target: 'bottomLeft',
             tooltip: 'topLeft'
                    }
                   }
});



</script>";


}
$html.="<p>
<input type='checkbox' id='drop-remove' /> <label for='drop-remove'>remove after drop</label>
</p>
</div>

<div id='calendar'></div>

<div style='clear:both'></div>
</div>
";
echo $html;



//ref1:  http://stackoverflow.com/questions/7918301/fullcalendar-doesnt-render-color-when-using-eventrender
