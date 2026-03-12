<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Input;
use BackendAuth;
use Eprog\Manager\Models\Scheduler;
use Eprog\Manager\Models\Project;
use Eprog\Manager\Models\Product;
use Eprog\Manager\Models\Work;
use Eprog\Manager\Models\Mailing;
use Eprog\Manager\Models\Producent;
use Eprog\Manager\Models\Type;
use Eprog\Manager\Models\Unit;
use Eprog\Manager\Models\Category;
use Eprog\Manager\Models\Attribute;
use Eprog\Manager\Models\Mail as ModelMail;
use Eprog\Manager\Models\Inmail;
use Eprog\Manager\Models\Accounting;
use Eprog\Manager\Models\Jpk;
use Eprog\Manager\Models\Fixed;
use Eprog\Manager\Models\Internal;
use Eprog\Manager\Classes\Util;
use Rainlab\User\Models\User;
use Auth;
use Artisan;
use Carbon\Carbon;
use Eprog\Manager\Models\SettingConfig as Settings;
use Eprog\Manager\Models\Invoice;
use Eprog\Manager\Models\Invoicevalue;
use Eprog\Manager\Models\Order;
use Eprog\Manager\Models\Ordervalue;
use Illuminate\Support\Facades\DB;
use Mail;
use Config;
use Redirect;


class Export extends Controller
{

    public static function getAfterFilters() {return [];}
    public static function getBeforeFilters() {return [];}


    public function callAction($method, $parameters=false) {
        return call_user_func_array(array($this, $method), $parameters);
    }


    public function __construct()
    {
 
     
    }

    public function index()
    {

    }


