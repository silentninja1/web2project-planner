<?php
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}
// @todo    refactor to use a core controller

global $AppUI;

$perms = &$AppUI->acl();
if (!canEdit('tasks')) {
	$AppUI->redirect(ACCESS_DENIED);
}



  $id =(int) $_REQUEST['id'] ;
  $value = $_REQUEST['value'] ;
  $column = $_REQUEST['columnName'] ;
  $columnPosition = $_REQUEST['columnPosition'] ;
  $columnId = $_REQUEST['columnId'] ;
  $rowId = $_REQUEST['rowId'] ;
  
  $task_obj= new CTask();
  $task_obj->load($id);
  if ($column=="task_name") $task_obj->task_name=$value;
  
  if ($column=="task_percent_complete") $task_obj->task_percent_complete=(int)$value;
  if ($column=="task_start_date") {
  	$userTZ = $AppUI->getPref('TIMEZONE');
	$start_date_userTZ = $start_date = new w2p_Utilities_Date($value,$userTZ);
         $start_date->convertTZ('UTC');
	$ts = $start_date->format(FMT_DATETIME_MYSQL);


  	$task_obj->task_start_date=$ts;
	}

  if ($column=="task_end_date") {
  	$userTZ = $AppUI->getPref('TIMEZONE');
	$start_date_userTZ = $start_date = new w2p_Utilities_Date($value,$userTZ);
         $start_date->convertTZ('UTC');
	$ts = $start_date->format(FMT_DATETIME_MYSQL);


  	$task_obj->task_end_date=$ts;
	}


  
//  if (column=="task_start_date") $task_obj->task_name=$value;
 // if (column=="task_end_date") $task_obj->task_name=$value;

  /* Update a record using information about id, columnName (property
     of the object or column in the table) and value that should be
     set */ 
	 

  if ($task_obj->store()) echo $value; else echo "cannot store/edit task name";

?>