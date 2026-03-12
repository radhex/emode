<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use Eprog\Manager\Models\SettingConfig;
use Eprog\Manager\Controllers\Taxfile;
use Rainlab\User\Models\User;
use BackendMenu;
use Redirect;
use Input;
use Session;
use Lang;
use Eprog\Manager\Models\Jpk as ModelJpk;
use Eprog\Manager\Classes\Jpk as ClassJpk;
use Eprog\Manager\Classes\Util;
use October\Rain\Exception\ValidationException;
use Barryvdh\Snappy\Facades\SnappyPdf;
use System\Models\File;
use Carbon\Carbon;
use Flash;
use DB;

class Jpk extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = ['eprog.manager.manage_accounting'];

    public function __construct(){

        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'accounting', 'jpk');
        
    }

    public function listExtendQuery($query, $definition = null)
    {
      
         $nip = Session::has("selected.nip") ? Session::get("selected.nip") : SettingConfig::get("nip"); 

         if($nip)
             $query->where("nip", $nip);  
         else 
             $query->where("id",0); 

         $query->orderBy("id", "desc"); 

    }


    public function formExtendFields($form)
    {
        Util::checkExpired();
        Util::checkCapacity();
        
        if(Input::segment(5) == "create") { 

            $nip = Session::get("selected.nip") ?? '';
            $firm = User::where("firm_nip", $nip )->first() ?? '';
            if(!$firm && $nip != SettingConfig::get("nip"))  die('Bad NIP');

            $form->getField('name')->value = Session::get("jpk.".$nip.".name");
            $form->getField('version')->value = Session::get("jpk.".$nip.".version");
            $form->getField('period')->value = Session::get("jpk.".$nip.".period");
            $form->getField('xml')->value = Session::get("jpk.".$nip.".xml");

            $form->getField('jpk_at')->hidden = true;
            $form->getField('created_at')->hidden = true;
        }

        if(Input::segment(5) == "update") { 

            if($form->getField('jpk_at')->value == "")
            $form->getField('jpk_at')->hidden = true;
        }
    }


    public function onExport()
    {

        $url = config('cms.backendUri')."/eprog/manager/export/jpkxml";
        $arg = "";
        if(isset($_POST['checked'])) $arg .= "&checked=".implode(",",$_POST['checked']);
        if(isset($_POST['nip'])) $arg .= "&nip=".$_POST['nip'];
        if(strlen($arg) > 0) $arg[0] = "?";
        return Redirect::to($url.$arg);

    }

    public function onJpkSend()
    {

        $jpk = ModelJpk::find(Input::segment(6)); 
        $xml = ClassJpk::verifyJpkXml(Util::escapeXml($jpk->xml),$jpk->version);  
        $classjpk = new ClassJpk();
        $ref = $classjpk->jpkSend($xml,$jpk->version);

        if($ref){        
            if(Input::segment(6) > 0)
            DB::update("update  eprog_manager_jpk set jpk_at = '".Carbon::now()."', referenceNumber = '".$ref."' where id = '".Input::segment(6)."'");
            return $ref;
        }           
        else
            throw new ValidationException(['my_field'=>e(trans('eprog.manager::lang.ksef.send_error'))]);
        
    
    }

    public function onJpkUpo()
    {

        $jpk = ModelJpk::find(Input::segment(6)); 
        $xml = ClassJpk::verifyJpkXml(Util::escapeXml($jpk->xml),$jpk->version);  
        $classjpk = new ClassJpk();
        $upo = $classjpk->getUpo($jpk->referenceNumber);
   

        if(isset($upo['Code'])){
            if($upo['Code'] == "200"){        
                $html= $classjpk->getUpoHtml($upo['Upo']);
                $filename = "UPO_".str_replace("(","",str_replace(" ","_",str_replace(")","",$jpk->version)))."_".str_replace("/","_",$jpk->period).".pdf";
                $file = storage_path('temp/public/'.$filename);
                $pdf = SnappyPdf::loadHTML($html)->output(); 
                file_put_contents($file, $pdf);  
                if(file_exists($file))
                return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getfile?file='.urlencode($file));   
            }
            else
                throw new ValidationException(['my_field'=>$upo['Description']]);
        }  
        else
            throw new ValidationException(['my_field'=>e(trans('eprog.manager::lang.ksef.send_error'))]);
        
    }

    public static function onJpkFileUpo($id)
    {

        $jpk = ModelJpk::find($id); 
        $xml = ClassJpk::verifyJpkXml(Util::escapeXml($jpk->xml),$jpk->version);  
        $classjpk = new ClassJpk();
        $upo = $classjpk->getUpo($jpk->referenceNumber);
        if(isset($upo['Code'])){
            if($upo['Code'] == "200"){        
                $html= $classjpk->getUpoHtml($upo['Upo']);
                $filename = "UPO_".str_replace("(","",str_replace(" ","_",str_replace(")","",$jpk->version)))."_".str_replace("/","_",$jpk->period).".pdf";
                $file = storage_path('temp/public/'.$filename);
                $pdf = SnappyPdf::loadHTML($html)->output(); 
                file_put_contents($file, $pdf);  
                return $file;  
            }
            else
                throw new ValidationException(['my_field'=>$upo['Description']]);
        }  
        else
            throw new ValidationException(['my_field'=>e(trans('eprog.manager::lang.ksef.send_error'))]);
        
    }



    public function onJpkXml()
    {

        $jpk = ModelJpk::find(Input::segment(6)); 
        if($jpk->xml){   
            $filename = str_replace("(","",str_replace(" ","_",str_replace(")","",$jpk->version)))."_".str_replace("/","_",$jpk->period).".xml";
            $file = storage_path('temp/public/'.$filename);
            file_put_contents($file, $jpk->xml);  
            if(file_exists($file))
            return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getfile?file='.urlencode($file));   
        }
        else
            Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));

    }

    public static function onJpkFile($id)
    {

        $jpk = ModelJpk::find($id); 
        if($jpk->xml){           
            $filename = str_replace("(","",str_replace(" ","_",str_replace(")","",$jpk->version)))."_".str_replace("/","_",$jpk->period).".xml";
            $file = storage_path('temp/public/'.$filename);
            file_put_contents($file,$jpk->xml);  
            return $file;
        }

    }


}