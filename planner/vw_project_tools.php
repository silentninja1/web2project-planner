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
Copyright (c) 2013/2014 Klaus Buecher (Opto)

No warranty whatsoever is given - use at your own risk. See index.php
 * 

*/
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

global $this_day, $prev_day, $next_day, $first_time, $last_time, $company_id, $event_filter, $event_filter_list, $AppUI;



$perms = &$AppUI->acl();
$user_id = $AppUI->user_id;



global $m, $a, $project_id, $f, $task_status, $min_view, $query_string, $durnTypes, $tpl;
global $task_sort_item1, $task_sort_type1, $task_sort_order1;
global $task_sort_item2, $task_sort_type2, $task_sort_order2;
global $user_id, $w2Pconfig, $currentTabId, $currentTabName, $canEdit, $showEditCheckbox;
global $history_active;



function showtask_pd_ed1(&$arr, $level = 0, $today_view = false) {
	global $AppUI, $w2Pconfig, $done, $userAlloc, $showEditCheckbox;
	global $task_access, $PROJDESIGN_CONFIG, $m, $expanded;
  $class = 'late';//w2pFindTaskComplete($arr['task_start_date'], $arr['task_end_date'], $arr['task_percent_complete']);
/*
               $myDate = intval($value) ? new w2p_Utilities_Date($value) : null;
                $cell = $myDate ? $myDate->format($this->df) : '-';
 
*/
$userTZ = $AppUI->getPref('TIMEZONE');

  $tid=(string)$arr['task_id'];
$df=  ' ' . $AppUI->getPref('SHDATEFORMAT');
$tp=(string)$arr['task_percent_complete'];
$tn=(string)$arr['task_name'];
$te=(string)$AppUI->formatTZAwareTime($arr['task_end_date'], '%Y-%m-%d %T');;
 $ts=(string)$arr['task_start_date'];
//	$start_date_userTZ = $start_date = new w2p_Utilities_Date($ts,$userTZ);
// 	$ts = $start_date->format(FMT_DATETIME_MYSQL);
	$tsTZ=$AppUI->formatTZAwareTime($ts, '%Y-%m-%d %T');
 /*
              $myDate = new w2p_Utilities_Date($ts);
                $cell = $myDate ? $myDate->format($df) : '-';
				$ts=$cell;
*/
 $padl=$level*17;
$pad="$padlpx";//$level*50;
    $htmlHelper = new w2p_Output_HTMLHelper($AppUI);
    $htmlHelper->df .= ' ' . $AppUI->getPref('TIMEFORMAT');
    $htmlHelper->stageRowData($arr);

//$tsd=$htmlHelper->createCell('task_start_datetime', $arr['task_start_date'])
echo "    <tr id='$tid'   class=".'"'.$class.'"'."><td nowrap='nowrap'>$tp </td><td style='padding-left:$padl"."px; text-align:left'>$tn</td><td >$tsTZ</td><td   >$te</td></tr> ";

//echo "    <tr style='text-align:left'><td nowrap='nowrap'>$tp </td><td style='padding-left:$padl"."px; text-align:left'>$tn</td><td>".$tsd."</td><td   >$te</td></tr> ";
}

