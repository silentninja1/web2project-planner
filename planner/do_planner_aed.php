<?php /* $Id$ $URL$ */
if (!defined('W2P_BASE_DIR')) {
    die('You should not access this file directly.');
}
// deny all but system admins
$canEdit = canEdit('system');
if (!$canEdit) {
	$AppUI->redirect('m=public&a=access_denied');
}
$AppUI->savePlace();
$dokuwiki_baseURL = w2PgetCleanParam($_POST, 'dokuwiki_base_URL', '');
$dokuwiki_projectsURL = w2PgetCleanParam($_POST, 'dokuwiki_projects_namespace', '');
$dokuwiki_tasksURL = w2PgetCleanParam($_POST, 'dokuwiki_tasks_namespace', '');

$obj = new CDokuwiki();
$obj->load(1);
$obj->dokuwiki_URL=   $dokuwiki_baseURL;
$obj->store($AppUI);
$obj->load(2);
$obj->dokuwiki_URL=$dokuwiki_projectsURL;
$obj->store($AppUI);
$obj->load(3);
$obj->dokuwiki_URL=$dokuwiki_tasksURL;
$obj->store($AppUI);


$success = 'm=system&a=viewmods';
    $AppUI->redirect($success);