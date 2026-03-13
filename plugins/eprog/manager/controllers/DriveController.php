<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Input;
use Redirect;
use Flash;
use October\Rain\Exception\ValidationException;
use Eprog\Manager\Models\Mailing as ModelMailing;
use Eprog\Manager\Models\File as ModelFile;
use RainLab\User\Models\User;
use RainLab\User\Models\UserGroup;
use RainLab\User\Models\UserGroups;
use Mail;
use Carbon\carbon;
use Artisan;
use Webklex\IMAP\Facades\Client;
use System\Models\File;
use Lang;
use Eprog\Manager\Classes\Google;
use Eprog\Manager\Classes\Util;



class DriveController extends Controller
{
    public $implement = ['Extends\Backend\Behaviors\ListController','Extends\Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $name = "drive";


    public $requiredPermissions = ['eprog.manager.access_drive'];

    public function __construct()
    {

        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'drive',  'drive');
        $this->folder = '';
        
    }

    public function index()
    {

        $this->addJs('/plugins/rainlab/user/assets/js/bulk-actions.js');
        $this->asExtension('ListController')->index();

    }

    public function listExtendQuery($query, $definition = null)
    {
      

  		$query->orderBy("id", "desc");	

    }

    public function inbox()
    {


    }


    public function formExtendFields($form)
    {

        Util::checkExpired();
        Util::checkCapacity();
    }

    
    public function onDownload()
    {

        $client = Google::connect();

        if($client->getAccessToken()){

            $service = new \Google_Service_Drive($client);
            $file = $service->files->get(Input::get("id"),['fields' => 'name,id,size,webContentLink','supportsAllDrives' => true,'supportsTeamDrives' => true]);
            if($file->webContentLink)
            return Redirect::to($file->webContentLink);
                     
        }
        
    }


    public function index_onBulkAction()
    {

        \Log::info(post());

        $client = Google::connect();
        if($client->getAccessToken()){
            $service = new \Google_Service_Drive($client);

                if (
                    ($bulkAction = post('action')) &&
                    ($checkedIds = post('checked')) &&
                    is_array($checkedIds) &&
                    count($checkedIds)
                ) {

                    foreach ($checkedIds as $Id) {           
                        switch ($bulkAction) {

                            case 'trash':

                                $metadata = new \Google_Service_Drive_DriveFile();
                                $metadata->setTrashed(true);
                                $service->files->update($Id, $metadata);

                                break; 
                                
                            case 'delete':
                                    $service->files->delete($Id);         
                                break;

                            case 'restore':

                                $metadata = new \Google_Service_Drive_DriveFile();
                                $metadata->setTrashed(false);
                                $service->files->update($Id, $metadata);

                                break;  
              
                        }
                    }

                    Flash::success(Lang::get('eprog.manager::lang.process_success'));
                }
                else {
                    Flash::error(Lang::get('eprog.manager::lang.selected_empty'));
                }

                return $this->listRefresh();
        }
    }


    public function onLogout()
    {

        return Redirect::to("/".config('cms.backendUri')."/eprog/manager/drive?logout=true");

    }

}