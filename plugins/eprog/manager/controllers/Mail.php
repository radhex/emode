<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Flash;
use Input;
use Redirect;
use Eprog\Manager\Models\Mail as ModelMail;
use Rainlab\User\Models\User;
use Eprog\Manager\Classes\Util;
use Request;
use BackendAuth;
use Config;
use Eprog\Manager\Classes\Google;

class Mail extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $requiredPermissions = ['eprog.manager.access_mail'];


    public function __construct()
    {

        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'mail', 'user_inbox');

    }

    public function listExtendQuery($query, $definition = null)
    {
      
      	if(Input::segment("5") == "inbox"){
      		$query->where("send", "=", 0);
            if(!BackendAuth::getUser()->hasAccess("eprog.manager.manage_mail"))
      		$query->where("admin_id", "=", BackendAuth::getUser()->id);

      	}
      	if(Input::segment("5") == "sent"){
      		$query->where("send", "=", 1);
      		if(!BackendAuth::getUser()->hasAccess("eprog.manager.manage_mail"))
      		$query->where("admin_id", "=", BackendAuth::getUser()->id);

      	}		

		$query->orderBy("id", "desc");	

    }

    public function listExtendColumns($list)
    {


    	if(Input::segment("5") == "sent"){

    		$list->removeColumn("user_id");
    		$list->removeColumn("date");
    		$list->getColumn("admin_id")->label = e(trans('eprog.manager::lang.from'));
    		$list->addColumns([
                'user_id' => [
                    'label' => 'eprog.manager::lang.to',
                    'type' => 'partial',
                    'path' => 'user',
                    'sortable' => 'true'
                ],
            ]);
    		$list->addColumns([
                'date' => [
                    'label' => 'eprog.manager::lang.date',
                    'type' => 'partial',
                    'path' => 'date'
                ],
            ]);
    	}

        if(Input::segment("5") == null){

            $list->getColumn("user_id")->label = e(trans('eprog.manager::lang.user'));
            $list->getColumn("admin_id")->label = e(trans('eprog.manager::lang.admin'));

        }
 
        if(!BackendAuth::getUser()->hasAccess("eprog.manager.manage_mail")){
     		//$list->removeColumn("id"); 
     	}

        if(Input::segment("5") == "inbox" || Input::segment("5") == "sent" ){
            $list->removeColumn("send");
        }
   	
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
        BackendMenu::setContext('Eprog.Manager', 'mail', 'user_inbox');
  
    }

    public function sent()
    {

    	$this->makeLists($this);
		$this->pageTitle = e(trans('eprog.manager::lang.outmail'));
        BackendMenu::setContext('Eprog.Manager', 'mail', 'user_sent');

    }

    public function onMail()
    {


		if(Input::has("create") && Input::get("create"))
		return Redirect::to(config('cms.backendUri')."/eprog/manager/mail/create");

        if(Input::has("del_mail_id") && Input::get("del_mail_flag")) {

            ModelMail::destroy(Input::get("del_mail_id"));
			Flash::success(trans("eprog.manager::lang.del_task"));
                
        }   

	

    }

    public function getUserIdOptions()
    {

        return  ModelMail::getUserIdOptions();

    }

    public function getAdminIdOptions()
    {

        return  ModelMail::getAdminIdOptions();

    }


    public function formExtendFields($form)
    {

        Util::checkExpired();
        Util::checkCapacity();
        
		if((Input::segment(5) == "preview" || Input::segment(5) == "update") && Input::segment(6) > 0) {
			$tmpmail = ModelMail::find(Input::segment(6));
			if(!$tmpmail->send && !BackendAuth::getUser()->is_superuser) $tmpmail->update(["read"=>1]);
		}
	

	  	if(Input::segment(5) == "inbox" ) { 

			$form->removeField("name");
			$form->removeField("desc");
			$form->removeField("files");
			$form->removeField("date");
			$form->removeField("user_id");
			$form->removeField("admin_id");
			$form->removeField("partial0");
			$form->removeField("partial3");

		}

	  	else if(Input::segment(5) == "outbox" ) { 

			$form->removeField("name");
			$form->removeField("desc");
			$form->removeField("files");
			$form->removeField("date");
			$form->removeField("user_id");
			$form->removeField("admin_id");
			$form->removeField("partial0");
			$form->removeField("partial2");

		}
	  	else  if(Input::segment(5) == "preview" ) { 

			$form->removeField("name");
			$form->removeField("desc");
			$form->removeField("files");
			$form->removeField("date");
			$form->removeField("user_id");
			$form->removeField("admin_id");
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
					$form->getField('desc')->value = "\n\n\n------------------------   ".($ml->user ? $ml->user->name." ".$ml->user->surname : '')." ".$ml->date." ".Util::timeZoneOffset()."   ------------------------\n".$ml->desc;
	 				if($ml->user) $form->getField('user_id')->value = $ml->user_id;
	 				if($ml->admin) $form->getField('admin_id')->value = $ml->admin_id;
				}
			}

			if(Input::has("user_id")) {
				
				$form->getField('user_id')->value = Input::get("user_id");
				if(BackendAuth::getUser())
				$form->getField('admin_id')->value = BackendAuth::getUser()->id;
				
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

        $url = config('cms.backendUri')."/eprog/manager/export/mailxml";
        if(isset($_POST['checked'])) $url .= "?checked=".implode(",",$_POST['checked']);
        return Redirect::to($url);

    }

    public function onGoogle()
    {

        Google::sendFile();

    }

}