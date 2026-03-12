<?php namespace Eprog\Manager\Components;

use Lang;
use Auth;
use Mail;
use Event;
use Flash;
use Input;
use Request;
use Redirect;
use Validator;
use ValidationException;
use ApplicationException;
use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use RainLab\User\Models\Settings as UserSettings;
use Exception;
use Eprog\Manager\Models\Project as ModelProject;
use Eprog\Manager\Models\Work;
use Eprog\Manager\Models\Mail as ModelMail;
use Carbon\Carbon;

class UserMail extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Mail',
            'description' => 'Obsluga Wiadomosci'
        ];
    }




    /**
     * Executed when this component is bound to a page or layout.
     */
     public function onRun()
     {

	
	
	    if(Input::segment(3)) {

			if(Input::segment(3) != "create") {
				$mail = ModelMail::where("user_id", "=", Auth::getUser()->id)->where("id", "=", Input::segment(3))->get();

				if(isset($mail[0])){

					$mail = $mail[0];
					if(Input::segment(2) == "inbox") 	
					$mail->update(["read" => 1]);
	    				$this->page['mail'] = $mail;	
				}
			}	
	
	    }
	    else {	
    			if(Input::segment(2) == "inbox") 
  	    		$this->page['mails'] = ModelMail::where("user_id", "=", Auth::getUser()->id)->where("send", "=", 1)->OrderBy("id","desc")->paginate(config('app.paginate.frontend'));

				if(Input::segment(2) == "outbox") 
  	    		$this->page['mails'] = ModelMail::where("user_id", "=", Auth::getUser()->id)->where("send", "=", 0)->OrderBy("id","desc")->paginate(config('app.paginate.frontend'));
	    }
	
     }


     public function onCreate()
     {

        $data = post();
            $rules = [
                'name'    => 'required',
                'desc' => 'required'
            ];

            $customMessages = [

                'name.required' => e(trans('eprog.manager::lang.valid_title')),
                'desc.required' => e(trans('eprog.manager::lang.valid_body'))
              
            ];


            $validation = Validator::make($data, $rules, $customMessages);
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }

	    	$data = [ 
				"user_id" => Auth::getUser()->id ,
				"name" => post("name"),
				"desc" =>post("desc"),
			    "date"=> Carbon::now()
		    ];

	    	$mail = ModelMail::create($data);
	
		
  
		//Flash::success(trans('eprog.manager::lang.mail_send'));

		return Redirect::to("/mail/outbox/".$mail->id."?send=1");

     }
}
