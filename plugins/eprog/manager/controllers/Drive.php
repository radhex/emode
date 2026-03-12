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

class Drive extends DriveController
{

    public function __construct()
    {
        
        if(Input::segment(5) == "create"){
            $this->formConfig = 'config_form_create.yaml'; 
            $this->requiredPermissions = ['eprog.manager.manage_drive'];
        }
        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'drive',  'drive');

        
    }


    public function listExtendQuery($query, $definition = null)
    {
        

    }


    public function onTrash()
    {

        $client = Google::connect();
        if($client->getAccessToken()){

            $service = new \Google_Service_Drive($client);

            $metadata = new \Google_Service_Drive_DriveFile();
            $metadata->setTrashed(true);
            $res = $service->files->update(Input::get("id"), $metadata);
            Flash::success(Lang::get('eprog.manager::lang.process_success'));
            return Redirect::to($_SERVER['REQUEST_URI']);

    
        }

    }

    public function onGoogle()
    {

        Google::sendFile();

    }


    public function onExport()
    {

        $url = config('cms.backendUri')."/eprog/manager/export/fixedxml";
        $arg = "";
        if(isset($_POST['checked'])) $arg .= "&checked=".implode(",",$_POST['checked']);
        if(isset($_POST['nip'])) $arg .= "&nip=".$_POST['nip'];
        if(strlen($arg) > 0) $arg[0] = "?";
        return Redirect::to($url.$arg);


    }

    public function onDoc()
    {
        $file = self::generatePdf();
        return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getfile?file='.urlencode($file));
    }


    public static function getFile($id)
    {

   
            $client = Google::connect();
            if($client->getAccessToken()){

                $service = new \Google_Service_Drive($client);
                $file = $service->files->get($id,['fields' => 'name,id,size,webContentLink','supportsAllDrives' => true,'supportsTeamDrives' => true]);

                if($file){
                    
                        $http = $client->authorize();

                        $tmp_file = "storage/temp/public/".$file->name;
                        $fp = fopen($tmp_file, 'w');

                        $chunkSizeBytes = 1 * 1024 * 1024;
                        $chunkStart = 0;

                        while ($chunkStart < $file->size) {
                            $chunkEnd = $chunkStart + $chunkSizeBytes;
                            $response = $http->request(
                                'GET',
                                sprintf('/drive/v3/files/%s', $file->id),
                                [
                                'query' => ['alt' => 'media'],
                                'headers' => [
                                'Range' => sprintf('bytes=%s-%s', $chunkStart, $chunkEnd)
                                ]
                                ]
                            );
                            $chunkStart = $chunkEnd + 1;
                            fwrite($fp, $response->getBody()->getContents());
                        }

                        fclose($fp);

                        return $tmp_file;
                    
                }

            }
     

    }

}