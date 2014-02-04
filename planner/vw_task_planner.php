<?php /* $Id$ $URL$ */
/*
Planner v1.0.3 ->Task date planner
Klaus Buecher
   


LICENSE

=====================================

The Taskplanner module was built by Klaus Buecher and is released here
under modified BSD license (see GNU.org).

Uses jquery, jqueryui and jsgantt. Please see their separate licences.

No warranty whatsoever is given - use at your own risk. See index.php

 * 
Copyright (c) 2013 Klaus Buecher (Opto)

*/
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

global $this_day, $prev_day, $next_day, $first_time, $last_time, $company_id, $event_filter, $event_filter_list, $AppUI;

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

    public function getTaskColor($start_date, $end_date, $percent) {
    return 'ff00ff';
     $class = w2pFindTaskComplete($start_date, $end_date, $percent);
/*
     late #CC6666
  not started  #ffeebb
  ontime  #e6eedd

//    if ($class = 'done') { return 'done'; }
    if ($class = '')   { return 'e6eedd'; }
    if ($class = 'late')     { return 'CC6666'; }
 //   if ($class = 'active' ) { return 'active'; }
    if ($class = 'notstarted') { return 'ffeebb'; }
*/    return 'e6eedd';
 
 }

}
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
$dayFormat=FMT_TIMESTAMP_DATE;

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



<?php


