<?php
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}
//todo:  access rights for store
global $AppUI;
$user_id=$AppUI->user_id;
 
$perms = &$AppUI->acl();
$canRead = canView($m);

if (!$canRead) {
	$AppUI->redirect(ACCESS_DENIED);
}

/*
		if (!isset($user_id)) {
			$user_id = $AppUI->user_id;
		}
*/
$id =  (int) w2PgetParam($_POST, 'id', 0);
$start = w2PgetParam($_POST, 'starttime', 0);
$end = w2PgetParam($_POST, 'endtime', 0);
$event_private = w2PgetParam($_POST, 'private', 0);
$event_name=w2PgetParam($_POST, 'event_name', '');
$event_descript= w2PgetParam($_POST, 'event_description', 0);
$f=0;
//$startm=$AppUI->convertToSystemTZ($start);
//$w2p_date_object = new w2p_Utilities_Date($start);
//$new_mysql_formatted_date = $w2p_date_object->format(FMT_DATETIME_MYSQL);
//$startm=date('Y-m-d H:i:s', $start);
//$endm=$AppUI->convertToSystemTZ($end);
/*
 * $w2p_date_object = new w2p_Utilities_Date($original_mysql_formatted_date);
$new_mysql_formatted_date = $w2p_date_object->format(FMT_DATETIME_MYSQL);
*/
/*
$FF=fopen("C:\Bitnami\apache2\htdocs\w2pdpl/post.txt","w");
 fputs($FF, "$id  $start   $end    $startm ; $w2p_date_object  ; $endm");
 fclose($FF);
 */
  $ev1= new CEvent() ;
 if ($id>0) $ev1->load($id);
 if ($id==0) {
     $ev1->event_name=$event_name;
     $ev1->event_owner=$user_id;
     $ev1->event_private=$event_private; //otherwise not shown in myevent filter in dayview/events
     $ev1->event_description=    $event_descript;
 }
 $ev1->event_start_date=$start;
 $ev1->event_end_date=$end;

 $res=false;//
 $res=$ev1->store();
 //echo "0";
if ($res) {
echo "$ev1->event_id";
$ass = array();
$ass=$AppUI->user_id;
 if ($id==0) $ev1->updateAssigned($ass);
}
  else echo "0";

?>
