<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Backend\Controllers\Files;
use Input;
use Eprog\Manager\Classes\Util;
use BackendAuth;
use Redirect;
use Session;
use Lang;
use Eprog\Manager\Models\SettingStatus;
use Eprog\Manager\Classes\Google;

class Work extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $groups;

    public $requiredPermissions = ['eprog.manager.access_work'];
    
    public function __construct()
    {

	    $this->groups = Util::getUserGroups();
        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'work');
        SettingStatus::get("project_".Session::get("locale"));
        
    }

    public static function status($id = null)
    {

        $return = [];
        $statuses = explode(PHP_EOL,SettingStatus::get("order_".Lang::getLocale()) ?? []);

        foreach($statuses as $status){
            $st = explode(";",$status);
            $return[$st[0]] = $st[1];
        }

        return $return;

    }

    public function listFilterExtendScopes($scope)
    {

        $status = self::status();
        unset($status[0]);
        $scope->getScope("status")->options = $status;

        
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.manage_work')) { 

            $scope->removeScope("project_id");
        }
    
    }
    
    public function listExtendQuery($query, $definition = null)
    {
    
        if(Input::has("id"))
        $query->where('project_id', '=', Input::get("id"));


     	if(!BackendAuth::getUser()->hasAccess('eprog.manager.manage_work')) 
    	$query->where('staff','like','%"'.BackendAuth::getUser()->id.'"%');

      	$query->orderBy("id", "desc");	
    }

    public function formExtendQuery($query, $definition = null)
    {
    

     	if(!BackendAuth::getUser()->hasAccess('eprog.manager.manage_work')) 
    	$query->where('staff','like','%"'.BackendAuth::getUser()->id.'"%');

    }
    
    
    public function getDownloadUrl($file)
    {

        return Files::getDownloadUrl($file);
    }

    public function formExtendFields($form)
    {

        Util::checkExpired();
        Util::checkCapacity();
        
      	if(!BackendAuth::getUser()->hasAccess('eprog.manager.manage_work')){ 


    		$form->removeField("desc");
    		$form->removeField("public");
    		$form->removeField("public_files");
    		$form->removeField("staff");
    		$form->getField("name")->disabled = true;
    		$form->getField("start")->disabled = true;
    		$form->getField("stop")->disabled = true;
    		$form->getField("status_id")->disabled = true;

    	}

        if(Input::segment(5) == "create") {

            if(Input::filled("start"))
            $form->getField("start")->value = Input::get("start");

            if(Input::filled("stop"))
            $form->getField("stop")->value = Input::get("stop");
        
        }  

    }

    public function listExtendColumns($list)
    {


    	if(!BackendAuth::getUser()->hasAccess('eprog.manager.manage_work')) { 

    		$list->showSetup = false;
    		$list->showFilter = false;
    		$list->removeColumn("public");
    		$list->removeColumn("files");
    		$list->removeColumn("staff");
    		$list->removeColumn("desc");

      
    	}

    }


    public function onExport()
    {

        $url = config('cms.backendUri')."/eprog/manager/export/workxml";
        if(isset($_POST['checked'])) $url .= "?checked=".implode(",",$_POST['checked']);
        return Redirect::to($url);

    }

    public function onGoogle()
    {

        Google::sendFile();

    }

}