<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use Rainlab\User\Models\User;
use Eprog\Manager\Classes\Util;
use Eprog\Manager\Models\SettingConfig;
use Eprog\Manager\Models\Worker as ModelWorker;
use October\Rain\Exception\ValidationException;
use BackendMenu;
use Redirect;
use Session;
use Mpdf\Mpdf;
use Input;

class Worker extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = ['eprog.manager.manage_accounting'];

    public function __construct(){

        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'accounting', 'worker');
        
    }

    public function listExtendQuery($query, $definition = null)
    {
      
        $nip = Session::has("selected.nip") ? Session::get("selected.nip") : SettingConfig::get("nip"); 

        if($nip)
            $query->where("nip", $nip);  
        else 
            $query->where("id",0); 

        $query->orderBy("id", "asc"); 


    }

    public function formExtendFields($form)
    {

        Util::checkExpired();
        Util::checkCapacity();
        
    }
    
    public function onExport()
    {

        $url = config('cms.backendUri')."/eprog/manager/export/workerxml";
        $arg = "";
        if(isset($_POST['checked'])) $arg .= "&checked=".implode(",",$_POST['checked']);        if(isset($_POST['year'])) $arg .= "&year=".$_POST['year'];
        if(isset($_POST['nip'])) $arg .= "&nip=".$_POST['nip'];
        if(strlen($arg) > 0) $arg[0] = "?";
        return Redirect::to($url.$arg);

    }


}