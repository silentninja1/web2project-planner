<?php /* $Id:  $ $URL:  $ */
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}
require_once W2P_BASE_DIR . '/modules/planner/planner.class.php';

// deny all but system admins
$canEdit = canEdit('system');
if (!$canEdit) {
	$AppUI->redirect('m=public&a=access_denied');
}
$AppUI->savePlace();
$obj= new CPlanner();
/*
$dokuwiki_baseURL=$obj->getBaseURL();
$dokuwiki_projectsURL=$obj->getProjectsNamespaceURL()  ;
$dokuwiki_tasksURL=$obj->getTasksNamespaceURL()  ;
*/
// setup the title block
$titleBlock = new w2p_Theme_TitleBlock('Configure Planner Module', 'support.png', $m, $m . '.' . $a);
$titleBlock->addCrumb('?m=system', 'system admin');
$titleBlock->addCrumb('?m=system&a=viewmods', 'modules list');
$titleBlock->show();



/*


  <input type="text" name="dokuwiki_base_URL" id="dokuwiki_base_URL" value= <?php echo $dokuwiki_baseURL   ?>  size="50" maxlength="255" onclick="javascript:submitFrm('frmDokuwikiConfigure');" />
<label for="dokuwiki_base_URL"><?php echo $AppUI->_('dokuwiki_base_URL, e.g. http://localhost/dokuwiki/'); ?></label>
<br>
<!--
<input type="text" name="dokuwiki_base_URL" id="dokuwiki_base_URL" value="dod" <?php  ?> onclick="javascript:submitFrm('frmDokuwikiConfigure');" />
<label for="dokuwiki_base_URL"><?php echo $AppUI->_('dokuwiki_base_URL'); ?></label>
-->
<input type="text" name="dokuwiki_projects_namespace" id="dokuwiki_projects_namespace" value= <?php echo $dokuwiki_projectsURL ?>  size="50" maxlength="255"  onclick="javascript:submitFrm('frmDokuwikiConfigure');" />
<label for="dokuwiki_base_URL"><?php echo $AppUI->_('dokuwiki_projects_namespace, e.g. web2project:projects'); ?></label>
<br>
<!--
<input type="text" name="dokuwiki_tasks_namespace" id="dokuwiki_tasks_namespace" value= <?php echo $dokuwiki_tasksURL ?>  size="50" maxlength="255"  onclick="javascript:submitFrm('frmDokuwikiConfigure');" />
<label for="dokuwiki_base_URL"><?php echo $AppUI->_('dokuwiki_tasks_namespace'); ?></label>
-->

<br><input class="button" type="submit"  name="submit"  value="<?php echo $AppUI->_('save'); ?>"/>
<input class="button" type="button" name="cancel" value="<?php echo $AppUI->_('cancel'); ?>" onclick="javascript:if(confirm('Are you sure you want to cancel.')){location.href = './index.php?m=system&a=viewmods';}" />


*/


?>

<!--
<script language="javascript" type="text/javascript">
function submitFrm( frmName ) {

	eval('document.'+frmName+'.submit();');

}
</script>
-->

<form name="frmPlannerConfigure" method="post" action="?m=dokuwiki&a=do_dokuwiki_aed" accept-charset="utf-8">
<input type="hidden" name="forcesubmit" value="true" />
 
</form>