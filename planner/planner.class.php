<?php /* $Id$ $URL$ */
if (!defined('W2P_BASE_DIR'))
{
  die('You should not access this file directly.');
}
##
## Planner Class
##

//the following code cannot be inside a class due to w2p autoloader


class CPlanner extends w2p_Core_BaseObject
{
	public $planner_id = 0;

        public $_tbl = 'planner';
	public $_tbl_key = 'planner_id';
	//TODO: support table prefixes

	public function __construct()
	{
		parent::__construct('planner', 'planner_id');
	}


	public function check()
	{
        $errorArray = array();
        $baseErrorMsg = get_class($this) . '::store-check failed - ';

        return $errorArray;
	}

 /*
    	public function getBaseURL()
        {
            $q = new w2p_Database_Query();
            $q->addQuery('dw.dokuwiki_URL');
            $q->addTable('dokuwiki', 'dw');
            $q->addWhere("dw.dokuwiki_URL_use='dokuwiki_base_URL'");
            $str55=$q->loadColumn();

            return $str55[0];
        }
        
    	public function getProjectsNamespaceURL()
        {
            $q = new w2p_Database_Query();
            $q->addQuery('dw.dokuwiki_URL');
            $q->addTable('dokuwiki', 'dw');
            $q->addWhere("dw.dokuwiki_URL_use='dokuwiki_projects_namespace'");
            $str55=$q->loadColumn();

            return $str55[0];
        }

        
    	public function getContactsNamespaceURL()
        {
            $q = new w2p_Database_Query();
            $q->addQuery('dw.dokuwiki_URL');
            $q->addTable('dokuwiki', 'dw');
            $q->addWhere("dw.dokuwiki_URL_use='dokuwiki_tasks_namespace'");
            $str55=$q->loadColumn();

            return $str55[0];
        }
        
 
    	public function getTasksNamespaceURL()
        {
            $q = new w2p_Database_Query();
            $q->addQuery('dw.dokuwiki_URL');
            $q->addTable('dokuwiki', 'dw');
            $q->addWhere("dw.dokuwiki_URL_use='dokuwiki_tasks_namespace'");
            $str55=$q->loadColumn();

            return $str55[0];
        }
         
        
        
        
    	public function combineStringBeginEnd( $str, $iChars=6 )
        {
            //take iChars characters from begin and from end of string
  //          $URL=substr($str,0,$iChars);
            if (strlen($str)>2*$iChars) 
            {
                 $URL=substr($str,0,$iChars);
                 $URL=$URL."..";
                $URL=$URL.substr($str,-$iChars,$iChars);
            }
            else
            $URL=substr($str,0,2*$iChars);
 

            return $URL;
        }
        
     	public function getProjectPageName( $str, $iChars=6 )
        {
            $URL=$this->getProjectsNamespaceURL();
            $URL=$URL.":";
            ////take iChars characters from begin and from end of string
            $URL=$URL.$this->combineStringBeginEnd($str,$iChars);
            return $URL;
        }

        
     	public function combineProjectURL( $str, $iChars=6 )
        {
            //to concatenate the "." didn't work on wampserver
            $URL=$this->getBaseURL();
            $URL=$URL."doku.php?id=";
             ////take iChars characters from begin and from end of string
            $URL=$URL.$this->getProjectPageName($str,$iChars);
            return $URL;
        }
       
     	public function combineContactURL( $str, $iChars=6 )
        {
            $URL=$this->getContactsNamespaceURL();
            $URL=$URL.":";
            ////take iChars characters from begin and from end of string
            $URL=$URL.$this->combineStringBeginEnd($str,$iChars);
            return $URL;
        }
 
        
        
    	public function combineTaskURL( $strProjName, $strTaskName,$iChars=6 )
        {
            $URL=$this->combineProjectURL($strProjName,$iChars);
            $URL=$URL.":";
            ////take iChars characters from begin and from end of string
   //         $URL=$URL.$this->combineStringBeginEnd($strProjName,$iChars);
   //         $URL=$URL.":";
            $URL=$URL.$this->combineStringBeginEnd($strTaskName,$iChars);
            return $URL;
        }
        
     	public function DokuwikiPageExists( $name)
        {
        include_once W2P_BASE_DIR . '/modules/dokuwiki/xmlrpc/lib/xmlrpc.inc';
        //include_once W2P_BASE_DIR  ."modules/dokuwiki/dokuwikiconnect.php";
        $BaseURL=$this->getBaseURL();
        $clientURL=$BaseURL.'/lib/exe/xmlrpc.php';
        $c =  new xmlrpc_client($clientURL,'','','');
        // enable debugging to see more infos :-) (well, not for production code)
        $c->setDebug(0);
        // create the XML message to send
        //$m = new xmlrpcmsg('dokuwiki.getVersion');
        //$m = new xmlrpcmsg('dokuwiki.getTitle');
        $m = new xmlrpcmsg('wiki.getPageInfo');
        $m->addParam(new xmlrpcval($name, "string"));// send the message and wait for response
        $r = $c->send($m);
        if($r == false) die('error');
        if(!$r->faultCode()){
        // seems good. Now do whatever you want with the data
        $v = php_xmlrpc_decode($r->value());};
        $res=($v!=NULL);
        //echo "$v";
        return $res;
        }
       

    	public function addDokuwikiTab( &$tab,$modulename, $id, $name1, $name2="", $name3="", $iChars=6 )
        {
            global $DokuwikiURL;
        
            if ($modulename=='projects')
            {
                $URL=$this->getProjectPageName($name1, $iChars);
                if ($this->DokuwikiPageExists($URL))  $tabname="Dokuwiki";  else $tabname="Add DokuwikiPage";
                $DokuwikiURL=$this->combineProjectURL($name1, $iChars);
            }
            else
                if ($modulename=='tasks')
                {
                    $proj=new CProject();
                    $tsk=new CTask();
                    $tsk->load($id);
                    $proj->load($tsk->task_project);
                    $URL=$this->getProjectPageName($proj->project_name, $iChars).":";
                    $URL=$URL.$this->combineStringBeginEnd($name1, $iChars);
                    $URL=$URL."_$id";
                    if ($this->DokuwikiPageExists($URL))  $tabname="Dokuwiki";  else $tabname="Add DokuwikiPage";
                    $DokuwikiURL=$this->combineTaskURL($proj->project_name,$name1."_$id", $iChars);
                }
            else
            {
                    $URL=$this->combineStringBeginEnd($name1, $iChars);
                    $URL=$URL.$this->combineStringBeginEnd($name2, $iChars);
                    $URL=$URL.$this->combineStringBeginEnd($name3, $iChars);
                    if ($this->DokuwikiPageExists($URL))  $tabname="Dokuwiki";  else $tabname="Add DokuwikiPage";
                    $baseURL=$this->getBaseURL();
                    $DokuwikiURL=$baseURL.$URL;
            }
                
            $tab->add(W2P_BASE_DIR . '/modules/dokuwiki/tabcontents', $tabname);
            return $URL;
        }
        
 */       
        
        
  public function delete(w2p_Core_CAppUI $AppUI)
	{
		$this->load();
		return $this->store($AppUI);
	}

