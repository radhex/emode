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

class Project extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Project',
            'description' => 'Opis projectu'
        ];
    }




    /**
     * Executed when this component is bound to a page or layout.
     */
     public function onRun()
     {


	    if(Input::segment(2)) {
	    	$project = ModelProject::where("user_id","=", Auth::getUser()->id)->where("id","=", Input::segment(2))->where("public","=", 1)->get();	
		if(isset($project[0]))  $this->page['modelproject'] = $project[0];
		if(Input::segment(3)){
			$work = Work::join('eprog_manager_project', 'eprog_manager_project.id', '=', 'eprog_manager_work.project_id')->where("eprog_manager_project.user_id","=", Auth::getUser()->id)->where("eprog_manager_work.id","=", Input::segment(3))->where("eprog_manager_project.public","=", 1)->where("eprog_manager_work.public","=", 1)->select('eprog_manager_work.*')->paginate(config('app.paginate.frontend'));	
			if(isset($work[0]))  $this->page['work'] = $work[0];
		}
		else {
			if(isset($project[0]))
			$this->page['work'] = Work::where("project_id", "=", Input::segment(2))->where("public","=", 1)->OrderBy("id","desc")->paginate(config('app.paginate.frontend'));
			
		}
	    }
	    else
	    $this->page['modelproject'] = ModelProject::where("user_id","=", Auth::getUser()->id)->where("public","=", 1)->OrderBy("id","desc")->paginate(config('app.paginate.frontend'));	
  
	
     }
  
}
