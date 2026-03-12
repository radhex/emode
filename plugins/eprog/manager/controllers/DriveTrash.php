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

class DriveTrash extends DriveController
{

    public function __construct()
    {
  
        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'drive',  'drivetrash');
     
        
    }


    public function listExtendQuery($query, $definition = null)
    {
        

    }  


    public function onDelete()
    {

        $client = Google::connect();
        if($client->getAccessToken()){

            $service = new \Google_Service_Drive($client);
            $service->files->delete(Input::get("id"));
            Flash::success(Lang::get('eprog.manager::lang.process_success'));
            return Redirect::to($_SERVER['REQUEST_URI']);

    
        }

    }

    public function onRestore()
    {

        $client = Google::connect();
        if($client->getAccessToken()){

            $service = new \Google_Service_Drive($client);

            $metadata = new \Google_Service_Drive_DriveFile();
            $metadata->setTrashed(false);
            $res = $service->files->update(Input::get("id"), $metadata);
            Flash::success(Lang::get('eprog.manager::lang.process_success'));
            return Redirect::to($_SERVER['REQUEST_URI']);

    
        }

    }

}