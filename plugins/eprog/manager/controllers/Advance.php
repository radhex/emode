<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use Eprog\Manager\Classes\Util;
use Eprog\Manager\Models\SettingConfig;
use BackendMenu;
use Redirect;
use Session;
use Mpdf\Mpdf;
use Input;

class Advance extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = ['eprog.manager.manage_accounting'];

    public function __construct(){

        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'accounting', 'advance');

    }

    public function listExtendQuery($query, $definition = null)
    {
 
        $nip = Session::has("selected.nip") ? Session::get("selected.nip") : SettingConfig::get("nip"); 

        if($nip)
            $query->where("nip", $nip);  
        else 
            $query->where("id",0); 

        $query->orderBy("year", "desc")->orderBy("month", "desc");

    }

    public function formExtendFields($form)
    {

        Util::checkExpired();
        Util::checkCapacity();

        if(!isset($_SESSION)) session_start();
    

        if(Input::segment(5) == "create") { 
            
            $year = Session::has("selected.year") ? Session::get("selected.year") : date("Y",time());
            $month = Session::has("selected.month") ? Session::get("selected.month") : date("m",time());

            $form->getField('year')->value =  $year;
            $form->getField('month')->value =  $month ; 

        }



    }


    public function onExport()
    {

        $url = config('cms.backendUri')."/eprog/manager/export/advancexml";
        $arg = "";
        if(isset($_POST['checked'])) $arg .= "&checked=".implode(",",$_POST['checked']);
        if(isset($_POST['nip'])) $arg .= "&nip=".$_POST['nip'];
        if(strlen($arg) > 0) $arg[0] = "?";
        return Redirect::to($url.$arg);
    }

    public static function year($id = null)
    {

        $return = [];
        $statuses = explode(PHP_EOL,SettingStatus::get("invoice_".Lang::getLocale()) ?? []);

        foreach($statuses as $status){
            $st = explode(";",$status);
            $return[$st[0]] = $st[1];
        }

        return $return;

    }


}