//This kludgy function echos children tasks as threads on project designer (_pd)
//TODO: modules/projectdesigner/projectdesigner.class.php
function showtask_pd_ed(&$arr, $level = 0, $today_view = false) {
	global $AppUI, $w2Pconfig, $done, $userAlloc, $showEditCheckbox;
	global $task_access, $PROJDESIGN_CONFIG, $m, $expanded;

    $durnTypes = w2PgetSysVal('TaskDurationType');
    //Check for Tasks Access
    $tmpTask = new CTask();
    $tmpTask->load($arr['task_id']);
    $canAccess = $tmpTask->canAccess();
	if (!$canAccess) {
		return (false);
	}

    $htmlHelper = new w2p_Output_HTMLHelper($AppUI);
    $htmlHelper->df .= ' ' . $AppUI->getPref('TIMEFORMAT');
    $htmlHelper->stageRowData($arr);

	$types = w2Pgetsysval('TaskType');

	$show_all_assignees = $w2Pconfig['show_all_task_assignees'] ? true : false;

	$done[] = $arr['task_id'];

	// prepare coloured highlight of task time information
    $class = w2pFindTaskComplete($arr['task_start_date'], $arr['task_end_date'], $arr['task_percent_complete']);

	$jsTaskId = 'task_proj_' . $arr['task_project'] . '_level-' . $level . '-task_' . $arr['task_id'] . '_';
	if ($expanded) {
		$s = '<tr id="' . $jsTaskId . '" class="'.$class.'" onclick="select_row(\'selected_task\', \'' . $arr['task_id'] . '\', \'frm_tasks\')">'; // edit icon
	} else {
		$s = '<tr id="' . $jsTaskId . '" class="'.$class.'" onclick="select_row(\'selected_task\', \'' . $arr['task_id'] . '\', \'frm_tasks\')" ' . ($level ? 'style="display:none"' : '') . '>'; // edit icon
	}
	$s .= '<td class="data _edit">';
	$canEdit = ($arr['task_represents_project']) ? false : true;
	if ($canEdit) {
		$s .= '<a href="?m=tasks&a=addedit&task_id=' . $arr['task_id'] . '">' . w2PshowImage('icons/pencil.gif', 12, 12) . '</a>';
	}
	$s .= '</td>';

    $s .= $htmlHelper->createCell('task_percent_complete', $arr['task_percent_complete']);
    $s .= $htmlHelper->createCell('task_priority', $arr['task_priority']);
    $s .= $htmlHelper->createCell('user_task_priority', $arr['user_task_priority']);
    $s .= $htmlHelper->createCell('other', mb_substr($task_access[$arr['task_access']], 0, 3));
    $s .= $htmlHelper->createCell('other', mb_substr($types[$arr['task_type']], 0, 3));
    // reminders set
    $s .= $htmlHelper->createCell('other', ($arr['queue_id']) ? 'Yes' : '');
    $s .= $htmlHelper->createCell('other', ($arr['task_status'] == -1) ? 'Yes' : '');

	// add log
	$s .= '<td align="center" nowrap="nowrap">';
	if ($arr['task_dynamic'] != 1 && 0 == $arr['task_represents_project']) {
		$s .= '<a href="?m=tasks&a=view&tab=1&project_id=' . $arr['task_project'] . '&task_id=' . $arr['task_id'] . '">' . w2PtoolTip('tasks', 'add work log to this task') . w2PshowImage('edit_add.png') . w2PendTip() . '</a>';
	}
	$s .= '</td>';

	// dots
    $s .= '<td style="width: ' . (($today_view) ? '20%' : '50%') . '" class="data _name">';
	for ($y = 0; $y < $level; $y++) {
		if ($y + 1 == $level) {
			$image = w2PfindImage('corner-dots.gif', $m);
		} else {
			$image = w2PfindImage('shim.gif', $m);
		}
        $s .= '<img src="' . $image . '" width="16" height="12"  border="0" alt="" />';
	}
	// name link
	if ($arr['task_description']) {
		$s .= w2PtoolTip('Task Description', $arr['task_description'], true);
	}
    $jsTaskId = 'task_proj_' . $arr['task_project'] . '_level-' . $level . '-task_' . $arr['task_id'] . '_';
	$open_link = '<a href="javascript: void(0);" onclick="selected_task_' . $arr['task_id'] . '.checked=true"><img onclick="expand_collapse(\'' . $jsTaskId . '\', \'tblProjects\',\'\',' . ($level + 1) . ');" id="' . $jsTaskId . '_collapse" src="' . w2PfindImage('icons/collapse.gif', $m) . '" border="0" align="center" ' . (!$expanded ? 'style="display:none"' : '') . ' alt="" /><img onclick="expand_collapse(\'' . $jsTaskId . '\', \'tblProjects\',\'\',' . ($level + 1) . ');" id="' . $jsTaskId . '_expand" src="' . w2PfindImage('icons/expand.gif', $m) . '" border="0" align="center" ' . ($expanded ? 'style="display:none"' : '') . ' alt="" /></a>';
	$taskObj = new CTask;
	$taskObj->load($arr['task_id']);
	if (count($taskObj->getChildren())) {
		$is_parent = true;
	} else {
		$is_parent = false;
	}
	if ($arr['task_milestone'] > 0) {
		$s .= '&nbsp;<a href="./index.php?m=tasks&a=view&task_id=' . $arr['task_id'] . '" ><b>' . $arr['task_name'] . '</b></a> <img src="' . w2PfindImage('icons/milestone.gif', $m) . '" border="0" alt="" /></td>';
	} elseif ($arr['task_dynamic'] == '1' || $is_parent) {
		$s .= $open_link;
		if ($arr['task_dynamic'] == '1') {
			$s .= '&nbsp;<a href="./index.php?m=tasks&a=view&task_id=' . $arr['task_id'] . '" ><b><i>' . $arr['task_name'] . '</i></b></a></td>';
		} else {
			$s .= '&nbsp;<a href="./index.php?m=tasks&a=view&task_id=' . $arr['task_id'] . '" >' . $arr['task_name'] . '</a></td>';
		}
	} else {
		$s .= '&nbsp;<a href="./index.php?m=tasks&a=view&task_id=' . $arr['task_id'] . '" >' . $arr['task_name'] . '</a></td>';
	}
	if ($arr['task_description']) {
		$s .= w2PendTip();
	}
	// task description
	if ($PROJDESIGN_CONFIG['show_task_descriptions']) {
		$s .= '<td align="justified">' . $arr['task_description'] . '</td>';
	}
	// task owner
    $s .= $htmlHelper->createCell('task_owner', $arr['contact_name']);
    $s .= $htmlHelper->createCell('task_start_datetime', $arr['task_start_date']);
	// duration or milestone
    $s .= $htmlHelper->createCell('task_duration', $arr['task_duration'] . ' ' . mb_substr($AppUI->_($durnTypes[$arr['task_duration_type']]), 0, 1));
    $s .= $htmlHelper->createCell('task_end_datetime', $arr['task_end_date']);
	if (isset($arr['task_assigned_users']) && ($assigned_users = $arr['task_assigned_users'])) {
		$a_u_tmp_array = array();
		if ($show_all_assignees) {
			$s .= '<td align="left">';
			foreach ($assigned_users as $val) {
				$aInfo = '<a href="?m=users&a=view&user_id=' . $val['user_id'] . '"';
				$aInfo .= 'title="' . (w2PgetConfig('check_overallocation') ? $AppUI->_('Extent of Assignment') . ':' . $userAlloc[$val['user_id']]['charge'] . '%; ' . $AppUI->_('Free Capacity') . ':' . $userAlloc[$val['user_id']]['freeCapacity'] . '%' : '') . '">';
				$aInfo .= $val['contact_name'] . ' (' . $val['perc_assignment'] . '%)</a>';
				$a_u_tmp_array[] = $aInfo;
			}
			$s .= join(', ', $a_u_tmp_array);
			$s .= '</td>';
		} else {
			$s .= '<td align="left" nowrap="nowrap">';
			$s .= '<a href="?m=users&a=view&user_id=' . $assigned_users[0]['user_id'] . '"';
			$s .= 'title="' . (w2PgetConfig('check_overallocation') ? $AppUI->_('Extent of Assignment') . ':' . $userAlloc[$assigned_users[0]['user_id']]['charge'] . '%; ' . $AppUI->_('Free Capacity') . ':' . $userAlloc[$assigned_users[0]['user_id']]['freeCapacity'] . '%' : '') . '">';
			$s .= $assigned_users[0]['contact_name'] . ' (' . $assigned_users[0]['perc_assignment'] . '%)</a>';
			if ($arr['assignee_count'] > 1) {
				$id = $arr['task_id'];
				$s .= '<a href="javascript: void(0);"  onclick="toggle_users(\'users_' . $id . '\');" title="' . join(', ', $a_u_tmp_array) . '">(+' . ($arr['assignee_count'] - 1) . ')</a>';
				$s .= '<span style="display: none" id="users_' . $id . '">';
				$a_u_tmp_array[] = $assigned_users[0]['user_username'];
				for ($i = 1, $i_cmp = count($assigned_users); $i < $i_cmp; $i++) {
					$a_u_tmp_array[] = $assigned_users[$i]['user_username'];
					$s .= '<br /><a href="?m=users&a=view&user_id=';
					$s .= $assigned_users[$i]['user_id'] . '" title="' . (w2PgetConfig('check_overallocation') ? $AppUI->_('Extent of Assignment') . ':' . $userAlloc[$assigned_users[$i]['user_id']]['charge'] . '%; ' . $AppUI->_('Free Capacity') . ':' . $userAlloc[$assigned_users[$i]['user_id']]['freeCapacity'] . '%' : '') . '">';
					$s .= $assigned_users[$i]['contact_name'] . ' (' . $assigned_users[$i]['perc_assignment'] . '%)</a>';
				}
				$s .= '</span>';
			}
			$s .= '</td>';
		}
	} else {
		// No users asigned to task
		$s .= '<td class="data">-</td>';
	}

	// Assignment checkbox
	if ($showEditCheckbox && 0 == $arr['task_represents_project']) {
		$s .= '<td class="data"><input type="checkbox" onclick="select_box(\'multi_check\', ' . $arr['task_id'] . ',\'project_' . $arr['task_project'] . '_level-' . $level . '-task_' . $arr['task_id'] . '_\',\'frm_tasks\')" onfocus="is_check=true;" onblur="is_check=false;" id="selected_task_' . $arr['task_id'] . '" name="selected_task" value="' . $arr['task_id'] . '"/></td>';
	}
	$s .= '</tr>';

	return $s;
}

