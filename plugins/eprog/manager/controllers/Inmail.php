<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Flash;
use Input;
use Redirect;
use Eprog\Manager\Models\Inmail as ModelMail;
use Eprog\Manager\Classes\Util;
use Rainlab\User\Models\User;
use Request;
use BackendAuth;
use Eprog\Manager\Classes\Google;

class Inmail extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $requiredPermissions = ['eprog.manager.access_mail'];


    public function __construct()
    {

        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'mail', 'admin_inbox');
        
    }

    public function listExtendQuery($query, $definition = null)
    {
      
      	if(Input::segment("5") == "inbox"){
      		//$query->where("send", "=", 0);
            if(!BackendAuth::getUser()->hasAccess("eprog.manager.manage_mail"))
      		$query->where("receiver_id", "=", BackendAuth::getUser()->id);

      	}
      	if(Input::segment("5") == "sent"){
      		//$query->where("send", "=", 1);
      		if(!BackendAuth::getUser()->hasAccess("eprog.manager.manage_mail"))
      		$query->where("sender_id", "=", BackendAuth::getUser()->id);

      	}		

		$query->orderBy("id", "desc");	

    }

    public function listExtendColumns($list)
    {

    
        if(!BackendAuth::getUser()->hasAccess("eprog.manager.manage_mail")){
     		//$list->removeColumn("id"); 
     	}

        $list->removeColumn("send");
      
    
    }

    public function listFilterExtendScopes($scope)
    {
        if(Input::segment("5") == "inbox" || Input::segment("5") == "sent" ){
        	$scope->removeScope("send");
        }
    }

    public function inbox()
    {

    	$this->makeLists($this);
		$this->pageTitle = e(trans('eprog.manager::lang.outmail'));
        BackendMenu::setContext('Eprog.Manager', 'mail', 'admin_inbox');
  
    }

    public function sent()
    {

    	$this->makeLists($this);
		$this->pageTitle = e(trans('eprog.manager::lang.outmail'));
        BackendMenu::setContext('Eprog.Manager', 'mail', 'admin_sent');

    }


    public function onMail() {

		if(Input::has("create") && Input::get("create"))
		return Redirect::to(config('cms.backendUri')."/eprog/manager/mail/create");

        if(Input::has("del_mail_id") && Input::get("del_mail_flag")) {

            ModelMail::destroy(Input::get("del_mail_id"));
			Flash::success(trans("eprog.manager::lang.del_task"));
                
        }   

    }

    public function getReceiverIdOptions(){

        return  ModelMail::getReceiverIdOptions();

    }

    public function getSenderIdOptions(){

        return  ModelMail::getSenderIdOptions();

    }


    public function formExtendFields($form)
    {
        Util::checkExpired();
        Util::checkCapacity();

		if((Input::segment(5) == "preview" || Input::segment(5) == "update") && Input::segment(6) > 0) {
			$tmpmail = ModelMail::find(Input::segment(6));
			if(BackendAuth::getUser()->id  == $tmpmail->receiver_id && !BackendAuth::getUser()->is_superuser) $tmpmail->update(["read"=>1]);
		}
		

	  	if(Input::segment(5) == "inbox" ) { 

			$form->removeField("name");
			$form->removeField("desc");
			$form->removeField("files");
			$form->removeField("date");
			$form->removeField("receiver_id");
			$form->removeField("sender_id");
			$form->removeField("partial0");
			$form->removeField("partial3");

		}

	  	else if(Input::segment(5) == "outbox" ) { 

			$form->removeField("name");
			$form->removeField("desc");
			$form->removeField("files");
			$form->removeField("date");
			$form->removeField("receiver_id");
			$form->removeField("sender_id");
			$form->removeField("partial0");
			$form->removeField("partial2");

		}
	  	else  if(Input::segment(5) == "preview" ) { 
	  	
			$form->removeField("name");
			$form->removeField("desc");
			$form->removeField("files");
			$form->removeField("date");
			$form->removeField("receiver_id");
			$form->removeField("sender_id");
			$form->removeField("partial0");
			$form->removeField("partial1");
			$form->removeField("partial2");
			$form->removeField("partial3");
		
		}
		else {

			if(Input::has("reply")) {
				
				$ml = ModelMail::find(Input::get("reply"));	
				if($ml) {
					$form->getField('name')->value = "Re:".$ml->name;
					$form->getField('desc')->value = "\n\n\n------------------------   ".$ml->sender->first_name." ".$ml->sender->last_name." ".$ml->date." ".Util::timeZoneOffset()."   ------------------------\n".$ml->desc;
	 				$form->getField('sender_id')->value = $ml->receiver_id;
	 				$form->getField('receiver_id')->value = $ml->sender_id;
				}
			}

			if(Input::has("receiver_id")) {
				
				$form->getField('receiver_id')->value = Input::get("receiver_id");

			}

            if(Input::has("sender_id")) {
                
                $form->getField('sender_id')->value = Input::get("receiver_id");
                                        
            }

	  		if(Input::segment(5) == "create" ) {
				$form->removeField("partial0");
				$form->removeField("date");
			}

			$form->removeField("partial1");
			$form->removeField("partial2");
			$form->removeField("partial3");
			$form->removeField("partial4");

		}

    }

    public function onExport()
    {

        $url = config('cms.backendUri')."/eprog/manager/export/inmailxml";
        if(isset($_POST['checked'])) $url .= "?checked=".implode(",",$_POST['checked']);
        return Redirect::to($url);

    }

    public function onGoogle()
    {

        Google::sendFile();

    }

}