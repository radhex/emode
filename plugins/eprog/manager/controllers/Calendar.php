<?php namespace Eprog\Manager\Controllers;

use Eprog\Manager\Classes\Util;
use Backend\Classes\Controller;
use BackendMenu;

class Calendar extends Controller
{

    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = ['eprog.manager.access_scheduler'];

    public function __construct()
    {

        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'calendar');

    }


    public function index()
    {

        Util::checkExpired();
        Util::checkCapacity();
        
        $this->pageTitle = e(trans('eprog.manager::lang.calendar'));

    }


}