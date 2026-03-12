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
use Eprog\Manager\Models\Serwis as ModelSerwis;
use Eprog\Manager\Models\Work;

class Serwis extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Serwis',
            'description' => 'Opis serwisu'
        ];
    }




    /**
     * Executed when this component is bound to a page or layout.
     */
     public function onRun()
     {


	    if(Input::segment(2)) {
	    	$serwis = ModelSerwis::where("user_id","=", Auth::getUser()->id)->where("id","=", Input::segment(2))->where("public","=", 1)->get();	
		if(isset($serwis[0]))  $this->page['modelserwis'] = $serwis[0];
		if(Input::segment(3)){
			$work = Work::join('eprog_manager_serwis', 'eprog_manager_serwis.id', '=', 'eprog_manager_work.serwis_id')->where("eprog_manager_serwis.user_id","=", Auth::getUser()->id)->where("eprog_manager_work.id","=", Input::segment(3))->where("eprog_manager_serwis.public","=", 1)->where("eprog_manager_work.public","=", 1)->select('eprog_manager_work.*')->get();	
			if(isset($work[0]))  $this->page['work'] = $work[0];
		}
		else {
			if(isset($serwis[0]))
			$this->page['work'] = Work::where("serwis_id", "=", Input::segment(2))->where("public","=", 1)->OrderBy("id","desc")->paginate(25);
			
		}
	    }
	    else
	    $this->page['modelserwis'] = ModelSerwis::where("user_id","=", Auth::getUser()->id)->where("public","=", 1)->OrderBy("id","desc")->paginate(25);	
  
	
     }
  
}
