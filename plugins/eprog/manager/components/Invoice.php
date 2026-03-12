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
use Eprog\Manager\Models\Invoice as ModelInvoice;
use Eprog\Manager\Models\Mail as ModelMail;
use Carbon\Carbon;

class Invoice extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Invoice',
            'description' => 'Obsluga faktur'
        ];
    }




    /**
     * Executed when this component is bound to a page or layout.
     */
     public function onRun()
     {

	    if(Input::segment(2)) {
		
            $invoice = ModelInvoice::where("user_id", "=", Auth::getUser()->id)->where("id", "=", Input::segment(2))->where("disp","=", 1)->paginate(config('app.paginate.frontend'));
		    if(isset($invoice [0])) $this->page['invoice'] = $invoice[0]; else $this->page['invoice'] = null;
		
	    }
	    else {	
    		
  	    	$invoice = ModelInvoice::where("user_id", "=", Auth::getUser()->id)->OrderBy("id","desc")->where("disp","=", 1)->paginate(config('app.paginate.frontend'));
		    $this->page['invoice'] = $invoice;
	    }
	
     }



}
