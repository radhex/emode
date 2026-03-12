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

class Inbox extends ImapController
{

    public function __construct()
    {

        if(Input::segment(5) == "create"){
            $this->formConfig = 'config_form_create.yaml'; 
            $this->requiredPermissions = ['eprog.manager.manage_inbox'];
        }
        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'inbox',  'inbox');
        $this->folder = config("imap.folders.inbox.folder");

        
    }

    public function inbox()
    {

        return $this->connect()->query()->unseen()->get()->count();
      
    }


    public function formExtendFields($form)
    {



        if(Input::segment(5) == "preview" && is_array(json_decode($form->model->attach, true)) && sizeof(json_decode($form->model->attach, true)) == 0)
        $form->getField('attach')->hidden = true;

        if(Input::segment(5) == "preview" && strlen(trim($form->model->cc)) == 0)
        $form->getField('cc')->hidden = true;

        if(Input::segment(5) == "create"){

            if(Input::get("action") == "re" && Input::filled("folder") && Input::get("id") > 0) {

                $client = Client::account('default');
                $client->connect();
                $folder = $client->getFolder(config("imap.folders.".Input::get("folder").".folder"));                
                $message = $folder->query()->getMessage(Input::get("id"));

                $to = $message->getFrom()[0]->mail;
                $title =  $message->getSubject()->toArray()[0] ?? '';
                $body =  $message->hasHtmlBody() ?  $message->getHtmlBody() :  $message->getTextBody();     

                if(Input::get("action") == "re"){

                    $form->getField('to')->value =  iconv('utf-8', 'utf-8//IGNORE', $to);
                    $form->getField('title')->value = "Re: ". iconv('utf-8', 'utf-8//IGNORE', $title);
                    $form->getField('body')->value = "<br>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>><br>". iconv('utf-8', 'utf-8//IGNORE', $body);

                }
    
            }

            if(Input::get("action") == "to" && Input::filled("email"))
                $form->getField('to')->value = Input::get("email");

            if(Input::get("action") == "to" && Input::filled("title"))
                $form->getField('title')->value = Input::get("title");


        }


    }

    public function onUpload()
    {


    }

    public function onGoogle()
    {

        Google::sendFile();

    }

}