	public function store(w2p_Core_CAppUI $AppUI)
	{
        $perms = $AppUI->acl();
        $stored = false;

        $errorMsgArray = $this->check();
        if (count($errorMsgArray) > 0) {
          return $errorMsgArray;
        }
        $q = new w2p_Database_Query;
		$this->w2PTrimAll();


        if ($this->planner_id && $perms->checkModuleItem('planner', 'edit', $this->planner_id)) {
            if (($msg = parent::store())) {
                return $msg;
            }
            $stored = true;
        }
        if (0 == $this->planner_id && $perms->checkModuleItem('planner', 'add')) {
            if (($msg = parent::store())) {
                return $msg;
            }
            $stored = true;
        }
        return $stored;
	}
/*

	public function hook_calendar($userId) {
		return $this->getOpenTodoItems($userId);
	}

    public function hook_search() {
        $search['table'] = 'todos';
        $search['table_alias'] = 't';
        $search['table_module'] = 'todos';
        $search['table_key'] = $search['table_alias'].'.todo_id'; // primary key in searched table
        $search['table_link'] = 'index.php?m=todos&todo_id='; // first part of link
        $search['table_title'] = 'Todos';
        $search['table_orderby'] = 'todo_title';
        $search['search_fields'] = array('todo_title');
        $search['display_fields'] = $search['search_fields'];

        return $search;
    }

*/    
    
    
}
