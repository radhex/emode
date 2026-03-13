<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Eprog\Manager\Models\Order as ModelOrder;
use Eprog\Manager\Models\SettingConfig;
use Eprog\Manager\Models\SettingNumeration;
use Eprog\Manager\Classes\Util;
use Rainlab\User\Models\User;
use Eprog\Manager\Models\SettingStatus;
use October\Rain\Exception\ValidationException;
use Carbon\Carbon;
use Input;
use Flash;
use Redirect;
use Session;
use Lang;
use Validator;
use Eprog\Manager\Classes\Ksef;
use Barryvdh\Snappy\Facades\SnappyPdf;
use DB;
use N1ebieski\KSEFClient\ClientBuilder;
use N1ebieski\KSEFClient\Factories\EncryptionKeyFactory;
use N1ebieski\KSEFClient\ValueObjects\Mode;

class Order extends Controller
{


    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $kor = 0;
    public $xml = null;
    public $referenceNumber = "";
    public $ksefNumber = "";

    public $requiredPermissions = ['eprog.manager.access_order'];


    public function __construct()
    {

        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'order');

        if(Input::segment(5) === null){

   
            if(!isset($_SESSION)) session_start();
            //unset($_SESSION["ksef"]);dd($_SESSION);
            if(!isset($_SESSION["ksef"])) 
                Ksef::getSession(); 
            else{
            
                $result = Ksef::sessionStatus($_SESSION["ksef"]["referenceNumber"]);
                $result = json_decode($result, true);  
            
                if(isset($result["processingCode"]) && $result["processingCode"] != 315 || isset($result["exception"])){

                    Ksef::sessionTerminate();
                    unset($_SESSION["ksef"]);
                    $redirect_uri = (isset($_SERVER['HTTPS']) ? "https":"http")."://".$_SERVER["SERVER_NAME"]."/".config('cms.backendUri')."/eprog/manager/order";
                    header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
                                                    
                }
            }
        }

        
    }


    public function listExtendQuery($query, $definition = null){

        $query->orderBy("id", "desc");  
        //$this->listConfig->recordsPerPage = 3;    
        //Session::forget("ksef");
        //dd(Session::all());

    }

    public function formExtendFields($form)
    {
        Util::checkExpired();
        Util::checkCapacity();
        
        if(!isset($_SESSION)) session_start();
 
        $xml = null;

        if(Input::segment(5) == "update") { 
        
            $order = ModelOrder::find(Input::segment(6));    
            if($order->xml)$xml = $order->xml;

        }

        if(Input::segment(5) == "create") { 
                 
            $form->getField('place')->value = SettingConfig::get("city");
            $form->getField('nr')->value = self::number(0);

            //$form->getField('currency')->value = SettingConfig::get("crrency") ?? 'PLN';
            //$form->getField('exchange')->value = 1;
            
            $form->getField('seller_name')->value = SettingConfig::get("firm");
            $form->getField('seller_nip')->value = SettingConfig::get("nip");
            $form->getField('seller_adres1')->value = "ul. ".SettingConfig::get("street")." ".SettingConfig::get("number");
            $form->getField('seller_adres2')->value = SettingConfig::get("code")." ".SettingConfig::get("city");
            $form->getField('seller_country')->value = "PL";


            if(Input::has("user_id")){
                
                $user = User::find(Input::get("user_id"));
                if($user){
                    $form->getField('buyer_name')->value = $user->firm_name;
                    $form->getField('buyer_nip')->value = $user->firm_nip;
                    $form->getField('buyer_adres1')->value = $user->firm_street." ".$user->firm_number;
                    $form->getField('buyer_adres2')->value = $user->firm_code." ".$user->firm_city;
                    //$form->getField('buyer_email')->value = $user->email;
                    //$form->getField('buyer_phone')->value = $user->phone;
                }

            }



        }

        if(SettingNumeration::get("order_block_number")) $form->getField('nr')->readOnly = true;

        if($xml){
        

            $xml = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($xml);
            $data = json_decode($json, true);
    
            if($data) {

                if(isset($data["Podmiot1"]["DaneIdentyfikacyjne"])){
                    $form->getField('seller_name')->value = $data["Podmiot1"]["DaneIdentyfikacyjne"]["Nazwa"];  
                    $form->getField('seller_nip')->value = $data["Podmiot1"]["DaneIdentyfikacyjne"]["NIP"]; 
                }
                if(isset($data["Podmiot1"]["Adres"])){
                    $form->getField('seller_adres1')->value = $data["Podmiot1"]["Adres"]["AdresL1"];    
                    $form->getField('seller_adres2')->value = $data["Podmiot1"]["Adres"]["AdresL2"];    
                    $form->getField('seller_country')->value = $data["Podmiot1"]["Adres"]["KodKraju"];  
                }
                if(isset($data["Podmiot1"]["DaneKontaktowe"])){
                    $form->getField('seller_email')->value = $data["Podmiot1"]["DaneKontaktowe"]["Email"] ?? "";    
                    $form->getField('seller_phone')->value = $data["Podmiot1"]["DaneKontaktowe"]["Telefon"] ?? "";  
                }


                if(isset($data["Podmiot2"]["DaneIdentyfikacyjne"])){

                    if(isset($data["Podmiot2"]["DaneIdentyfikacyjne"]["Nazwa"]))
                    $form->getField('buyer_name')->value = $data["Podmiot2"]["DaneIdentyfikacyjne"]["Nazwa"];   
                    if(isset($data["Podmiot2"]["DaneIdentyfikacyjne"]["NIP"])) {
                        $form->getField('buyer_type')->value = 0;
                        $form->getField('buyer_nip')->value = $data["Podmiot2"]["DaneIdentyfikacyjne"]["NIP"];  
                    }
                    if(isset($data["Podmiot2"]["DaneIdentyfikacyjne"]["NrVatUE"])) {
                        $form->getField('buyer_type')->value = 1;
                        $form->getField('buyer_nip')->value = $data["Podmiot2"]["DaneIdentyfikacyjne"]["KodUE"].$data["Podmiot2"]["DaneIdentyfikacyjne"]["NrVatUE"];    
                    }
                    if(isset($data["Podmiot2"]["DaneIdentyfikacyjne"]["NrID"])) {
                        $form->getField('buyer_type')->value = 2;
                        $form->getField('buyer_nip')->value = $data["Podmiot2"]["DaneIdentyfikacyjne"]["NrID"]; 
                    }
                    if(isset($data["Podmiot2"]["DaneIdentyfikacyjne"]["BrakID"])) {
                        $form->getField('buyer_type')->value = 3;
                    }
            
                }
                if(isset($data["Podmiot2"]["Adres"])){
                    $form->getField('buyer_adres1')->value = $data["Podmiot2"]["Adres"]["AdresL1"]; 
                    $form->getField('buyer_adres2')->value = $data["Podmiot2"]["Adres"]["AdresL2"]; 
                    $form->getField('buyer_country')->value = $data["Podmiot2"]["Adres"]["KodKraju"];   
                }
                if(isset($data["Podmiot2"]["DaneKontaktowe"])){
                    $form->getField('buyer_email')->value = $data["Podmiot2"]["DaneKontaktowe"]["Email"] ?? ""; 
                    $form->getField('buyer_phone')->value = $data["Podmiot2"]["DaneKontaktowe"]["Telefon"] ?? "";   
                }

                if(isset($data["Podmiot3"]))
                $form->getField('_addbuyer')->value = 1;

                if(isset($data["Podmiot3"]["DaneIdentyfikacyjne"])){
                    $form->getField('_addbuyer_name')->value = $data["Podmiot3"]["DaneIdentyfikacyjne"]["Nazwa"];   
                    if(isset($data["Podmiot3"]["DaneIdentyfikacyjne"]["NIP"])) {
                        $form->getField('_addbuyer_type')->value = 0;
                        $form->getField('_addbuyer_nip')->value = $data["Podmiot3"]["DaneIdentyfikacyjne"]["NIP"];  
                    }
                    if(isset($data["Podmiot3"]["DaneIdentyfikacyjne"]["NrVatUE"])) {
                        $form->getField('_addbuyer_type')->value = 1;
                        $form->getField('_addbuyer_nip')->value = $data["Podmiot3"]["DaneIdentyfikacyjne"]["KodUE"].$data["Podmiot3"]["DaneIdentyfikacyjne"]["NrVatUE"];    
                    }
                    if(isset($data["Podmiot3"]["DaneIdentyfikacyjne"]["NrID"])) {
                        $form->getField('_addbuyer_type')->value = 2;
                        $form->getField('_addbuyer_nip')->value = $data["Podmiot3"]["DaneIdentyfikacyjne"]["NrID"]; 
                    }
                    if(isset($data["Podmiot3"]["DaneIdentyfikacyjne"]["BrakID"])) {
                        $form->getField('_addbuyer_type')->value = 3;
                    }
                
                }
                if(isset($data["Podmiot3"]["Adres"])){
                    $form->getField('_addbuyer_adres1')->value = $data["Podmiot3"]["Adres"]["AdresL1"]; 
                    $form->getField('_addbuyer_adres2')->value = $data["Podmiot3"]["Adres"]["AdresL2"]; 
                    $form->getField('_addbuyer_country')->value = $data["Podmiot3"]["Adres"]["KodKraju"];   
                }
                if(isset($data["Podmiot3"]["DaneKontaktowe"])){
                    $form->getField('_addbuyer_email')->value = $data["Podmiot3"]["DaneKontaktowe"]["Email"] ?? ""; 
                    $form->getField('_addbuyer_phone')->value = $data["Podmiot3"]["DaneKontaktowe"]["Telefon"] ?? "";   
                }

                if(isset($data["Podmiot3"]["Rola"]))
                    $form->getField('_addbuyer_role')->value = $data["Podmiot3"]["Rola"] - 1;

                if(isset($data["Podmiot3"]["RolaInna"])) {
                    $form->getField('_addbuyer_role')->value = 10;
                    $form->getField('_addbuyer_role_desc')->value = $data["Podmiot3"]["OpisRoli"] ?? "";
                }

                if(isset($data["Fa"]["FakturaZaliczkowa"]["NrKSeFFaZaliczkowej"]))
                    $form->getField('_advance_nr')->value = $data["Fa"]["FakturaZaliczkowa"]["NrKSeFFaZaliczkowej"] ?? "";
                


                if(isset($data["Fa"]["Platnosc"]["Zaplacono"])){
                    $form->getField('_pay_info')->value = 0;
                    $form->getField('_pay_date')->value = $data["Fa"]["Platnosc"]["DataZaplaty"] ?? "";
                }

                if(isset($data["Fa"]["Platnosc"]["ZnacznikZaplatyCzesciowej"])){
                    $form->getField('_pay_info')->value = 1;
                    $form->getField('_pay_part')->value = $data["Fa"]["Platnosc"]["ZaplataCzesciowa"]["KwotaZaplatyCzesciowej"] ?? "";
                    $form->getField('_pay_date')->value = $data["Fa"]["Platnosc"]["ZaplataCzesciowa"]["DataZaplatyCzesciowej"] ?? "";
                    $form->getField('_pay_info')->width= 100;
                }

                $form->getField('_pay_termin')->value = $data["Fa"]["Platnosc"]["TerminPlatnosci"]["Termin"] ?? "";
                $form->getField('_pay_other_desc')->value = $data["Fa"]["Platnosc"]["OpisPlatnosci"] ?? "";

                if(isset($data["Fa"]["Platnosc"]["FormaPlatnosci"])) $form->getField('_pay_type')->value = $data["Fa"]["Platnosc"]["FormaPlatnosci"] - 1; 
                if(isset($data["Fa"]["Platnosc"]["PlatnoscInna"]))  $form->getField('_pay_type')->value = 7;
                $form->getField('_pay_termin_ilosc')->value = $data["Fa"]["Platnosc"]["TerminPlatnosci"]["TerminOpis"]['Ilosc'] ?? "";
                $form->getField('_pay_termin_jednostka')->value = $data["Fa"]["Platnosc"]["TerminPlatnosci"]["TerminOpis"]['Jednostka'] ?? "dni";
                $form->getField('_pay_termin_zdarzeniepoczatkowe')->value = $data["Fa"]["Platnosc"]["TerminPlatnosci"]["TerminOpis"]['ZdarzeniePoczatkowe'] ?? "od daty wystawienia";

                $form->getField('_bank_nr')->value = $data["Fa"]["Platnosc"]["RachunekBankowy"]["NrRB"] ?? "";
                $form->getField('_swift')->value = $data["Fa"]["Platnosc"]["RachunekBankowy"]["SWIFT"] ?? "";
                $form->getField('_bank')->value = $data["Fa"]["Platnosc"]["RachunekBankowy"]["NazwaBanku"] ?? "";

                $form->getField('_pay_link')->value = $data["Fa"]["Platnosc"]["LinkDoPlatnosci"] ?? "";
                $form->getField('_pay_id')->value = $data["Fa"]["Platnosc"]["IPKSeF"] ?? "";

                $form->getField('_skonto_cond')->value = $data["Fa"]["Platnosc"]["Skonto"]["WarunkiSkonta"] ?? "";
                $form->getField('_skonto')->value = $data["Fa"]["Platnosc"]["Skonto"]["WysokoscSkonta"] ?? "";

                if(isset($data["Fa"]["Adnotacje"]["Zwolnienie"]["P_19A"])){
                    $form->getField('_zw')->value = 0;
                    $form->getField('_zw_desc')->value = $data["Fa"]["Adnotacje"]["Zwolnienie"]["P_19A"] ?? "";
                }
                if(isset($data["Fa"]["Adnotacje"]["Zwolnienie"]["P_19B"])){
                    $form->getField('_zw')->value = 1;
                    $form->getField('_zw_desc')->value = $data["Fa"]["Adnotacje"]["Zwolnienie"]["P_19B"] ?? "";
                }
                if(isset($data["Fa"]["Adnotacje"]["Zwolnienie"]["P_19C"])){
                    $form->getField('_zw')->value = 2;
                    $form->getField('_zw_desc')->value = $data["Fa"]["Adnotacje"]["Zwolnienie"]["P_19C"] ?? "";
                }

                if(isset($data["Fa"]["Adnotacje"]["PMarzy"]["P_PMarzy_3_1"])) $form->getField('_marza')->value = 0;
                if(isset($data["Fa"]["Adnotacje"]["PMarzy"]["P_PMarzy_3_2"])) $form->getField('_marza')->value = 1;
                if(isset($data["Fa"]["Adnotacje"]["PMarzy"]["P_PMarzy_2"])) $form->getField('_marza')->value = 2;
                if(isset($data["Fa"]["Adnotacje"]["PMarzy"]["P_PMarzy_3_3"])) $form->getField('_marza')->value = 3;

                if($data["Fa"]["Adnotacje"]["P_16"] == "1") $form->getField('_mk')->value = 1;
                if($data["Fa"]["Adnotacje"]["P_17"] == "1") $form->getField('_sf')->value = 1;
                if($data["Fa"]["Adnotacje"]["P_18"] == "1") $form->getField('_oo')->value = 1;
                if($data["Fa"]["Adnotacje"]["P_18A"] == "1") $form->getField('_mpp')->value = 1;
                if($data["Fa"]["Adnotacje"]["P_23"] == "1") $form->getField('_ptu')->value = 1;

                if(isset($data["Fa"]["PrzyczynaKorekty"])) $form->getField('_kor_reason')->value = $data["Fa"]["PrzyczynaKorekty"];
                if(isset($data["Fa"]["TypKorekty"])) $form->getField('_kor_type')->value = $data["Fa"]["TypKorekty"];
                
                if(isset($data["Fa"]["FP"])) $form->getField('_fp')->value = 1;
                if(isset($data["Fa"]["TP"])) $form->getField('_tp')->value = 1;
                if(isset($data["Fa"]["ZwrotAkcyzy"])) $form->getField('_za')->value = 1;

                $form->getField('_umo_nr')->value = $data["Fa"]["WarunkiTransakcji"]["Umowy"]["NrUmowy"] ?? "";
                $form->getField('_umo_date')->value = $data["Fa"]["WarunkiTransakcji"]["Umowy"]["DataUmowy"] ?? "";
                //$form->getField('_zam_nr')->value = $data["Fa"]["WarunkiTransakcji"]["Zamowienia"]["NrZamowienia"] ?? "";
                //$form->getField('_zam_date')->value = $data["Fa"]["WarunkiTransakcji"]["Zamowienia"]["DataZamowienia"] ?? "";
                $form->getField('_wu')->value = $data["Fa"]["WarunkiTransakcji"]["WalutaUmowna"] ?? "";
                $form->getField('_ku')->value = $data["Fa"]["WarunkiTransakcji"]["KursUmowny"] ?? "";
                $form->getField('_wdt')->value = $data["Fa"]["WarunkiTransakcji"]["WarunkiDostawy"] ?? "";
                if(isset($data["Fa"]["WarunkiTransakcji"]["PodmiotPosredniczacy"])) $form->getField('_pp')->value = 1;

                $form->getField('_wz')->value = implode("\n",$data["Fa"]["WZ"] ?? []) ?? '';

                //$form->getField('_add_key')->value = $data["Fa"]["DodatkowyOpis"]["Klucz"] ?? "";
                //$form->getField('_add_value')->value = $data["Fa"]["DodatkowyOpis"]["Wartosc"] ?? "";
                $form->getField('_full_name')->value = $data["Stopka"]["Rejestry"]["PelnaNazwa"] ?? "";
                $form->getField('_krs')->value = $data["Stopka"]["Rejestry"]["KRS"] ?? "";
                $form->getField('_regon')->value = $data["Stopka"]["Rejestry"]["REGON"] ?? "";
                $form->getField('_bdo')->value = $data["Stopka"]["Rejestry"]["BDO"] ?? "";
                $form->getField('_stopka')->value = $data["Stopka"]["Informacje"]["StopkaFaktury"] ?? "";

                if(isset($data["Fa"]["DodatkowyOpis"][0])){
                    foreach($data["Fa"]["DodatkowyOpis"] as $opis){
                        if($opis["Klucz"] == "Adnotacje") $form->getField('_uwagi')->value =  $opis["Wartosc"] ?? '';
                    }
                }
                if(isset($data["Fa"]["DodatkowyOpis"]["Klucz"]) && $data["Fa"]["DodatkowyOpis"]["Klucz"] == "Uwagi")
                    $form->getField('_uwagi')->value =  $data["Fa"]["DodatkowyOpis"]["Wartosc"] ?? '';

            }

        }


    }

    public function listExtendColumns($list)
    {

        $list->getColumn("brutto")->label = e(trans("eprog.manager::lang.gross"));
        $list->getColumn("netto")->label =  e(trans("eprog.manager::lang.net"));
        $list->getColumn("vat")->label =  e(trans("eprog.manager::lang.vat"));
  
    } 

    public static function status($id = null)
    {

        $return = [];
        $statuses = explode(PHP_EOL,SettingStatus::get("order_".Lang::getLocale()) ?? []);

        foreach($statuses as $status){
            $st = explode(";",$status);
            $return[$st[0]] = $st[1];
        }

        return $return;

    }

    public function listFilterExtendScopes($scope)
    {

        $status = self::status();
        unset($status[0]);
        $scope->getScope("status")->options = $status;
    
    }


    public function onExport()
    {

        $url = config('cms.backendUri')."/eprog/manager/export/orderxml";
        if(isset($_POST['checked'])) $url .= "?checked=".implode(",",$_POST['checked']);
        return Redirect::to($url);

    }

    public function onKsefSend()
    {

        $faktura = Ksef::xml("Order"); 
        $order = ModelOrder::find(Input::segment(6));  
        $xml = Ksef::verifyXml($order->xml);
        $result = Ksef::orderSend($xml);

        if(isset($result['ksefNumber'])){        
            if(Input::segment(6) > 0)
            DB::update("update  eprog_manager_order set ksefNumber = '".$result['ksefNumber']."', referenceNumber = '".$result['referenceNumber']."', orderReferenceNumber = '".$result['orderReferenceNumber']."'  where id = '".Input::segment(6)."'");
            return  $result['ksefNumber'];   
        }           
        else
            throw new ValidationException(['my_field'=>e(trans('eprog.manager::lang.ksef.send_error'))]);
        
    
    }


    public function onPdf()
    {
        $file = self::onPdfGenerate(Input::segment(6));
        if(file_exists($file)) return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getfile?file='.urlencode($file));
    }

    public static function onPdfGenerate($id)
    {

        $order = ModelOrder::find($id);  
        if($order && $order->nr){   
            $file = storage_path('temp/public/'.str_replace("/","_",$order->nr).'_'.str_replace(".","",$order->seller_name).'.pdf');
            $html = Ksef::orderHtml($order->xml,$order->nr);
            $pdf = SnappyPdf::loadHTML($html)->output(); 
            file_put_contents($file, $pdf);  
            return $file;      
        }
        else
            Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));
            
    }


    public function onProforma()
    {
        $file = self::onProformaGenerate(Input::segment(6));
        if(file_exists($file)) return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getfile?file='.urlencode($file));
    }

    public static function onProformaGenerate($id)
    {

        $order = ModelOrder::find($id);  
        if($order && $order->nr){   
            $file = storage_path('temp/public/proforma_'.str_replace("/","_",$order->nr).'_'.str_replace(".","",$order->seller_name).'.pdf');
            $html = Ksef::orderHtmlPro($order->xml,$order->nr);
            $pdf = SnappyPdf::loadHTML($html)->output(); 
            file_put_contents($file, $pdf);  
            return $file;     
        }
        else
            Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));
            
    }





    public static function number($type)
    {

            $types = ["ZAM"];

            $order_type = SettingNumeration::get("order_type") ?? "month";

            $prefix = SettingNumeration::get("order_prefix") ?? "";
            $separator = SettingNumeration::get("order_separator") ?? "/";
            $sufix = SettingNumeration::get("order_sufix") ?? "";

            $max = ModelOrder::where("nr","!=","")->orderBy("id","desc")->first()->nr ?? null;
            $max = preg_replace('/^'.preg_quote($prefix, '/').'/', '',$max);
            $max = preg_replace('/'.preg_quote($sufix, '/'). '$/', '',$max);

            if(isset($max)){
                $max = explode($separator, $max);
                settype($max[1], "integer");
            }

            $order_number = "";
            $month = Util::invdate(Carbon::now(), 3);
            $year = Util::invdate(Carbon::now(), 4);

            if($order_type == "month"){
                if(isset($max[2]) && $max[2] == $month)
                    $number = Util::zero_first(++$max[1]);
                else
                    $number = "01";

                $order_number =  $prefix.$types[$type].$separator.$number.$separator.$month.$separator.$year.$sufix;
            }

            if($order_type == "year"){
                if(isset($max[2]) && $max[2] == $year)
                    $number = Util::zero_first(++$max[1]);
                else
                    $number = "01";

                $order_number =  $prefix.$types[$type].$separator.$number.$separator.$year.$sufix;
            }


            return $order_number;

    }


}