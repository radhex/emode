<?php namespace Eprog\Manager\Classes;

use Carbon\Carbon;
use Lang;
use October\Rain\Argon\Argon;
use Backend\Helpers\Backend;
use Session;
use BackendAuth;
use Timezone;
use Config;
use Settings;
use Auth;
use Backend\Models\UserPreference;
use Rainlab\User\Models\User;
use DateTime;
use DateTimeZone;
use Mail;
use Eprog\Manager\Models\Category;
use Eprog\Manager\Models\Refreshtoken;
use System\Models\File as SystemFile;
use Flash;
use Input;

class Google
{

  
    public static function connect(){

           if(!isset($_SESSION)) session_start();
           $redirect_uri = (isset($_SERVER['HTTPS']) ? "https":"http")."://".$_SERVER["SERVER_NAME"]."/".config('cms.backendUri')."/eprog/manager/drive";

           $oauthConfig = [
               'web' => [
                   'client_id' => env('GOOGLE_CLIENT_ID'),
                   'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                   'redirect_uris' => [
                       env('GOOGLE_REDIRECT_URI')
                   ]
               ]
           ];
           
           $scope = constant('\Google_Service_Drive::'.(env('GOOGLE_SCOPE') ?? 'DRIVE'));     
           $client = new \Google\Client();
           $client->setAuthConfig($oauthConfig);
           $client->setRedirectUri($redirect_uri);
           $client->setScopes([$scope]);
           $client->setAccessType('offline');
           //$client->setApprovalPrompt('force');
           //$client->setIncludeGrantedScopes(true); 

           //dd(date("Y-m-d H:i:s", $_SESSION['upload_token']['created']));

           if (isset($_REQUEST['logout']) && isset($_SESSION['upload_token']['access_token'])) {
               $logout = @file_get_contents("https://accounts.google.com/o/oauth2/revoke?token=".$_SESSION['upload_token']['access_token']);
               if(!preg_match("/error/", $logout)){
                  self::updateToken(null);
                  //".config('cms.backendUri')."Auth::logout();
                  unset($_SESSION['upload_token']);
                  $redirect_uri = (isset($_SERVER['HTTPS']) ? "https":"http")."://".$_SERVER["SERVER_NAME"]."/".config('cms.backendUri');
                  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
                  exit();
              }            
           }

           if (isset($_GET['code'])) {
               $token = $client->fetchAccessTokenWithAuthCode($_GET['code'], $_SESSION['code_verifier']);
               $_SESSION['upload_token'] = $token; 
               if(isset($token['refresh_token'])) self::updateToken($token['refresh_token']);
               header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
           }
                      
           $refresh_token  = Refreshtoken::where("user_id","=", BackendAuth::getUser()->id)->first();

           if(isset($_SESSION['upload_token']) && isset($refresh_token['token']) && $refresh_token['token'] != "")
               $_SESSION['upload_token']['refresh_token'] = $refresh_token['token'];
         
           if(!isset($_SESSION['upload_token']['created']))
           unset($_SESSION['upload_token']);

           if(isset($_SESSION['upload_token'])){
                if(time() > $_SESSION['upload_token']['created'] + $_SESSION['upload_token']['expires_in']){ 
                    if($_SESSION['upload_token']['refresh_token']) {
                        $client->fetchAccessTokenWithRefreshToken($_SESSION['upload_token']['refresh_token']);        
                        $_SESSION['upload_token'] = $client->getAccessToken();
                        if(isset($_SESSION['upload_token']['refresh_token'])) self::updateToken($_SESSION['upload_token']['refresh_token']);
                    }
                    else 
                        unset($_SESSION['upload_token']);  
                }
                else               
                $client->setAccessToken($_SESSION['upload_token']);
           }
           else{ 
                if(isset($refresh_token['token']) && $refresh_token['token'] != ""){
                    $client->fetchAccessTokenWithRefreshToken($refresh_token['token']);
                    if($client->getAccessToken()){            
                        $_SESSION['upload_token'] = $client->getAccessToken();
                        if(isset($_SESSION['upload_token']['refresh_token'])) self::updateToken($_SESSION['upload_token']['refresh_token']);
                    }
                    else{
                        self::updateToken("");
                        header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
                    }
                }
                else {
                    $_SESSION['code_verifier'] = $client->getOAuth2Service()->generateCodeVerifier();
                    $authUrl = $client->createAuthUrl();
                    header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
                    $_SESSION['authUrl'] = $authUrl;
                }
           }

           return $client;
        
    }

    private static function updateToken($token){

        $exists = Refreshtoken::where("user_id","=", BackendAuth::getUser()->id)->first(); 
        if($exists){ 
            $exists->update(["token" => ""]);
            $exists->update(["token" => $token]);
          }
        else
            Refreshtoken::create(['user_id' => BackendAuth::getUser()->id, "token" => $token]); 

    }

    public static function sendFile()
    {

        $client = self::connect();
        if($client->getAccessToken()){
          $service = new \Google_Service_Drive($client);


            $file = SystemFile::find(Input::get("file_id"));
            if($file && $file->exists()){
          
                $uplodFile = new \Google\Service\Drive\DriveFile();
                $uplodFile->setName($file->file_name);
                $result = $service->files->create(
                    $uplodFile,
                    [
                        'data' => file_get_contents($file->getLocalPath()),
                        'mimeType' => 'application/octet-stream',
                        'uploadType' => 'multipart'
                    ]
                );

                Flash::success(Lang::get('eprog.manager::lang.process_success'));
                //return Redirect::to("/".config('cms.backendUri')."/eprog/manager/drive");


            }
        }

    }

    

}