<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Input;
use Redirect;
use Flash;
use October\Rain\Exception\ValidationException;
use Eprog\Manager\Models\Mailing as ModelMailing;
use RainLab\User\Models\User;
use RainLab\User\Models\UserGroup;
use RainLab\User\Models\UserGroups;
use Mail;
use Carbon\carbon;
use Artisan;
use Eprog\Manager\Classes\Google;
use Eprog\Manager\Classes\Util;

class Mailing extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    private $mailing;
    public $name = "mailing";
    public $requiredPermissions = ['eprog.manager.access_mailing'];

    public function __construct()
    {

        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'mailing');

    }

    public function listExtendQuery($query, $definition = null)
    {
      
  		$query->orderBy("id", "desc");	

    }

    public function formExtendFields($form)
    {

        Util::checkExpired();
        Util::checkCapacity();
        
    }

    public function onMail(){


			$id = Input::get("id");

			$this->mailing = ModelMailing::find($id);
			if(!$this->mailing) return false;

      $this->mailing->update(["id"=> $id, "type" => Input::get("type"), "date" => Input::get("Mailing.date"), "groups" => Input::get("Mailing.groups"), "name" => Input::get("Mailing.name"), "desc" => Input::get("Mailing.desc"), "list" => Input::get("Mailing.list")]);
	


      if(Input::get("type") == "group"){

          $groups = UserGroups::where("user_group_id","=", $this->mailing->groups)->get();
          if(sizeof($groups) == 0)
            		throw new ValidationException(['my_field'=>trans("eprog.manager::lang.null_group")]);
          if(sizeof($groups) > 1000)
            		throw new ValidationException(['my_field'=>trans("eprog.manager::lang.full_group")]);


          $emails = [];    
          $l = 0;
          foreach($groups as $group){

          		$user = $group->user_id; 
          		$user = User::find($group->user_id);

          		if(!$user) return false;
              self::sendMail($user->email, $l > 0 ? 0 : 1);
              $emails[] = $user->email;

              $l++; 			
          }

          foreach ($this->mailing->files as $file) {
          			//$file && $file->delete();
          }

          $send = json_decode($this->mailing->send, true) ?? [];
          array_push($send, ["to" => UserGroup::find($this->mailing->groups)->name." ".date("d-m-Y H:i", time()), "emails" => $emails]);
          $this->mailing->update(["send" => json_encode($send)]);


          Flash::success(trans("eprog.manager::lang.mailing_send_group"));

     }

     if(Input::get("type") == "list"){ 

          if(strlen(trim(Input::get("Mailing.list"))) == 0)
              throw new ValidationException(['my_field'=>trans("eprog.manager::lang.required_send_list")]);

          $list = explode("\n", Input::get("Mailing.list"));

          if(sizeof($list) > 100)
              throw new ValidationException(['my_field'=>trans("eprog.manager::lang.max_send_list")]);

          $list = array_unique($list);
          if(sizeof($list) > 0){ 

              foreach($list as $ls){
               if(filter_var(trim($ls), FILTER_VALIDATE_EMAIL) == false) 
                  throw new ValidationException(['my_field'=>trans("eprog.manager::lang.valid_send_list")]);
              }

              $l = 0;
              foreach($list as $ls){
                  self::sendMail($ls, $l > 0 ? 0 : 1);
                  $l++;
              }
          }

          $send = json_decode($this->mailing->send, true) ?? [];
          array_push($send, ["to" => date("d-m-Y H:i", time()), "emails" => $list]);
          $this->mailing->update(["send" => json_encode($send)]);   

          Flash::success(trans("eprog.manager::lang.mailing_send_list"));
     }


	    return Redirect::to("/".config('cms.backendUri')."/eprog/manager/mailing/update/".$id );


    } 

    private function sendMail($email, $sent){


              $data = [

                  "id" => $this->mailing->id,
                  "title" => $this->mailing->name,
                  "body" => $this->mailing->desc,
                  "email" => $email

              ];

              $send = Mail::send('eprog.manager::mail.mailing', $data, function($message) use ($data) {
                    $message->to($data['email']);
                    foreach($this->mailing->files as $file) 
                    $message->attach($file->getLocalPath(), ['as' => $file->file_name]);
              });

              
              if($sent){

                  $stream = imap_open(
                      "{".env('MAIL_HOST', 'smtp.mailgun.org')."/imap/ssl/novalidate-cert}",
                      env('MAIL_USERNAME', 'username'),
                      env('MAIL_PASSWORD', 'password'),
                      null,
                      1,
                      ['DISABLE_AUTHENTICATOR' => 'GSSAPI']
                  );
                  
                  imap_append(
                      $stream,
                      "{".env('MAIL_HOST', 'localhost')."/imap/ssl/novalidate-cert}" . env('IMAP_SENT', 'Sent'),
                      $send->toString()."\r\n",
                      "\\Seen" 
                  );
                  
                  imap_close($stream);

              }

    }

    public function onExport()
    {

        $url = config('cms.backendUri')."/eprog/manager/export/mailingxml";
        if(isset($_POST['checked'])) $url .= "?checked=".implode(",",$_POST['checked']);
        return Redirect::to($url);

    }

    public function onGoogle()
    {

        Google::sendFile();

    }


}