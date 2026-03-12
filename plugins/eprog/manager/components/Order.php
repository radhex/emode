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
use Eprog\Manager\Models\Order as ModelOrder;
use Eprog\Manager\Models\Mail as ModelMail;
use Carbon\Carbon;

class Order extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Order',
            'description' => 'Obsluga zamowien'
        ];
    }




    /**
     * Executed when this component is bound to a page or layout.
     */
     public function onRun()
     {

	    if(Input::segment(2)) {
		      $order = ModelOrder::where("user_id", "=", Auth::getUser()->id)->where("id", "=", Input::segment(2))->where("disp","=", 1)->paginate(config('app.paginate.frontend'));
		      if(isset($order[0])) $this->page['order'] = $order[0]; else $this->page['order'] = null;
		
	    }
	    else {	
    		
  	    	$order = ModelOrder::where("user_id", "=", Auth::getUser()->id)->OrderBy("id","desc")->where("disp","=", 1)->paginate(config('app.paginate.frontend'));
		      $this->page['order'] = $order;
	    }
	
     }



}
