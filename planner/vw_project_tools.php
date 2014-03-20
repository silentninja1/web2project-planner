<?php
/* $Id$ $URL$ */
/*
Dayplanner v1.0.3
Klaus Buecher
   

LICENSE

=====================================

The planner module was built by Klaus Buecher and is released here
under modified BSD license (see GNU.org).

Uses jquery, jqueryui and datatables. Please see their separate licences.
 * 
Copyright (c) 2014 Klaus Buecher (Opto)

No warranty whatsoever is given - use at your own risk. See index.php
 * 

*/
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

global $this_day, $prev_day, $next_day, $first_time, $last_time, $company_id, $event_filter, $event_filter_list, $AppUI;


//Lets load the users panel viewing options
$q = new w2p_Database_Query;
$q->addTable('project_designer_options', 'pdo');
$q->addQuery('pdo.*');
$q->addWhere('pdo.pd_option_user = ' . (int)$AppUI->user_id);
$view_options = $q->loadList();

$project_id = (int) w2PgetParam($_POST, 'project_id', 0);
$project_id = (int) w2PgetParam($_GET, 'project_id', $project_id);

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
} else {
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
	// setup the title block
	$ttl = 'ProjectDesigner';
	$titleBlock = new w2p_Theme_TitleBlock($ttl, 'icon.png', $m, $m . '.' . $a);
	$titleBlock->addCrumb('?m=projects', 'projects list');
	$titleBlock->addCrumb('?m=' . $m, 'select another project');
	$titleBlock->addCrumb('?m=projects&a=view&bypass=1&project_id=' . $project_id, 'normal view project');

	if ($canAddProjects) {
		$titleBlock->addCell();
        $titleBlock->addButton('New project', '?m=projects&a=addedit');
    }

	if ($canAddTasks) {
		$titleBlock->addCell();
        $titleBlock->addButton('New task', '?m=tasks&a=addedit&task_project=' . $project_id);
	}
	if ($canEditProject) {
		$titleBlock->addCell();
        $titleBlock->addButton('New event', '?m=events&a=addedit&event_project=' . $project_id);

		$titleBlock->addCell();
        $titleBlock->addButton('New file', '?m=files&a=addedit&project_id=' . $project_id);
		$titleBlock->addCrumb('?m=projects&a=addedit&project_id=' . $project_id, 'edit this project');
		if ($canDeleteProject) {
			$titleBlock->addCrumbDelete('delete project', $canDelete, $msg);
		}
	}
	$titleBlock->addCell();
	$titleBlock->addCell(w2PtoolTip($m, 'print project') . '<a href="javascript: void(0);" onclick ="window.open(\'index.php?m=projectdesigner&a=printproject&dialog=1&suppressHeaders=1&project_id=' . $project_id . '\', \'printproject\',\'width=1200, height=600, menubar=1, scrollbars=1\')">
      		<img src="' . w2PfindImage('printer.png') . '" border="0" width="22" heigth"22" alt="" />
      		</a>
      		' . w2PendTip());
	$titleBlock->addCell(w2PtoolTip($m, 'expand all panels') . '<a href="javascript: void(0);" onclick ="expandAll()">
      		<img src="' . w2PfindImage('down.png', $m) . '" border="0" width="22" heigth="22" alt="" />
      		</a>
      		' . w2PendTip());
	$titleBlock->addCell(w2PtoolTip($m, 'collapse all panels') . '<a href="javascript: void(0);" onclick ="collapseAll()">
      		<img src="' . w2PfindImage('up.png', $m) . '" border="0" width="22" heigth="22" alt="" />
      		</a>
      		' . w2PendTip());
	$titleBlock->addCell(w2PtoolTip($m, 'save your workspace') . '<a href="javascript: void(0);" onclick ="document.frmWorkspace.submit()">
      		<img src="' . w2PfindImage('filesave.png', $m) . '" border="0" width="22" heigth="22" alt="" />
      		</a>
      		' . w2PendTip());
	$titleBlock->addCell();

	$titleBlock->show();

*/

?>
