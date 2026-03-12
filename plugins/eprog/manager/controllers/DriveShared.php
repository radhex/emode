<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Input;
use Redirect;
use Flash;
use October\Rain\Exception\ValidationException;
use Eprog\Manager\Models\Inboxupdate;
use RainLab\User\Models\User;
use RainLab\User\Models\UserGroup;
use RainLab\User\Models\UserGroups;
use Mail;
use Carbon\carbon;
use Artisan;
use Webklex\IMAP\Facades\Client;
use Lang;
use Session;
use Eprog\Manager\Classes\Google;

class DriveShared extends DriveController
{

    public function __construct()
    {
  
        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'drive',  'driveshared');
     
        
    }


    public function listExtendQuery($query, $definition = null)
    {
        

    }  



}