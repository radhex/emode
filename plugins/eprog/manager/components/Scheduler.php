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
use Eprog\Manager\Models\Scheduler as ModelScheduler;
use Eprog\Manager\Models\Mail as ModelMail;
use Carbon\Carbon;

class Scheduler extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Scheduler',
            'description' => 'Obsluga terminarza'
        ];
    }




    /**
     * Executed when this component is bound to a page or layout.
     */
     public function onRun()
     {

	    if(Input::segment(3)) {

		if(Input::segment(2) == "before")
		$scheduler = ModelScheduler::where("user_id", "=", Auth::getUser()->id)->where("start", ">=", Carbon::now())->where("id", "=", Input::segment(3))->where("disp","=", 1)->paginate(config('app.paginate.frontend'));	
		if(Input::segment(2) == "after")
		$scheduler = ModelScheduler::where("user_id", "=", Auth::getUser()->id)->where("start", "<", Carbon::now())->where("id", "=", Input::segment(3))->where("disp","=", 1)->paginate(config('app.paginate.frontend'));	

		if(isset($scheduler[0])) $this->page['scheduler'] = $scheduler[0]; else $this->page['scheduler'] = null;
		
	    }
	    else {	
    		
		
  	    	$scheduler = ModelScheduler::where("user_id", "=", Auth::getUser()->id);
		if(Input::segment(2) == "before") $scheduler = $scheduler->where("start", ">=", Carbon::now())->OrderBy("start","asc");
		if(Input::segment(2) == "after") $scheduler = $scheduler->where("start", "<", Carbon::now())->OrderBy("start","desc");

		$this->page['scheduler'] = $scheduler->where("disp","=", 1)->paginate(config('app.paginate.frontend'));	
	    }
	
     }



}
