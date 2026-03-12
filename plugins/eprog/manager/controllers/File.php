<?php namespace Eprog\Manager\Controllers;

use October\Rain\Exception\ValidationException;
use Eprog\Manager\Models\File as ModelFile;
use Backend\Classes\Controller;
use Eprog\Manager\Models\SettingConfig;
use System\Models\File as SystemFile;
use BackendMenu;
use Redirect;
use Session;
use Response;
use Flash;
use Mpdf\Mpdf;
use Input;
use Lang;

class File extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = ['eprog.manager.manage_accounting'];

    public function __construct(){

        parent::__construct();
        BackendMenu::setContext('Eprog.Manager',  'file');
        
    }

    public function listExtendQuery($query, $definition = null) {
    

        $query->orderBy("year", "desc")->orderBy("month", "desc");
    }

    public function formExtendFields($form)
    {

        if(!isset($_SESSION)) session_start();
    



        if(Input::segment(5) == "create") { 
            
            $year = Session::has("selected.year") ? Session::get("selected.year") : date("Y",time());
            $month = Session::has("selected.month") ? Session::get("selected.month") : date("m",time());

            $form->getField('year')->value =  $year;
            $form->getField('month')->value =  $month ; 

        }

        if(Input::segment(5) == "update") { 
            

 

        }


    }

    public function download()
    {

            $file = SystemFile::find(Input::get("file_id"));
            if($file && $file->exists())
               return Response::download($file->getLocalPath(),$file->file_name);
            else
               return Redirect::back();
      
    }


    public function onExport()
    {

        $url = config('cms.backendUri')."/eprog/manager/export/filexml";
        $arg = "";
        if(isset($_POST['checked'])) $arg .= "&checked=".implode(",",$_POST['checked']);
        if(isset($_POST['nip'])) $arg .= "&nip=".$_POST['nip'];
        if(strlen($arg) > 0) $arg[0] = "?";
        return Redirect::to($url.$arg);
    }

    public static function saveFile($file, $year, $month , $type = "record")
    {

            $nip = Session::get("selected.nip");

            $modelfile = ModelFile::where("year",$year)
                        ->where("month",$month)
                        ->first();
       
            if (!$modelfile) {
                $modelfile = new ModelFile();
                $modelfile->year = $year;
                $modelfile->month = $month;
                $modelfile->save();
            }

            if($modelfile){
                $attachfile = new SystemFile();
                $attachfile->fromFile($file);
                $attachfile->save();

                if($type == "document") $modelfile->document()->add($attachfile);
                if($type == "image") $modelfile->image()->add($attachfile);
                if($type == "media") $modelfile->media()->add($attachfile);

                $targetPath = $attachfile->getLocalPath();
                $dir = dirname($targetPath);
                if (!file_exists($dir)) {
                    mkdir($dir, 0775, true);
                }

                copy($file, $targetPath);
                unlink($file);
                return true;
   
            }
      
    }

}