<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Redirect;
use Eprog\Manager\Models\SettingConfig as Settings;
use Eprog\Manager\Classes\Google;
use Eprog\Manager\Classes\Util;

class Product extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $requiredPermissions = ['eprog.manager.access_product'];

    public function __construct()
    {
        
        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'product','product');

 
    }


    public function listExtendQuery($query, $definition = null)
    {
   
        $query->orderBy("ord")->orderBy("id", "desc");	

    }


    public function onExport()
    {

        $url = config('cms.backendUri')."/eprog/manager/export/productxml";
        if(isset($_POST['checked'])) $url .= "?checked=".implode(",",$_POST['checked']);
        return Redirect::to($url);

    }

    public function listExtendColumns($list)
    {

        $list->getColumn("brutto")->label = e(trans("eprog.manager::lang.gross"));
        $list->getColumn("netto")->label =  e(trans("eprog.manager::lang.net"));
        $list->getColumn("vat")->label =  e(trans("eprog.manager::lang.vat"));
  
    } 

    public function formExtendFields($form)
    {

        Util::checkExpired();
        Util::checkCapacity();
        
    }

    public function onGoogle()
    {

        Google::sendFile();

    }
    
}