    public function invoicexml()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "Invoice";   
        $modelname = "Invoicevalue";  

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Eprog\\Manager\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0)
        $model  = $model->wherein("id",$checked);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model, $modelname);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();

    }

    public function orderxml()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "Order";   
        $modelname = "Ordervalue";   

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Eprog\\Manager\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0)
        $model  = $model->wherein("id",$checked);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model, $modelname);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();

    }

    public function productxml()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "Product";   

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Eprog\\Manager\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0)
        $model  = $model->wherein("id",$checked);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();

    }

    public function categoryxml()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "category";   

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Eprog\\Manager\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0)
        $model  = $model->wherein("id",$checked);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();

    }

    public function producentxml()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "Producent";  

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Eprog\\Manager\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0)
        $model  = $model->wherein("id",$checked);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();

    }

    public function attributexml()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "Attribute";   

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Eprog\\Manager\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0)
        $model  = $model->wherein("id",$checked);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();

    }

    public function unitxml()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "Unit";   

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Eprog\\Manager\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0)
        $model  = $model->wherein("id",$checked);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();

    }

    public function schedulerxml()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "Scheduler";   

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Eprog\\Manager\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0)
        $model  = $model->wherein("id",$checked);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();
    }

    public function typexml()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "Type";   

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Eprog\\Manager\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0)
        $model  = $model->wherein("id",$checked);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();

    }

    public function projectxml()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "Project";    

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Eprog\\Manager\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0)
        $model  = $model->wherein("id",$checked);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();

    }

    public function workxml()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "Work";    

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Eprog\\Manager\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0)
        $model  = $model->wherein("id",$checked);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();

    }

    public function userxml()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "User";    

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Rainlab\\User\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0)
        $model  = $model->wherein("id",$checked);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();

    }

    public function mailingxml()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "Mailing";    

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Eprog\\Manager\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0)
        $model  = $model->wherein("id",$checked);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();

    }


    public function mailxml()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "Mail";    

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Eprog\\Manager\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0)
        $model  = $model->wherein("id",$checked);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();

    }

    public function inmailxml()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "Inmail";    

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Eprog\\Manager\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0)
        $model  = $model->wherein("id",$checked);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();

    }

    public function accountingxml()
    {


        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "Accounting";    

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Eprog\\Manager\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0) $model  = $model->wherein("id",$checked);
        if(isset($_GET['year'])) $model  = $model->where("year",$_GET['year']);
        if(isset($_GET['month'])) $model  = $model->where("month",$_GET['month']);
        if(isset($_GET['nip'])) $model  = $model->where("nip",$_GET['nip']);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();

    }

    public function jpkxml()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "Jpk";    

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Eprog\\Manager\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0)
        $model  = $model->wherein("id",$checked);
        if(isset($_GET['nip'])) $model  = $model->where("nip",$_GET['nip']);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();

    }

    public function internalxml()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "Internal";   
        $modelname = "Internalvalue";   

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Eprog\\Manager\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0)
        $model  = $model->wherein("id",$checked);
        if(isset($_GET['nip'])) $model  = $model->where("nip",$_GET['nip']);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model, $modelname);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();

    }

    public function fixedxml()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "Fixed";    

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Eprog\\Manager\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0)
        $model  = $model->wherein("id",$checked);
        if(isset($_GET['nip'])) $model  = $model->where("nip",$_GET['nip']);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();

    }

    public function payrollxml()
    {


        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "Payroll";    

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Eprog\\Manager\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0) $model  = $model->wherein("id",$checked);
        if(isset($_GET['year'])) $model  = $model->where("year",$_GET['year']);
        if(isset($_GET['month'])) $model  = $model->where("month",$_GET['month']);
        if(isset($_GET['nip'])) $model  = $model->where("nip",$_GET['nip']);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();

    }

    public function advancexml()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "Advance";    

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Eprog\\Manager\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0)
        $model  = $model->wherein("id",$checked);
        if(isset($_GET['nip'])) $model  = $model->where("nip",$_GET['nip']);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();

    }

    public function zusxml()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "Zus";    

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Eprog\\Manager\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0)
        $model  = $model->wherein("id",$checked);
        if(isset($_GET['nip'])) $model  = $model->where("nip",$_GET['nip']);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();

    }

    public function workerxml()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;

        $name = "Worker";    

        if(isset($_GET['checked'])) $checked = explode(",",$_GET['checked']); else $checked = null;
        $modelClass = "Eprog\\Manager\\Models\\{$name}";
        $model = $modelClass::where("id", ">", 0);
        if(is_array($checked) && sizeof($checked) > 0)
        $model  = $model->wherein("id",$checked);
        if(isset($_GET['nip'])) $model  = $model->where("nip",$_GET['nip']);
        $model  = $model->get();

        $xmlDoc = self::prepare_xml($name, $model);

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="emode_'.strtolower($name).'.xml"');

        echo $xmlDoc->saveXML();

    }


    private function prepare_xml($name,$model, $modelname = null)
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_xml')) return;
        
        $table = 'eprog_manager_'.strtolower($name);
        if($name == "User") $table = "users";
        $export = [];
        $export[$name."s"] = [];

        $l = 0;
        foreach ($model as $mod) {
            $export[$name."s"][$l] = [];
            foreach(DB::getSchemaBuilder()->getColumnListing($table) as $field)                                
            $export[$name."s"][$l][$field] = $mod->{$field};   
            $l++;
        }

        $xmlDoc = new \DOMDocument();
        $root = $xmlDoc->appendChild($xmlDoc->createElement($name."s"));

        if(isset($export[$name."s"]) && is_array($export[$name."s"])){
            foreach ($export[$name."s"] as $exp) {
                $node = $root->appendChild($xmlDoc->createElement($name));                
                foreach(DB::getSchemaBuilder()->getColumnListing($table) as $field){
                    $val = $exp[$field];     
                    if(is_array($val)) $val = json_encode($val);
                    $node->appendChild($xmlDoc->createElement($field,htmlspecialchars($val, ENT_XML1, 'UTF-8')));
                }
                if($modelname){
                    $values  = $node->appendChild($xmlDoc->createElement($modelname.'s'));
                    $modelClass = "Eprog\\Manager\\Models\\{$modelname}";
                    $modelvalues = $modelClass::where(strtolower($name)."_id", "=", $exp["id"])->get();
                    foreach ($modelvalues as $modelvalue) {
                        $value = $values->appendChild($xmlDoc->createElement($modelname));
                        foreach(DB::getSchemaBuilder()->getColumnListing('eprog_manager_'.strtolower($modelname)) as $field)
                        $value->appendChild($xmlDoc->createElement($field, $modelvalue->{$field}));
                    }
                }

            }
        }

        return $xmlDoc;

    }

}