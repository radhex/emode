<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Input;
use BackendAuth;
use Eprog\Manager\Models\Scheduler;
use Eprog\Manager\Models\Project;
use Eprog\Manager\Models\Product;
use Eprog\Manager\Models\Work;
use Eprog\Manager\Models\Category;
use Eprog\Manager\Models\Producent;
use Eprog\Manager\Models\Type;
use Eprog\Manager\Classes\Util;
use Rainlab\User\Models\User;
use Auth;
use Artisan;
use Carbon\Carbon;
use Eprog\Manager\Models\SettingConfig as Settings;
use Eprog\Manager\Models\Invoice;
use Eprog\Manager\Models\Invoicevalue;
use Illuminate\Support\Facades\DB;
use Mail;
use Config;
use Redirect;
use Request;


class Import extends Controller
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

    public function invoice()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;

        $config = ["model" => "Eprog\Manager\Models\Invoice", "modelvalue" => "Eprog\Manager\Models\Invoicevalue", "key" => "Invoice", "keyvalue" => "Invoicevalue", "keys" => "Invoices", "keyvalues" => "Invoicevalues"];
        self::form($config);

    }

    public function order()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;

        $config = ["model" => "Eprog\Manager\Models\Order", "modelvalue" => "Eprog\Manager\Models\Ordervalue", "key" => "Order", "keyvalue" => "Ordervalue", "keys" => "Orders", "keyvalues" => "Ordervalues"];
        self::form($config);

    }

    public function product()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;

        $config = ["model" => "Eprog\Manager\Models\Product", "modelvalue" => "", "key" => "Product", "keyvalue" => "", "keys" => "Products", "keyvalues" => ""];
        self::form($config);

    }

    public function category()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;

        $config = ["model" => "Eprog\Manager\Models\Category", "modelvalue" => "", "key" => "Category", "keyvalue" => "", "keys" => "Categories", "keyvalues" => ""];
        self::form($config);

    }

    public function producent()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;

        $config = ["model" => "Eprog\Manager\Models\Producent", "modelvalue" => "", "key" => "Producent", "keyvalue" => "", "keys" => "Producents", "keyvalues" => ""];
        self::form($config);

    }

    public function attribute()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;

        $config = ["model" => "Eprog\Manager\Models\Attribute", "modelvalue" => "", "key" => "Attribute", "keyvalue" => "", "keys" => "Attributes", "keyvalues" => ""];
        self::form($config);

    }

    public function unit()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;

        $config = ["model" => "Eprog\Manager\Models\Unit", "modelvalue" => "", "key" => "Unit", "keyvalue" => "", "keys" => "Units", "keyvalues" => ""];
        self::form($config);

    }

    public function user()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;

        $config = ["model" => "RainLab\User\Models\User", "modelvalue" => "", "key" => "User", "keyvalue" => "", "keys" => "Users", "keyvalues" => ""];
        self::form($config);

    }

    public function scheduler()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;

        $config = ["model" => "Eprog\Manager\Models\Scheduler", "modelvalue" => "", "key" => "Scheduler", "keyvalue" => "", "keys" => "Schedulers", "keyvalues" => ""];
        self::form($config);

    }

    public function type()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;

        $config = ["model" => "Eprog\Manager\Models\Type", "modelvalue" => "", "key" => "Type", "keyvalue" => "", "keys" => "Types", "keyvalues" => ""];
        self::form($config);

    }

    public function project()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;

        $config = ["model" => "Eprog\Manager\Models\Project", "modelvalue" => "", "key" => "Project", "keyvalue" => "", "keys" => "Projects", "keyvalues" => ""];
        self::form($config);

    }

    public function work()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;

        $config = ["model" => "Eprog\Manager\Models\Work", "modelvalue" => "", "key" => "Work", "keyvalue" => "", "keys" => "Works", "keyvalues" => ""];
        self::form($config);

    }

    public function mailing()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;

        $config = ["model" => "Eprog\Manager\Models\Mailing", "modelvalue" => "", "key" => "Mailing", "keyvalue" => "", "keys" => "Mailings", "keyvalues" => ""];
        self::form($config);

    }

    public function mail()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;

        $config = ["model" => "Eprog\Manager\Models\Mail", "modelvalue" => "", "key" => "Mail", "keyvalue" => "", "keys" => "Mails", "keyvalues" => ""];
        self::form($config);

    }

    public function inmail()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;

        $config = ["model" => "Eprog\Manager\Models\Inmail", "modelvalue" => "", "key" => "Inmail", "keyvalue" => "", "keys" => "Inmails", "keyvalues" => ""];
        self::form($config);

    }


    public function accounting()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;

        $config = ["model" => "Eprog\Manager\Models\Accounting", "modelvalue" => "", "key" => "Accounting", "keyvalue" => "", "keys" => "Accountings", "keyvalues" => ""];
        self::form($config);

    }

    public function jpk()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;

        $config = ["model" => "Eprog\Manager\Models\Jpk", "modelvalue" => "", "key" => "Jpk", "keyvalue" => "", "keys" => "Jpks", "keyvalues" => ""];
        self::form($config);

    }

    public function fixed()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;

        $config = ["model" => "Eprog\Manager\Models\Fixed", "modelvalue" => "", "key" => "Fixed", "keyvalue" => "", "keys" => "Fixeds", "keyvalues" => ""];
        self::form($config);

    }

    public function internal()
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;

        $config = ["model" => "Eprog\Manager\Models\Internal", "modelvalue" => "Eprog\Manager\Models\Internalvalue", "key" => "Internal", "keyvalue" => "Internalvalue", "keys" => "Internals", "keyvalues" => "Internalvalues"];
        self::form($config);

    }


    public function form($config)
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;

        include 'plugins/eprog/manager/views/modal/header.php';
        echo "<center><br><br>";

        $type = ""; $raport = "";
        if($_POST && $_FILES){
            $type = $_FILES['file']['type'];
            if($type != "text/xml" && $type != "") $raport = trans('eprog.manager::lang.file_unexpected_format');
            else {
                $data = file_get_contents($_FILES['file']['tmp_name']);
                if($data){
                    $raport = self::modelinsert($data, $_POST['type'] ?? 1, $config);

                    if(is_array($raport)){  
                        echo "<span style=\"color:green\">";        
                        echo trans('eprog.manager::lang.import_done')."<br>";
                        echo "<span style=\"font-size:12px\">";       
                        //echo trans('eprog.manager::lang.modify').": ".$raport[0]." ".trans('eprog.manager::lang.positions')."<br>";     
                        echo trans('eprog.manager::lang.added_positions').": ".$raport[1]."<br>"; 
                        echo "</span></span>"; 
                        echo "<br><br><button class=\"btn btn-primary btn-xl\" onclick=\"parent.window.modal.dialog('close')\">".trans('eprog.manager::lang.close_window')."</button>";                
                    }

                }
            }   
         }   


        if($type != "text/xml" || $type == "" || !is_array($raport)) {
          
            echo '<form  method="post"  action="" enctype="multipart/form-data">';
            echo '<input type="file" class="btn btn-secondary" name="file" style="border:0;height:40px" required>';
            //echo '<input type="radio" name="type" value="1" checked> '.trans('eprog.manager::lang.import_add');
            //echo '<input type="radio" name="type" value="2" style="margin-left:20px" '.($config['key'] == "User" ? 'disabled' : '').'> '.trans('eprog.manager::lang.import_override');
            echo '<input type="hidden" name="_token" value="'.csrf_token().'" />';
            echo '<br><br><button type="submit" class="btn btn-primary btn-xl">'.trans('eprog.manager::lang.import_send').'</button>';
            echo '</form>';

            if(!is_array($raport) && $raport != "")
            echo "<br><span style=\"color:red;\">".$raport."<br><br></span>";

        }

    }

    public function config($model)
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;
        
        $config = [];
        $model = explode("\\",$model);
        $name = $model[sizeof($model)-1];
        $table = "eprog_manager_".strtolower($name);
        if($name == "User") $table = "users";
        $columns = DB::select("SHOW COLUMNS FROM `$table`");
        foreach ($columns as $column) {
            $config[$column->Field] = $column->Type;  
        }
        return $config;
    }


    public function modelinsert($data, $type, $config)
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;

        $zm = "0";


        $isXML = Util::isXML($data);
        if($isXML != "") return trans('eprog.manager::lang.xml_bad_format')."<br><i style=\"font-size:12px\">".$isXML."</i>";

        $xml = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $tabs = json_decode($json, true);

        if(!isset($tabs[$config["key"]])) return trans('eprog.manager::lang.xml_bad_structure')."<br><i>".(array_keys($tabs)[0] ?? '')."</>"; 

        $raport = [0,0];

        if(!isset($tabs[$config["key"]][0])){
                $tmp = $tabs[$config["key"]];
                $tabs[$config["key"]] = [];
                $tabs[$config["key"]][0] = $tmp; 
        }

        $checkmodel = ""; $checkrow = []; $msgrow = "";
        foreach($tabs[$config["key"]] as $key1 => $tab) {
        $datavalue = [];
            foreach($tab as $key2 => $tb){
                    if(is_array($tb)) {
                        if($key2 == $config["keyvalues"]){
                            if(isset($tb[$config["keyvalue"]]) && is_array($tb[$config["keyvalue"]])){
                                if(!isset($tb[$config["keyvalue"]][0])){
                                        $tmp = $tb[$config["keyvalue"]];
                                        $tb[$config["keyvalue"]] = [];
                                        $tb[$config["keyvalue"]][0] = $tmp; 
                                }
                                foreach($tb[$config["keyvalue"]] as $keyvalue){
                                    foreach($keyvalue as $key3 => $t){
                                        if(is_array($t) && sizeof($t) == 0);                            
                                        else {
                                            if(isset(self::config($config["modelvalue"])[$key3]))
                                            $checkmodel .= self::check_model($key3,$t,self::config($config["modelvalue"])[$key3]); 

                                            if(!isset(self::config($config["modelvalue"])[$key3]))
                                            $checkrow [$key3] = trans('eprog.manager::lang.invalid_row')." (".$key3.")";                                                                                                 
                                        }
                                    }
                                }
                            }

                        }
                    }
                    else{
                        if(isset(self::config($config["model"])[$key2]))
                        $checkmodel .= self::check_model($key2,$tb,self::config($config["model"])[$key2]);

                        if(!isset(self::config($config["model"])[$key2]))
                        $checkrow [$key2] = trans('eprog.manager::lang.invalid_row')." (".$key2.")";      
                    }
                             
           }

        }


        if($checkmodel != "")  
                return trans('eprog.manager::lang.xml_bad_structure')."<br><i>".$checkmodel."</>"; 

        if(sizeof($checkrow) > 0){
                foreach($checkrow as $key => $val)
                $msgrow .= $val."<br>";
                return trans('eprog.manager::lang.xml_bad_structure')."<br><i>".$msgrow."</>";      
        }

        if($type == 1){
           if($config["model"] != "") $config["model"]::truncate();
           if($config["modelvalue"] != "") $config["modelvalue"]::truncate();
        }

        $data = []; 
        foreach($tabs[$config["key"]] as $key1 => $tab) {
            foreach($tab as $key2 => $tb){
                    if(is_array($tb)) {
                        if($key2 == $config["keyvalues"]){
                            if(isset($tb[$config["keyvalue"]]) && is_array($tb[$config["keyvalue"]])){
                                if(!isset($tb[$config["keyvalue"]][0])){
                                        $tmp = $tb[$config["keyvalue"]];
                                        $tb[$config["keyvalue"]] = [];
                                        $tb[$config["keyvalue"]][0] = $tmp; 
                                }
                                foreach($tb[$config["keyvalue"]] as $keyvalue){
                                    $datavalue = [];
                                    foreach($keyvalue as $key3 => $t){
                                        if(is_array($t) && sizeof($t) == 0);                            
                                        else 
                                            $datavalue[$key3] = $t;                                         
                                    }
                                    if(sizeof($datavalue) > 0 && isset($datavalue['id'])){                                            
                                        $exists = $config["modelvalue"]::find($datavalue['id']);
                                        if($exists)
                                            $exists->update($datavalue);
                                        else    
                                            $config["modelvalue"]::insertOrIgnore($datavalue);      
                                                                        
                                    }
                                }
                            }

                        }
                    }
                    else
                        $data[$key2] = $tb;                                  
           }
           if(sizeof($data) > 0 && isset($data['id'])){
                $exists = $config["model"]::find($data['id']);
                if($exists) {
                    $raport[0]++;
                    if(isset($data['password'])) unset($data['password']);
                    $exists->update($data);
                }
                else{    
                    $raport[1]++;
                    $config["model"]::insertOrIgnore($data);
                }
           }
        }   

        return $raport;     
        
    }

    public function check_model($field, $value, $type)
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_importxml')) return;
    
        if(preg_match('/int/i', $type)){

            if(!Util::is_int($value) || $value < 0)
                return trans('eprog.manager::lang.field')." ".$field." (".$value.") - ".trans('eprog.manager::lang.int_value')."<br>";

        }

        if(preg_match('/tinyint/i', $type)){

            if($value != "0" && $value != "1")
                return trans('eprog.manager::lang.field')." ".$field." (".$value.") - ".trans('eprog.manager::lang.boolean_value')."<br>";

        }

        if(preg_match('/decimal/i', $type) || preg_match('/float/i', $type)){

            if(!is_numeric($value)) 
                return trans('eprog.manager::lang.field')." ".$field." (".$value.") - ".trans('eprog.manager::lang.numeric_value')."<br>";

        }

        if(preg_match('/datetime/i', $type) || preg_match('/timestamp/i', $type)){

            if(!Util::is_date($value)) 
                return trans('eprog.manager::lang.field')." ".$field." (".$value.") - ".trans('eprog.manager::lang.date_value')."<br>";

        }


        return "";

    }
	

}