//TODO: modules/projectdesigner/projectdesigner.class.php



function findchild_pd_ed(&$tarr, $parent, $level = 0) {
	$level = $level + 1;
	$n = count($tarr);

	for ($x = 0; $x < $n; $x++) {
		if ($tarr[$x]['task_parent'] == $parent && $tarr[$x]['task_parent'] != $tarr[$x]['task_id']) {
			echo showtask_pd_ed1($tarr[$x], $level);
			findchild_pd_ed($tarr, $tarr[$x]['task_id'], $level);
		}
	}
}






function showtask_pr_ed(&$arr, $level = 0, $today_view = false) {
	global $AppUI, $done;

    //Check for Tasks Access
    $tmpTask = new CTask();
    $tmpTask->load($arr['task_id']);
    $canAccess = $tmpTask->canAccess();
	if (!$canAccess) {
		return (false);
	}

    $htmlHelper = new w2p_Output_HTMLHelper($AppUI);
    $htmlHelper->df .= ' ' . $AppUI->getPref('TIMEFORMAT');

	$done[] = $arr['task_id'];

	$s = '<tr>';

	// dots
    $s .= '<td style="width: ' . (($today_view) ? '20%' : '50%') . '" class="data _name">';
	for ($y = 0; $y < $level; $y++) {
		if ($y + 1 == $level) {
			$image = w2PfindImage('corner-dots.gif', $m);
		} else {
			$image = w2PfindImage('shim.gif', $m);
		}
        $s .= '<img src="' . $image . '" width="16" height="12"  border="0" alt="" />';
	}
	// name link
	$alt = mb_strlen($arr['task_description']) > 80 ? mb_substr($arr['task_description'], 0, 80) . '...' : $arr['task_description'];
	// instead of the statement below
	$alt = mb_str_replace('"', "&quot;", $alt);
	$alt = mb_str_replace("\r", ' ', $alt);
	$alt = mb_str_replace("\n", ' ', $alt);

	$open_link = w2PshowImage('collapse.gif');
	if ($arr['task_milestone'] > 0) {
		$s .= '&nbsp;<b>' . $arr["task_name"] . '</b><!--</a>--> <img src="' . w2PfindImage('icons/milestone.gif', $m) . '" border="0" alt="" />';
	} elseif ($arr['task_dynamic'] == '1') {
		$s .= $open_link;
		$s .= '<strong>' . $arr['task_name'] . '</strong>';
	} else {
		$s .= $arr['task_name'];
	}
    $s .= '</td>';

    $s .= $htmlHelper->createCell('task_percent_complete', $arr['task_percent_complete']);
    $s .= $htmlHelper->createCell('task_start_date',       $arr['task_start_date']);
    $s .= $htmlHelper->createCell('task_end_date',         $arr['task_end_date']);
    $s .= $htmlHelper->createCell('last_update',           $arr['last_update']);
    $s .= '</tr>';

	return $s;
}





