<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Input;
use Backend\Controllers\Files;
use Eprog\Manager\Classes\Util;
use BackendAuth;
use Redirect;
use Session;
use Lang;
use Eprog\Manager\Models\SettingStatus;
use Eprog\Manager\Classes\Google;
use System\Models\File as SystemFile;
use Flash;
use Eprog\Manager\Models\Project as ModelProject;
use Eprog\Manager\Classes\Ksef;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Support\Str;
use View;

class Project extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $groups;

    public $requiredPermissions = ['eprog.manager.access_project'];
    
    public function __construct()
    {

	    $this->groups = Util::getUserGroups();
        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'project');
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
    
    }
    
    public function listExtendQuery($query, $definition = null){
    
        if(Input::has("id"))
        $query->where('user_id', '=', Input::get("id"));

     	if(!BackendAuth::getUser()->hasAccess('eprog.manager.manage_project'))
    	$query->where('staff','like','%"'.BackendAuth::getUser()->id.'"%');

      	$query->orderBy("id", "desc");	

    }

    public function formExtendQuery($query, $definition = null){
    
     	if(!BackendAuth::getUser()->hasAccess('eprog.manager.manage_project'))
    	$query->where('staff','like','%"'.BackendAuth::getUser()->id.'"%');

    }
    
    
    public function getDownloadUrl($file) {

        return Files::getDownloadUrl($file);
    
    }
  


    public function formExtendFields($form)
    {
        Util::checkExpired();
        Util::checkCapacity();
        
      	if(!BackendAuth::getUser()->hasAccess('eprog.manager.manage_project')) { 

        	$form->getField("name")->disabled = true;
    		$form->getField("start")->disabled = true;

    	
    		$form->removeField("public");
    		$form->removeField("staff");
    		$form->getField("name")->disabled = true;
    		$form->getField("start")->disabled = true;

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

    	if(!BackendAuth::getUser()->hasAccess('eprog.manager.manage_project')) { 

    		$list->showSetup = false;
    		$list->removeColumn("public");
    		$list->removeColumn("files");
    		$list->removeColumn("staff");
      
    	}

    } 

    public function onPdf()
    {
        $file = self::onPdfGenerate(Input::segment(6));
        if(file_exists($file)) return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getfile?file='.urlencode($file));
    }

    public static function onPdfGenerate($id)
    {
        View::addLocation('plugins/eprog/manager/controllers/printer');
        $project = ModelProject::find($id);  
        if($project){   
            $file = storage_path('temp/public/'.trans('eprog.manager::lang.project_one').'('.$project->id.') - '.str_replace("/","_",Str::slug($project->name)).'.pdf');
            $pdf = SnappyPdf::loadView('project', compact('id'));
            $pdf->save($file);
            return $file;      
        }
        else
            Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));
            
    }

    public function onExport()
    {

        $url = config('cms.backendUri')."/eprog/manager/export/projectxml";
        if(isset($_POST['checked'])) $url .= "?checked=".implode(",",$_POST['checked']);
        return Redirect::to($url);

    }

    public function onGoogle()
    {

        Google::sendFile();

    }
}