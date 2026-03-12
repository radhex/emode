<?php namespace Eprog\Manager\Models;

use Model;
use RainLab\User\Models\User;
use RainLab\User\Models\UserGroup;
use RainLab\User\Models\UserGroups;
use Input;
use ApplicationException;
use Flash;
use Mail as SendMail;
use Carbon\Carbon;
use Redirect;
use Webklex\IMAP\Facades\Client;
use Eprog\Manager\Classes\Util;
use Eprog\Manager\Classes\Google;


/**
 * Model
 */
class DriveModel extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \Sushi\Sushi;


    
    /**
     * @var string The database table used by the model.
     */
    

    public $table = 'eprog_manager_drive';

    public $folder;

    public $perPage;

    public $total;

    public function setRecordsPerPage($perPage){

        $this->perPage = $perPage;

    }

    public function getFolder()
    {
     
        $this->folder = '';
        return $this->folder;

    }

    public function beforeFetch()
    {
     
        $this->getCurrent();

    }

    public function getPerPage()
    {
     
        $this->perPage = config("imap.perPage.drive");
        return $this->perPage;

    }

    public function getPage()
    {

        return  post('page') ? post('page') : 1;

    }

    public function getCurrent()
    {



    }

    public function getPageFrom()
    {


    }  

    public function getPageTo()
    {


    }    
  

    public function getTotal()
    {



    }

    public function getLast()
    {
     
      

    }

    public function getRows()
    {
      

        $client = Google::connect();
        $data = [];

        if($client->getAccessToken()){

            $service = new \Google\Service\Drive($client);

            $pageToken = request()->input('pageToken');

            $parameters['q'] = "mimeType='application/vnd.google-apps.folder' and 'root' in parents and trashed=false";

            $parameters  = array(
                'corpora' => "allDrives",
                'pageSize' => 50,
                'fields' => "nextPageToken, files(contentHints/thumbnail,fileExtension,iconLink,id,name,size,thumbnailLink,webContentLink,webViewLink,mimeType,parents, createdTime, modifiedTime)",
                'includeItemsFromAllDrives' => 'true',
                'supportsAllDrives' => 'true',
                'orderBy' => 'folder, name'
            );



            if(Input::segment(4) == "drive"){ 
                if(Input::filled("folder")) 
                    $q = "'me' in owners and '".Input::get("folder")."' in parents and trashed=false";     
                else    
                    $q = "'me' in owners and 'root' in parents and trashed=false";
            }

            if(Input::segment(4) == "drivetrash"){ 
                if(Input::filled("folder")) 
                    $q = "'me' in owners and '".Input::get("folder")."' in parents and trashed=true";     
                else    
                    $q = "'me' in owners and trashed=true";
            }

            if(Input::segment(4) == "driveshared"){ 
                if(Input::filled("folder")) 
                    $q = "'".Input::get("folder")."' in parents and trashed=false";     
                else    
                    $q = "sharedWithMe and trashed=false";
            }



            if(Input::filled("term"))
                $term = trim(Input::get("term"));
            else
                $term = post("listToolbarSearch[term]");

            if($term != "") {
                if(Input::segment(4) == "drive")
                    $q = "'me' in owners and trashed=false and name='".$term."'";
                if(Input::segment(4) == "drivetrash")
                    $q = "'me' in owners and trashed=true and name='".$term."'";
                if(Input::segment(4) == "driveshared")
                    $q = "sharedWithMe and trashed=false and name='".$term."'";
            }

            //$q = "'me' in owners and mimeType='application/vnd.google-apps.folder' and trashed=false";

            $parameters['q']  = $q;

            self::folderPath();             

            if (isset($pageToken) && !empty($pageToken)) {
                $parameters['pageToken'] = $pageToken;
            }

            $files = $service->files->listFiles($parameters);
       
            if($files->nextPageToken)
                $_SESSION['nextPageToken'] = $files->nextPageToken;            
            else
                unset($_SESSION['nextPageToken']);

            $_SESSION['prevPageToken'] = $_SESSION['nextPageToken'] ?? null;

            $l = 1;
            foreach($files as $file) {
                //["gid" => $file->id, "parent" => $file->parents[0] ?? '', 'name' => $file->name] ;//
                $data[] =  ["id" => $l, "gid" => $file->id, "name" => $file->name, "created" => date("Y-m-d H:i:s", strtotime($file->getCreatedTime())), "modified" => date("Y-m-d H:i:s", strtotime($file->getModifiedTime())), "mimeType" => $file->mimeType, "size"=> $file->size, "thumb" => $file->thumbnailLink, "webContentLink" => $file->webContentLink, "webViewLink" => $file->webViewLink];
                $l++;
            }

           // echo Util::makeTree($data,"0AONcocN6P-nkUk9PVA");

        }

        return $data;

    }

    private function folderPath(){


        if(Input::has("folder")) {
            $parameters['q'] = "'".Input::get("folder")."' in parents and trashed=false";     

            if(isset($_SESSION['folder']))
            $is = array_column($_SESSION['folder'], 'name');

            if(!isset($is) || !in_array(Input::get("name"),$is))
            $_SESSION['folder'][] = ["gid" => Input::get("folder"), "name" => Input::get("name")];
        }   
        else {    
            $parameters['q'] = "'root' in parents and trashed=false";
            unset($_SESSION['folder']);
        }

        $sess = [];
        if(isset($_SESSION['folder'])) {
            foreach($_SESSION['folder']as $folder){
                if($folder["gid"] != "")
                $sess[] = ["gid" => $folder["gid"], "name" => $folder["name"]];
                if(Input::has("folder") && Input::get("folder") == $folder["gid"]) break;
            }
        }

        array_unshift($sess,["gid" => "", "name" => e(trans('eprog.manager::lang.mainFolder'))]);
        $_SESSION['folder'] = $sess;


    }


}