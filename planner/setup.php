<?php /* $Id$ $URL$ */
if (!defined('W2P_BASE_DIR')){
  die('You should not access this file directly.');
}





/**
 * Name:			Planner
 * Directory: planner
 * Type:			user
 * UI Name:		planner
 * UI Icon: 	?
 */

$config = array();
$config['mod_name']        = 'Planner';			    // name the module
$config['mod_version']     = '1.0.6';			      	// add a version number
$config['mod_directory']   = 'planner';             // tell web2project where to find this module
$config['mod_setup_class'] = 'CSetupPlanner';		// the name of the PHP setup class (used below)
$config['mod_type']        = 'user';				      // 'core' for modules distributed with w2p by standard, 'user' for additional modules
$config['mod_ui_name']	   = $config['mod_name']; // the name that is shown in the main menu of the User Interface
$config['mod_ui_icon']     = '';                  // name of a related icon
$config['mod_description'] = 'Day and Project Planning Utilities';			    // some description of the module
$config['mod_config']      = false;					      // show 'configure' link in viewmods
$config['mod_main_class']  = 'CPlanner';

$config['permissions_item_table'] = 'planner';
$config['permissions_item_field'] = 'planner_id';
$config['permissions_item_label'] = 'planner_title';

class CSetupPlanner
{
	public function install()
	{ 
		global $AppUI;

        $q = new w2p_Database_Query();
		$q->createTable('planner');
		$sql = '(
			planner_id int(10) unsigned NOT NULL AUTO_INCREMENT,
			
			PRIMARY KEY  (planner_id))
			ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci';
		$q->createDefinition($sql);
		$q->exec();
/*                $q->clear();
                $q->addTable('planner','dw');
                $q->addInsert('dokuwiki_URL_use','dokuwiki_base_URL');
                $q->addInsert('dokuwiki_URL','http://localhost/dokuwiki/');
		$q->exec();

                $q->clear();
                $q->addTable('dokuwiki','dw');
                $q->addInsert('dokuwiki_URL','http://localhost/dwiki/doku.php?id=projects');
                $q->addInsert('dokuwiki_URL_use','dokuwiki_projects_namespace');
		$q->exec();
                $q->clear();
                $q->addTable('dokuwiki','dw');
                $q->addInsert('dokuwiki_URL','');
                $q->addInsert('dokuwiki_URL_use','dokuwiki_tasks_sub_namespace');
		$q->exec();
                $q->clear();
                $q->addTable('dokuwiki','dw');
                $q->addInsert('dokuwiki_URL','http://localhost/dwiki/doku.php?id=contacts');
                $q->addInsert('dokuwiki_URL_use','dokuwiki_contacs_namespace');
		$q->exec();
                
                $f['dokuwiki_URL']='';
//                $f['dw.dokuwiki_id']=1;
                $f['dokuwiki_URL_use']='dokuwiki_base_URL';
                $q->clear();
                $q->addTable('dokuwiki','dw');
//                $f['dw.dokuwiki_id']=2;
                $f['dw.dokuwiki_URL_use']='dokuwiki_projects_namespace';
                $q->addInsert($f);
		$q->exec();
                $q->clear();
                $q->addTable('dokuwiki','dw');
//                $f['dw.dokuwiki_id']=3;
                $f['dw.dokuwiki_URL_use']='dokuwiki_tasks_namespace';
                $q->addInsert($f);
		$q->exec();
                    

*/
        $perms = $AppUI->acl();
        return $perms->registerModule('Planner', 'planner');
	}

	public function upgrade($old_version)
	{
        switch ($old_version) {
            case '1.0.0':
            case '1.0.1':
            case '1.0.2':
            case '1.0.3':
            case '1.0.4':
            case '1.0.5':
            case '1.0.6':                //replace old Date_calc by Date_Calc
            default:
				//do nothing
		}
		return true;
	}

	public function remove()
	{ 
		global $AppUI;

        $q = new w2p_Database_Query;
		$q->dropTable('planner');
		$q->exec();

/**/	
        $perms = $AppUI->acl();
        return $perms->unregisterModule('planner');
	}


    public function configure() {
        global $AppUI;
        $AppUI->redirect('m=planner&a=configure');
        return true;
    }


}