//Lets load the users panel viewing options
$q = new w2p_Database_Query;
$q->addTable('project_designer_options', 'pdo');
$q->addQuery('pdo.*');
$q->addWhere('pdo.pd_option_user = ' . (int)$AppUI->user_id);
$view_options = $q->loadList();

$project_id = (int) w2PgetParam($_POST, 'project_id', 0);
$project_id = (int) w2PgetParam($_GET, 'project_id', (int)$project_id);
$extra = array('where' => 'project_active = 1');
$project = new CProject();
$projects = $project->getAllowedRecords($AppUI->user_id, 'projects.project_id,project_name', 'project_name', null, $extra, 'projects');
$q = new w2p_Database_Query;
$q->addTable('projects');
$q->addQuery('projects.project_id, company_name');
$q->addJoin('companies', 'co', 'co.company_id = project_company');
$idx_companies = $q->loadHashList();
$q->clear();
foreach ($projects as $prj_id => $prj_name) {
	$projects[$prj_id] = $idx_companies[$prj_id] . ': ' . $prj_name;
}
asort($projects);
$projects = arrayMerge(array('0' => $AppUI->_('(None)', UI_OUTPUT_RAW)), $projects);
$extra = array();
$task = new CTask();
$tasks = $task->getAllowedRecords($AppUI->user_id, 'task_id,task_name', 'task_name', null, $extra);
$tasks = arrayMerge(array('0' => $AppUI->_('(None)', UI_OUTPUT_RAW)), $tasks);

