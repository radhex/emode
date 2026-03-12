<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use BackendAuth;
use Backend\Controllers\Files;
use Redirect;
use Input;
use Eprog\Manager\Classes\Util;
use Eprog\Manager\Classes\Google;
use Eprog\Manager\Models\Scheduler as ModelScheduler;
use Eprog\Manager\Classes\Ksef;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Support\Str;
use View;


class Scheduler extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = ['eprog.manager.access_scheduler'];

    public function __construct()
    {

        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'scheduler', 'events');


    }

    public function getDownloadUrl($file)
    {

        return Files::getDownloadUrl($file);
    
    }

    public function listExtendQuery($query, $definition = null)
    {
   
     	if(!BackendAuth::getUser()->hasAccess('eprog.manager.manage_scheduler'))
    	$query->where('user_id','=', BackendAuth::getUser()->id);

      	$query->orderBy("id", "desc");	

    }

    public function formExtendQuery($query, $definition = null)
    {
    
     	if(!BackendAuth::getUser()->hasAccess('eprog.manager.manage_scheduler'))
    	$query->where('user_id','=', BackendAuth::getUser()->id);

    }

    public function formExtendFields($form)
    {

        Util::checkExpired();
        Util::checkCapacity();
        
        if(Input::segment(5) == "create") {

            if(Input::filled("start"))
            $form->getField("start")->value = Input::get("start");

            if(Input::filled("stop"))
            $form->getField("stop")->value = Input::get("stop");

        } 
    }

    public function onPdf()
    {
        $file = self::onPdfGenerate(Input::segment(6));
        if(file_exists($file)) return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getfile?file='.urlencode($file));
    }

    public static function onPdfGenerate($id)
    {
        View::addLocation('plugins/eprog/manager/controllers/printer');
        $scheduler = ModelScheduler::find($id);  
        if($scheduler){   
            $file = storage_path('temp/public/'.trans('eprog.manager::lang.scheduler').'('.$scheduler->id.') - '.str_replace("/","_",Str::slug($scheduler->name)).'.pdf');
            if(file_exists($file)) unlink($file);            
            $pdf = SnappyPdf::loadView('scheduler', compact('id'));
            $pdf->save($file);
            return $file;      
        }
        else
            Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));
            
    }


    public function onExport()
    {

        $url = config('cms.backendUri')."/eprog/manager/export/schedulerxml";
        if(isset($_POST['checked'])) $url .= "?checked=".implode(",",$_POST['checked']);
        return Redirect::to($url);

    }

    public function onGoogle()
    {

        Google::sendFile();

    }

}