$html = '
<form action="'.$_SERVER['REQUEST_URI'].'" method="post" name="pickFilter" accept-charset="utf-8">';
//$html .= $AppUI->_('Select Project') . ':' . arraySelect($event_filter_list, 'event_filter', 'onChange="document.pickFilter.submit()" class="text"', $event_filter, true);
if ($other_users) {
//	$html .= $AppUI->_('Show Events for') . ':' . '<select name="show_user_events" onchange="document.pickFilter.submit()" class="text">';

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
////<script language="javascript" type="text/javascript" src="./modules/planner/js/jquery.js"></script>
//<script language="javascript" src="./modules/planner/js/graphics.js"></script>
//
//<link rel="stylesheet" href="./modules/planner/js/defaultTheme.css"></script>>
//<script language="javascript" type="text/javascript" src="./modules/planner/js/jquery.fixedheadertable.min.js"></script>
$html.='<br>

      

<script language="javascript" type="text/javascript" src="./modules/planner/js/jquery-ui.js"></script>
<link rel="stylesheet" href="./modules/planner/js/jquery-ui.css"></script>



<link rel="stylesheet" type="text/css" href="./modules/planner/js/jsgantt.css"/>
<script language="javascript" src="./modules/planner/js/jsgantt.js"></script>


 
<style type="text/css">
<!--
.style1 {color: #0000FF}

.roundedCorner{display:block}
.roundedCorner *{
  display:block;
  height:1px;
  overflow:hidden;
  font-size:.01em;
  background:#0061ce}
.roundedCorner1{
  margin-left:3px;
  margin-right:3px;
  padding-left:1px;
  padding-right:1px;
  border-left:1px solid #91bbe9;
  border-right:1px solid #91bbe9;
  background:#3f88da}
.roundedCorner2{
  margin-left:1px;
  margin-right:1px;
  padding-right:1px;
  padding-left:1px;
  border-left:1px solid #e5effa;
  border-right:1px solid #e5effa;
  background:#307fd7}
.roundedCorner3{
  margin-left:1px;
  margin-right:1px;
  border-left:1px solid #307fd7;
  border-right:1px solid #307fd7;}
.roundedCorner4{
  border-left:1px solid #91bbe9;
  border-right:1px solid #91bbe9}
.roundedCorner5{
  border-left:1px solid #3f88da;
  border-right:1px solid #3f88da}
.roundedCornerfg{
  background:#0061ce;}


-->
</style>



<div style="position:relative" class="gantt" id="GanttChartDIV"></div>
                                                                
       ';



 $html.=


 "
<script language='javascript' type='text/javascript'>       
  // here's all the html code neccessary to display the chart object

  // Future idea would be to allow XML file name to be passed in and chart tasks built from file.

  
  var g = new JSGantt.GanttChart('g',document.getElementById('GanttChartDIV'), 'day');
 
	g.setShowRes(0); // Show/Hide Responsible (0/1)
	g.setShowDur(0); // Show/Hide Duration (0/1)
	g.setShowComp(0); // Show/Hide % Complete(0/1)
   g.setCaptionType('Resource');  // Set to Show Caption (None,Caption,Resource,Duration,Complete)
 
  if( g ) {
    // Parameters             (pID, pName,                  pStart,      pEnd,        pColor,   pLink,          pMile, pRes,  pComp, pGroup, pParent, pOpen, pDepend, pCaption)
	
	// You can also use the XML file parser JSGantt.parseXML('project.xml',g)       
//  g.setDateInputFormat($dayFormat)";
echo $html; 
foreach ($tasks as $task) {
//	   $taskclass = w2pFindTaskComplete($task['task_start_date'], $task['task_end_date'], $task['task_percent_complete']);
    $tname=$task[task_name]."...".$task[project_name];
    $tid=$task['task_id'];
    $t_start=substr($task['task_start_date'],5,2).'/'.substr($task['task_start_date'],8,2).'/'.substr($task['task_start_date'],0,4);
    $t_end=substr($task['task_end_date'],5,2).'/'.substr($task['task_end_date'],8,2).'/'.substr($task['task_end_date'],0,4);
$t_s_m= substr($task['task_start_date'],5,2);
$t_s_d= substr($task['task_start_date'],8,2);
$t_s_y=substr($task['task_start_date'],0,4);
$t_e_m= substr($task['task_end_date'],5,2);
$t_e_d= substr($task['task_end_date'],8,2);
$t_e_y=substr($task['task_end_date'],0,4);
$task_color= CTask_ex::getTaskColor($task['task_start_date'],$task['task_end_date'],$task['task_percent_complete']);
$perc_compl=$task['task_percent_complete'];
/*    $times=array(
    'start'=>$t_start,'end'=>$t_end);     
    
        times = '<?php echo "eeeee";//json_encode($$times); 

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
*/
//    $html.="g.AddTaskItem(new JSGantt.TaskItem($task['task_id'], '$tname',       '8/9/2008',  '8/29/2008', 'ff0000', 'http://help.com', 0, 'Anyone',   60, 0, 12, 1, 0, 'This is another caption'));");"
$html.="
 var t_s= String('$t_start');
 var t_e= String('$t_end');
t_s=String(Number($t_s_m))+'/'+String(Number($t_s_d))+'/'+String(Number($t_s_y));//'7/25/2008';
t_e=String(Number($t_e_m))+'/'+String(Number($t_e_d))+'/'+String(Number($t_e_y));//'7/25/2008';
//var gg=times;
//alert ('$t_end');
    // Parameters             (pID, pName,                  pStart,      pEnd,        pColor,   pLink,          pMile, pRes,  pComp, pGroup, pParent, pOpen, pDepend, pCaption)
//    g.AddTaskItem(new JSGantt.TaskItem(Number($tid), '$tname',      t_s,  t_e, 'ff0000', '', 0, 'Brian',         60, 0, 12, 1,121));
    g.AddTaskItem(new JSGantt.TaskItem($tid,  '$tname',     t_s, t_e, '$task_color', 'http://help.com', 0, 'Brian',    $perc_compl, 0, 3, 1, '','Caption 1'));

//    g.AddTaskItem(new JSGantt.TaskItem(Number(5), 'tname',      t_s,  t_e, 'ff0000', '', 0, 'Brian',         60, 0, 12, 1,121));
//S    g.AddTaskItem(new JSGantt.TaskItem($tid,  '$tname', t_s.toString(), t_e.toString(), '00ff00', '', 0, 'Shlomy',   40, 0, 3, 1));
//    g.AddTaskItem(new JSGantt.TaskItem(32,  'Calculate Chart Size', '8/15/2013', '8/24/2013', '00ff00', 'http://help.com', 0, 'Shlomy',   40, 0, 3, 1));
";

};  
//    g.AddTaskItem(new JSGantt.TaskItem(1,   'Define Chart API',     '',          '',          'ff0000', 'http://help.com', 0, 'Brian',     0, 1, 0, 1));
//    g.AddTaskItem(new JSGantt.TaskItem(11,  'Chart Object',         '7/20/2008', '12/26/2008', 'ff00ff', 'http://www.yahoo.com', 1, 'Shlomy',  100, 0, 1, 1));
//    g.AddTaskItem(new JSGantt.TaskItem(12,  'Task Objects',         '',          '',          '00ff00', '', 0, 'Shlomy',   40, 1, 1, 1));
//    g.AddTaskItem(new JSGantt.TaskItem(121, 'Constructor Proc',     '7/21/2008', '12/27/2008',  '00ffff', 'http://www.yahoo.com', 0, 'Brian T.', 60, 0, 12, 1));
//    g.AddTaskItem(new JSGantt.TaskItem(122, 'Task Variables',       '8/6/2008',  '8/11/2008', 'ff0000', 'http://help.com', 0, 'Brian',         60, 0, 12, 1,121));
//	g.AddTaskItem(new JSGantt.TaskItem(123, 'Task by Minute/Hour',       '8/6/2008',  '8/11/2008 12:00', 'ffff00', 'http://help.com', 0, 'Ilan',         60, 0, 12, 1,121));
//    g.AddTaskItem(new JSGantt.TaskItem(124, 'Task Functions',       '8/9/2008',  '8/29/2008', 'ff0000', 'http://help.com', 0, 'Anyone',   60, 0, 12, 1, 0, 'This is another caption'));
//    g.AddTaskItem(new JSGantt.TaskItem(2,   'Create HTML Shell',    '8/24/2008', '8/25/2008', 'ffff00', 'http://help.com', 0, 'Brian',    20, 0, 0, 1,122));
 //   g.AddTaskItem(new JSGantt.TaskItem(3,   'Code Javascript',      '',          '',          'ff0000', 'http://help.com', 0, 'Brian',     0, 1, 0, 1 ));

/*
for ($icou=1;$icou<2;$icou+=1)  {

$html.="


t_s=String(Number($t_s_m))+'/'+String(Number($t_s_d))+'/'+String(Number($t_s_y));//'7/25/2008';
t_e=String(Number($t_e_m))+'/'+String(Number($t_e_d))+'/'+String(Number($t_e_y));//'7/25/2008';
//    g.AddTaskItem(new JSGantt.TaskItem(31,  'Define Variables',     '7/25/2013', '8/17/2013', 'ff00ff', 'http://help.com', 0, 'Brian',    30, 0, 3, 1, '','Caption 1'));
//    g.AddTaskItem(new JSGantt.TaskItem(31,  'Define Variables',     t_s, t_e, 'ff00ff', 'http://help.com', 0, 'Brian',    30, 0, 3, 1, '','Caption 1'));
//    g.AddTaskItem(new JSGantt.TaskItem(32,  'Calculate Chart Size', '8/15/2008', '8/24/2008', '00ff00', 'http://help.com', 0, 'Shlomy',   40, 0, 3, 1));
";

};

*/
//    g.AddTaskItem(new JSGantt.TaskItem(33,  'Draw Taks Items',      '',          '',          '00ff00', 'http://help.com', 0, 'Someone',  40, 1, 3, 1));
//    g.AddTaskItem(new JSGantt.TaskItem(332, 'Task Label Table',     '8/6/2008',  '8/11/2008', '0000ff', 'http://help.com', 0, 'Brian',    60, 0, 33, 1));
//    g.AddTaskItem(new JSGantt.TaskItem(333, 'Task Scrolling Grid',  '8/9/2008',  '8/20/2008', '0000ff', 'http://help.com', 0, 'Brian',    60, 0, 33, 1));
//    g.AddTaskItem(new JSGantt.TaskItem(34,  'Draw Task Bars',       '',          '',          '990000', 'http://help.com', 0, 'Anybody',  60, 1, 3, 0));
//    g.AddTaskItem(new JSGantt.TaskItem(341, 'Loop each Task',       '8/26/2008', '9/11/2008', 'ff0000', 'http://help.com', 0, 'Brian',    60, 0, 34, 1));
//    g.AddTaskItem(new JSGantt.TaskItem(342, 'Calculate Start/Stop', '9/12/2008', '10/18/2008', 'ff6666', 'http://help.com', 0, 'Brian',    60, 0, 34, 1));
//    g.AddTaskItem(new JSGantt.TaskItem(343, 'Draw Task Div',        '10/13/2008', '10/17/2008', 'ff0000', 'http://help.com', 0, 'Brian',    60, 0, 34, 1));
//    g.AddTaskItem(new JSGantt.TaskItem(344, 'Draw Completion Div',  '10/17/2008', '11/04/2008', 'ff0000', 'http://help.com', 0, 'Brian',    60, 0, 34, 1,'342,343'));
//    g.AddTaskItem(new JSGantt.TaskItem(35,  'Make Updates',         '12/17/2008','2/04/2009','f600f6', 'http://help.com', 0, 'Brian',    30, 0, 3,  1));


  $html.="
//  alert('draw');
  var vMainTable=  g.Draw();	
//  $('#GanttChartDIV').html(vMainTable);
//  alert('dep');
    g.DrawDependencies();
  
 }
   else

  {

    alert('Gannt not defined');

  };
 ";
$html.=
" 
$.ui.draggable.prototype.destroy = function (ul, item) { }; 

 $('[id*=taskbar] ').resizable({maxHeight:13, minHeight:13,   ghost:true,
    resize: function(event, ui) { 
/*

ui.helper - a jQuery object containing the helper element
 ui.originalPosition - {top, left} before resizing started
 ui.originalSize - {width, height} before resizing started
 ui.position - {top, left} current position
 ui.size - {width, height} current size



*/ },

    stop: function( event, ui ) {id = ui.element.attr('id').substr(8); // alert(id);
    

     var vList = g.getList();
     var vTask = g.getArrayLocationByID(id);
      
//
//alert('bar'+ui.originalSize.width);
var len1=vList[vTask].getEndX() - vList[vTask].getStartX();
//alert('task len'+len1);
//alert ('neu'+ui.size.width);
var newLength=    ui.size.width;
var vDayWidth=g.getDayWidth();
var Days=         Math.ceil(newLength/vDayWidth);
//alert ('Days'+Math.ceil(newLength/vDayWidth));
var startd=new Date(vList[vTask].getStart());
//g.renderTask(id);
startd.addDays(Days-1);
//alert('neues Ende: '+startd.toLocaleString());
vList[vTask].setEnd(startd);
//alert ('S: '+ vList[vTask].getStartX());
//alert('E'+ vList[vTask].getEndX());
//g.Draw();
//var aaaa=g.renderTask(id);
//before drawing dependencies, readjust bar-right end to new value
 //          vTaskDiv = document.getElementById('taskbar_'+vID);
//            vBarDiv  = document.getElementById('bardiv_'+vID);
//            vParDiv  = document.getElementById('childgrid_'+vID);

 //           if(vBarDiv) {
  //             vList[i].setStartX( vBarDiv.offsetLeft );
//               vList[i].setStartY( vParDiv.offsetTop+vBarDiv.offsetTop+6 );
               vList[vTask].setEndX(  vList[vTask].getStartX() + newLength);// vBarDiv.offsetLeft + vBarDiv.offsetWidth );
//               vList[i].setEndY( vParDiv.offsetTop+vBarDiv.offsetTop+6 );

            $('#bardiv_'+id).width(ui.size.width);
//          alert('J: '+  $('#bardiv_'+id).width()  );
vDirty=1;
    g.DrawDependencies();
//       alert(vList[vTask].getName());
//set end date in table
//alert(g.getDateDisplayFormat());
var datestr=JSGantt.formatDateStr( vList[vTask].getEnd(), g.getDateDisplayFormat());


//alert(datestr);
//$('#child_'+id).find('td').eq(2).text((Days-1) + ' Days');

$('#child_'+id).find('td').eq(3).text(datestr);
      //  alert($('#'+id).text(id));

//correct date format for post
datestr=JSGantt.formatDateStr( vList[vTask].getEnd(), 'yyyy-mm-dd');
        $.post('$w2p_base_url/index.php?m=planner&a=test&suppressHeaders=true', { task_id: id, do_task: 'resize',
        endtime: datestr}, 
        function(data) {
 //       alert('Data Loaded: ' + data);
        if (data=='0') {
        alert('could not save to w2p.');
//         revertFunc();
 }
   });



    
//end stop function    
    }
    });

//start drag
var xpos; var ypos;
       $('[id*=taskbar] ').draggable({ axis: 'x', scroll:true,
    // get the initial X and Y position when dragging starts
    start: function(event, ui) {
      xpos = ui.position.left;
      ypos = ui.position.top;
    },
            stop: function( event, ui ) {
     id = $(this).attr('id').substr(8); 
//     alert('id:'+id);
     var vList = g.getList();
     var vTask = g.getArrayLocationByID(id);
     var vDayWidth=g.getDayWidth();
      // calculate the dragged distance, with the current X and Y position and the xpos and ypos
      var xmove = ui.position.left - xpos;
      var ymove = ui.position.top - ypos;
     // define the moved direction: right, bottom (when positive), left, up (when negative)
      var xd = xmove >= 0 ? ' To right: ' : ' To left: ';
      var yd = ymove >= 0 ? ' Bottom: ' : ' Up: ';




var newStart=    ui.position.left;
//1alert ('neuleft'+newStart);
var newLength= newStart;//-    oldstart;
//var Days=         Math.ceil(newLength/vDayWidth);
var Days=         (newLength/vDayWidth);
//1alert ('Days'+Days);
var startd=new Date(vList[vTask].getStart());
//1alert ('Startdate'+startd);
//g.renderTask(id);
startd.addDays(Days);
//alert('neues Ende: '+startd.toLocaleString());
var endd=new Date(vList[vTask].getEnd());
endd.addDays(Days);
//alert ('S: '+ vList[vTask].getStartX());
//alert('E'+ vList[vTask].getEndX());
uioffset=  $('#taskbar_'+id).offset();//ui.position;
//alert('oldleft:'+uioffset.left);
//uioffset.left+=   newStart;
//no idea why this indexshifting is necessary - may the finishing of drag adds another  newLength ?
uioldoffset=  $('#bardiv_'+id).offset();//ui.position;
            $('#bardiv_'+id).offset(uioffset);       //necessary for correct dependencies
//alert('neubarleft:'+uioffset.left);

//g.Draw();
g.clearDependencies();
//g.CalcTaskXY();
vDirty=1;
    g.DrawDependencies();
            $('#bardiv_'+id).offset(uioldoffset);         //otherwise bar is shifted twice
var datestrend=JSGantt.formatDateStr( endd, g.getDateDisplayFormat());
//1alert(datestrend);
var datestrstart=JSGantt.formatDateStr( startd, g.getDateDisplayFormat());
//1alert(datestrstart);

$('#child_'+id).find('td').eq(2).text(datestrstart);

$('#child_'+id).find('td').eq(3).text(datestrend);
      //  alert($('#'+id).text(id));

//correct date format for post
//datestr=JSGantt.formatDateStr( vList[vTask].getEnd(), 'yyyy-mm-dd');
datestrend=JSGantt.formatDateStr( endd, 'yyyy-mm-dd');
//alert(datestrend);
datestrstart=JSGantt.formatDateStr( startd, 'yyyy-mm-dd');
        $.post('$w2p_base_url/index.php?m=planner&a=test&suppressHeaders=true', { task_id: id, do_task: 'drag',
        starttime: datestrstart , endtime: datestrend}, 
        function(data) {
 //       alert('Data Loaded: ' + data);
        if (data=='0') {
        alert('could not save to w2p.');
//         revertFunc();
 }
   });



//end stop function
}
});
//end drag

$('#lefttable').fixedHeaderTable('show');
";
/*
$html1="";  

";
     
*/

$html.=
" 
 </script>

<BR><BR>

<BR>
";
/*

 ";
*/
echo $html;