if (!$project_id) {
	// setup the title block

?>
	<script language="javascript" type="text/javascript">
	function submitIt() {
		var f = document.prjFrm;
		var msg ='';
		if (f.project_id.value == 0) {
			msg += '<?php echo $AppUI->_('You must select a project first', UI_OUTPUT_JS); ?>';
			f.project_id.focus();
		}
		
		if (msg.length < 1) {
			f.submit();
		} else {
			alert(msg);
		}
	}
	</script>
<?php
    echo $AppUI->getTheme()->styleRenderBoxTop();

?>
    <form name="prjFrm" action="?m=planner" method="post" accept-charset="utf-8">
        <table border="1" cellpadding="4" cellspacing="0" width="100%" class="std">
            <tr>
                <td class="projectdesigner">
                    <?php echo $AppUI->_('Project'); ?>: <?php echo arraySelect($projects, 'project_id', 'onchange="submitIt()" class="text"', 0); ?>
                </td>
            </tr>
        </table>
    </form>

<?php

  } 
  else 
  {
 	// check permissions for this record
	$canReadProject = $perms->checkModuleItem('projects', 'view', $project_id);
	$canEditProject = $perms->checkModuleItem('projects', 'edit', $project_id);
	$canViewTasks = canView('tasks');
	$canAddTasks = canAdd('tasks');
	$canEditTasks = canEdit('tasks');
	$canDeleteTasks = canDelete('tasks');

	if (!$canReadProject) {
		$AppUI->redirect(ACCESS_DENIED);
	}

	// check if this record has dependencies to prevent deletion
	$msg = '';
	$obj = new CProject();
	// Now check if the project is editable/viewable.
	$denied = $obj->getDeniedRecords($AppUI->user_id);
	if (in_array($project_id, $denied)) {
		$AppUI->redirect(ACCESS_DENIED);
	}

	$canDeleteProject = $obj->canDelete($msg, $project_id);

	// load the record data
	$obj->loadFull(null, $project_id);

	if (!$obj) {
		$AppUI->setMsg('Project');
		$AppUI->setMsg('invalidID', UI_MSG_ERROR, true);
		$AppUI->redirect();
	} else {
		$AppUI->savePlace();
	}








/*
 * TODO: This file looks a *lot* like the common task list rendering code in 
 *   tasks/tasks.php
 */

if (empty($query_string)) {
	$query_string = '?m=' . $m . '&amp;a=' . $a;
}
$mods = $AppUI->getActiveModules();
$history_active = !empty($mods['history']) && canView('history');

/****
// Let's figure out which tasks are selected
*/
$task_id = (int) w2PgetParam($_GET, 'task_id', 0);

$q = new w2p_Database_Query;
$pinned_only = (int) w2PgetParam($_GET, 'pinned', 0);
if (isset($_GET['pin'])) {
	$pin = (int) w2PgetParam($_GET, 'pin', 0);
	$msg = '';

	// load the record data
	if ($pin) {
		$q->addTable('user_task_pin');
		$q->addInsert('user_id', $AppUI->user_id);
		$q->addInsert('task_id', $task_id);
	} else {
		$q->setDelete('user_task_pin');
		$q->addWhere('user_id = ' . (int)$AppUI->user_id);
		$q->addWhere('task_id = ' . (int)$task_id);
	}

	if (!$q->exec()) {
		$AppUI->setMsg('ins/del err', UI_MSG_ERROR, true);
	} else {
		$q->clear();
	}

	$AppUI->redirect('', -1);
}

$AppUI->savePlace();

$durnTypes = w2PgetSysVal('TaskDurationType');
$taskPriority = w2PgetSysVal('TaskPriority');

$task_project = $project_id;

$task_sort_item1 = w2PgetParam($_GET, 'task_sort_item1', '');
$task_sort_type1 = w2PgetParam($_GET, 'task_sort_type1', '');
$task_sort_item2 = w2PgetParam($_GET, 'task_sort_item2', '');
$task_sort_type2 = w2PgetParam($_GET, 'task_sort_type2', '');
$task_sort_order1 = (int) w2PgetParam($_GET, 'task_sort_order1', 0);
$task_sort_order2 = (int) w2PgetParam($_GET, 'task_sort_order2', 0);
if (isset($_POST['show_task_options'])) {
	$AppUI->setState('TaskListShowIncomplete', w2PgetParam($_POST, 'show_incomplete', 0));
}
$showIncomplete = $AppUI->getState('TaskListShowIncomplete', 0);

$project = new CProject;
// $allowedProjects = $project->getAllowedRecords($AppUI->user_id, 'project_id, project_name');
$allowedProjects = $project->getAllowedSQL($AppUI->user_id, 'projects.project_id');
$working_hours = ($w2Pconfig['daily_working_hours'] ? $w2Pconfig['daily_working_hours'] : 8);

$q->addQuery('projects.project_id, project_color_identifier, project_name');
$q->addQuery('SUM(task_duration * task_percent_complete * IF(task_duration_type = 24, ' . $working_hours . ', task_duration_type)) / SUM(task_duration * IF(task_duration_type = 24, ' . $working_hours . ', task_duration_type)) AS project_percent_complete');
$q->addQuery('company_name');
$q->addTable('projects');
$q->leftJoin('tasks', 't1', 'projects.project_id = t1.task_project');
$q->leftJoin('companies', 'c', 'company_id = project_company');
$q->leftJoin('project_departments', 'project_departments', 'projects.project_id = project_departments.project_id OR project_departments.project_id IS NULL');
$q->leftJoin('departments', 'departments', 'departments.dept_id = project_departments.department_id OR dept_id IS NULL');
$q->addWhere('t1.task_id = t1.task_parent');
$q->addWhere('projects.project_id=' . $project_id);
if (count($allowedProjects)) {
	$q->addWhere($allowedProjects);
}
$q->addGroup('projects.project_id');
$q2 = new w2p_Database_Query;
$q2 = $q;
$q2->addQuery('projects.project_id, COUNT(t1.task_id) as total_tasks');

$perms = &$AppUI->acl();
$projects = array();
if ($canViewTasks) {
	$prc = $q->exec();
	echo db_error();
	while ($row = $q->fetchRow()) {
		$projects[$row['project_id']] = $row;
	}

	$prc2 = $q2->exec();
	echo db_error();
	while ($row2 = $q2->fetchRow()) {
		$projects[$row2['project_id']] = ((!($projects[$row2['project_id']])) ? array() : $projects[$row2['project_id']]);
		array_push($projects[$row2['project_id']], $row2);
	}
}
$q->clear();
$q2->clear();

$q->addQuery('tasks.task_id, task_parent, task_name');
$q->addQuery('task_start_date, task_end_date, task_dynamic');
$q->addQuery('count(tasks.task_parent) as children');
$q->addQuery('task_pinned, pin.user_id as pin_user');
$q->addQuery('ut.user_task_priority');
$q->addQuery('task_priority, task_percent_complete');
$q->addQuery('task_duration, task_duration_type');
$q->addQuery('task_project, task_represents_project');
$q->addQuery('task_access, task_type');
$q->addQuery('task_description, task_owner, task_status');
$q->addQuery('usernames.user_username, usernames.user_id');
$q->addQuery('assignees.user_username as assignee_username');
$q->addQuery('count(distinct assignees.user_id) as assignee_count');
$q->addQuery('co.contact_first_name, co.contact_last_name, co.contact_display_name as contact_name');
$q->addQuery('task_milestone');
$q->addQuery('count(distinct f.file_task) as file_count');
$q->addQuery('tlog.task_log_problem');
$q->addQuery('evtq.queue_id');

$q->addTable('tasks');
if ($history_active) {
	$q->addQuery('MAX(history_date) as last_update');
	$q->leftJoin('history', 'h', 'history_item = tasks.task_id AND history_table=\'tasks\'');
}
$q->leftJoin('projects', 'projects', 'projects.project_id = task_project');
$q->leftJoin('users', 'usernames', 'task_owner = usernames.user_id');
$q->leftJoin('user_tasks', 'ut', 'ut.task_id = tasks.task_id');
$q->leftJoin('users', 'assignees', 'assignees.user_id = ut.user_id');
$q->leftJoin('contacts', 'co', 'co.contact_id = usernames.user_contact');
$q->leftJoin('task_log', 'tlog', 'tlog.task_log_task = tasks.task_id AND tlog.task_log_problem > 0');
$q->leftJoin('files', 'f', 'tasks.task_id = f.file_task');
$q->leftJoin('user_task_pin', 'pin', 'tasks.task_id = pin.task_id AND pin.user_id = ' . (int)$AppUI->user_id);
$q->leftJoin('event_queue', 'evtq', 'tasks.task_id = evtq.queue_origin_id AND evtq.queue_module = "tasks"');
$q->leftJoin('project_departments', 'project_departments', 'projects.project_id = project_departments.project_id OR project_departments.project_id IS NULL');
$q->leftJoin('departments', 'departments', 'departments.dept_id = project_departments.department_id OR dept_id IS NULL');

$q->addWhere('task_project = ' . (int)$project_id);

$allowedProjects = $project->getAllowedSQL($AppUI->user_id, 'task_project');
if (count($allowedProjects)) {
	$q->addWhere($allowedProjects);
}
$obj = new CTask;
$allowedTasks = $obj->getAllowedSQL($AppUI->user_id, 'tasks.task_id');
if (count($allowedTasks)) {
	$q->addWhere($allowedTasks);
}
$q->addGroup('tasks.task_id');

$q->addOrder('task_start_date, task_end_date, task_name');
if ($canViewTasks) {
	$tasks = $q->loadList();
}
// POST PROCESSING TASKS
foreach ($tasks as $row) {
	//add information about assigned users into the page output
	$q->clear();
	$q->addQuery('ut.user_id,	u.user_username');
	$q->addQuery('ut.perc_assignment, SUM(ut.perc_assignment) AS assign_extent');
	$q->addQuery('contact_first_name, contact_last_name, contact_display_name as contact_name');
	$q->addTable('user_tasks', 'ut');
	$q->leftJoin('users', 'u', 'u.user_id = ut.user_id');
	$q->leftJoin('contacts', 'c', 'u.user_contact = c.contact_id');
	$q->addWhere('ut.task_id = ' . (int)$row['task_id']);
	$q->addGroup('ut.user_id');
	$q->addOrder('perc_assignment desc, user_username');

	$assigned_users = array();
	$row['task_assigned_users'] = $q->loadList();
	$q->addQuery('count(task_id) as children');
	$q->addTable('tasks');
	$q->addWhere('task_parent = ' . (int)$row['task_id']);
	$q->addWhere('task_id <> task_parent');
	$row['children'] = $q->loadResult();
	$i = count($projects[$row['task_project']]['tasks']) + 1;
	$row['task_number'] = $i;
	$row['node_id'] = 'node_' . $i . '-' . $row['task_id'];
	if (strpos($row['task_duration'], '.') && $row['task_duration_type'] == 1) {
		$row['task_duration'] = floor($row['task_duration']) . ':' . round(60 * ($row['task_duration'] - floor($row['task_duration'])));
	}
	//pull the final task row into array
	$projects[$row['task_project']]['tasks'][] = $row;
}

$showEditCheckbox = isset($canEditTasks) && $canEditTasks || canView('admin');

$durnTypes = w2PgetSysVal('TaskDurationType');
$tempoTask = new CTask();
$userAlloc = $tempoTask->getAllocation('user_id');
global $expanded;
$expanded = $AppUI->getPref('TASKSEXPANDED');
$expanded=1;
$open_link = w2PtoolTip($m, 'click to expand/collapse all the tasks for this project.') . '<a href="javascript: void(0);"><img onclick="expand_collapse(\'task_proj_' . $project_id . '_\', \'tblProjects\',\'collapse\',0,2);" id="task_proj_' . $project_id . '__collapse" src="' . w2PfindImage('up22.png', $m) . '" border="0" width="22" height="22" align="center" ' . (!$expanded ? 'style="display:none"' : '') . ' /><img onclick="expand_collapse(\'task_proj_' . $project_id . '_\', \'tblProjects\',\'expand\',0,2);" id="task_proj_' . $project_id . '__expand" src="' . w2PfindImage('down22.png', $m) . '" border="0" width="22" height="22" align="center" ' . ($expanded ? 'style="display:none"' : '') . ' alt="" /></a>' . w2PendTip();

$fieldList = array();
$fieldNames = array();

$module = new w2p_System_Module();
$fields = $module->loadSettings('tasks', 'projectdesigner-view');

if (count($fields) > 0) {
    $fieldList = array_keys($fields);
    $fieldNames = array_values($fields);
} else {
    // TODO: This is only in place to provide an pre-upgrade-safe
    //   state for versions earlier than v3.0
    //   At some point at/after v4.0, this should be deprecated
    $fieldList = array('task_percent_complete', 'task_priority', 'user_task_priority',
        'task_access', 'task_type', 'task_1', 'task_2', 'task_3', 'task_name',
        'task_owner', 'task_start_date', 'task_duration', 'task_end_date', 'task_4');
    $fieldNames = array('Work', 'P', 'U', 'A', 'T', 'R', 'I', 'Log',
        'Task Name', 'Task Owner', 'Start', 'Duration', 'Finish',
        'Assgined Users');

    $module->storeSettings('tasks', 'projectdesigner-view', $fieldList, $fieldNames);
}
$open_link=true;
/*
 <table id="tblTasksarrow" class="tbl list">
    <tr>
        <td colspan="16" align='left'>
            <?php echo $open_link; ?>
        </td>
    </tr> </table>

*/


?>
<link rel="stylesheet" type="text/css" href="http://localhost/w2pm/modules/css/jquery.datatables.css" media="all" charset="utf-8"/>
<script language='javascript' type='text/javascript'>
function require(script) {
    $.ajax({
        url: script,
        dataType: "script",
        async: false,           // <-- This is the key
        success: function () {
            // all good...
          //  alert(script);
        },
        error: function () {
            throw new Error("Could not load script " + script);
        }
    });
}          
require("http://localhost/w2pm/modules/planner/js2/jquery.js");    
require("http://localhost/w2pm/modules/planner/js2/jquery.datatables.js");    
require("http://localhost/w2pm/modules/planner/js2/jquery.jeditable.js");    
require("http://localhost/w2pm/modules/planner/js2/jquery.datatables.editable.js");    
require("http://localhost/w2pm/modules/planner/js2/jquery.blockui.js");    
require("http://localhost/w2pm/modules/planner/js2/jquery.validate.js");    
require("http://localhost/w2pm/modules/planner/js2/additional-methods.js");    
require("http://localhost/w2pm/modules/planner/js2/jquery-ui.js");    


//require("http://localhost/w2pm/modules/planner/js/jquery.datatables.css");    
        
$(document).ready(function(){
    $('#tblTask').dataTable(
  {
"bStateSave":true,
"sDom": '<top1 f><top2 l>rt<"bottom"ip><"clear">'   ,
"aLengthMenu": [[25, 50, 100,-1], [25, 50,100, "All"]],
"aoColumns":  [
    { sName:"task_percent_complete"} ,
    { sName:"task_name"} ,
    { sName:"task_start_date"} ,
    { sName:"task_end_date"} 
]  ,
bSort:false,
"aaSorting":[ ]
  }
    ).makeEditable({
   sUpdateURL: "http://localhost/w2pm/index.php?m=planner&a=do_inlineaddedit_aed&suppressHeaders=true"
  
    });
});
                  
  //class="tbl list"    
</script>
<style type="text/css">
@import "http://localhost/w2pm/modules/planner/css/jquery.datatables.css";
</style>

<form name="frm_tasks" accept-charset="utf-8"">
<div id=top1>  </div>  <div id=top2 style="align:right">  </div>

<table id="tblTask"  class="display tbl list"  >

    <thead>    
<?php
$WorkTitle=$AppUI->_('Work');
$NameTitle=$AppUI->_('Task  Name');
$StartTitle=$AppUI->_('Start');
$EndTitle=$AppUI->_('Finish');
echo "    <th nowrap='nowrap'>$WorkTitle </th><th>$NameTitle</th><th>$StartTitle</th><th>$EndTitle</th> ";
            ?>
  
<?php
             
 /*
 <tr><td>aaa</td><td>bbb</td><td>aaa</td><td>bbb</td></tr>
 */            ?>
     </thead>
    <tbody>
<?php
reset($projects);

foreach ($projects as $k => $p) {
	$tnums = count($p['tasks']);
	if ($tnums > 0 || $project_id == $p['project_id']) {
		if ($task_sort_item1 != '') {
			if ($task_sort_item2 != '' && $task_sort_item1 != $task_sort_item2) {
				$p['tasks'] = array_csort($p['tasks'], $task_sort_item1, $task_sort_order1, $task_sort_type1, $task_sort_item2, $task_sort_order2, $task_sort_type2);
			} else {
				$p['tasks'] = array_csort($p['tasks'], $task_sort_item1, $task_sort_order1, $task_sort_type1);
			}
		}

		for ($i = 0; $i < $tnums; $i++) {
			$t = $p['tasks'][$i];
			if ($t['task_parent'] == $t['task_id']) {
				    $tmpTask = new CTask();
    $tmpTask->load($t['task_id']);
    $canAccess = $tmpTask->canAccess();
	if (!$canAccess) {
		return (false);
	}
/*
$tp=(string)$t['task_percent_complete'];
$tn=(string)$t['task_name'];
$te=(string)$t['task_end_date'];
$ts=(string)$t['task_start_date'];
echo "    <tr><td nowrap='nowrap'>$tp </td><td>$tn</td><td>$ts</td><td>$te</td></tr> ";
*/				
				echo showtask_pd_ed1($t, 0);
				findchild_pd_ed($p['tasks'], $t['task_id']);
			}
		}
	}
}
?>
   </tbody>
    </table>



</form>
<table>
<tr>
        <td><?php echo $AppUI->_('Key'); ?>:</td>
        <th>&nbsp;P&nbsp;</th>
        <td>=<?php echo $AppUI->_('Overall Priority'); ?></td>
        <th>&nbsp;U&nbsp;</th>
        <td>=<?php echo $AppUI->_('User Priority'); ?></td>
        <th>&nbsp;A&nbsp;</th>
        <td>=<?php echo $AppUI->_('Access'); ?></td>
        <th>&nbsp;T&nbsp;</th>
        <td>=<?php echo $AppUI->_('Type'); ?></td>
        <th>&nbsp;R&nbsp;</th>
        <td>=<?php echo $AppUI->_('Reminder'); ?></td>
        <th>&nbsp;I&nbsp;</th>
        <td>=<?php echo $AppUI->_('Inactive'); ?></td>
        <td>&nbsp; &nbsp;</td>
        <td>&nbsp; &nbsp;</td>
        <td class="future">&nbsp; &nbsp;</td>
        <td>=<?php echo $AppUI->_('Future Task'); ?></td>
        <td>&nbsp; &nbsp;</td>
        <td class="active">&nbsp; &nbsp;</td>
        <td>=<?php echo $AppUI->_('Started and on time'); ?></td>
        <td>&nbsp; &nbsp;</td>
        <td class="notstarted">&nbsp; &nbsp;</td>
        <td>=<?php echo $AppUI->_('Should have started'); ?></td>
        <td>&nbsp; &nbsp;</td>
        <td class="late">&nbsp; &nbsp;</td>
        <td>=<?php echo $AppUI->_('Overdue'); ?></td>
        <td>&nbsp; &nbsp;</td>
        <td class="done">&nbsp; &nbsp;</td>
        <td>=<?php echo $AppUI->_('Done'); ?></td>
</tr>
</table><?php


}
?>