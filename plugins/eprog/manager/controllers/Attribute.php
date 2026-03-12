<?php namespace Eprog\Manager\Controllers;

use Eprog\Manager\Classes\Util;
use Backend\Classes\Controller;
use BackendMenu;
use Redirect;

class Attribute extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = ['eprog.manager.manage_product'];

    public function __construct()
    {

        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'product', 'attribute');
        
    }

    public function listExtendQuery($query, $definition = null){
      
        $query->orderBy("id", "desc");	

    }

    public function onExport()
    {

        $url = config('cms.backendUri')."/eprog/manager/export/attributexml";
        if(isset($_POST['checked'])) $url .= "?checked=".implode(",",$_POST['checked']);
        return Redirect::to($url);

    }


    public function formExtendFields($form)
    {

        Util::checkExpired();
        Util::checkCapacity();
        
    }

}