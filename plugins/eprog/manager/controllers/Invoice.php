<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Eprog\Manager\Models\Invoice as ModelInvoice;
use Eprog\Manager\Models\SettingConfig;
use Eprog\Manager\Models\SettingNumeration;
use Eprog\Manager\Models\Order;
use Eprog\Manager\Models\Ksef as ModelKsef;
use Eprog\Manager\Models\SettingStatus;
use Eprog\Manager\Classes\Util;
use Rainlab\User\Models\User;
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

class Invoice extends Controller
{


    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $kor = 0;
    public $xml = null;
    public $referenceNumber = "";
    public $ksefNumber = "";

    public $requiredPermissions = ['eprog.manager.access_invoice'];


    public function __construct()
    {

        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'invoice');

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
        			$redirect_uri = (isset($_SERVER['HTTPS']) ? "https":"http")."://".$_SERVER["SERVER_NAME"]."/".config('cms.backendUri')."/eprog/manager/invoice";
        			header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
        											
        		}
        	}
        }

        
    }

    public function index()
    {
    	$this->addJs('/plugins/rainlab/user/assets/js/bulk-actions.js');
        $this->asExtension('ListController')->index();
        $this->listGetWidget()->setSort("id", "desc");

    }


    public function listExtendQuery($query, $definition = null){
  
    	//$this->listGetWidget()->setSort("id", "desc");
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
    	$form->getField('kor')->hidden = true;
    	$form->getField('_kor_reason')->hidden = true;  
    	$form->getField('_kor_type')->hidden = true;


    	//$form->getField('_advance_nr')->hidden = true;   	

    	$type = post('Item[type]') ?? 0;

    	if(Input::segment(5) == "update") { 
    	
    		$invoice = ModelInvoice::find(Input::segment(6));    
    		if($invoice->xml)$xml = $invoice->xml;
    		if($invoice->ksefNumber)
    			$form->getField('_ksef_number')->value = $invoice->ksefNumber;
    		

    		if($invoice && ($invoice->type == 1 || $invoice->type == 5 || $invoice->type == 6)){
    	
    			$this->kor = 1; 
    			$form->getField('_kor_reason')->hidden = false;   
    			$form->getField('_kor_type')->hidden = false;    	
    			$form->getField('kor')->hidden = false;

    		}

    	}

		if(Input::segment(5) == "create") { 
			
			$form->getField('status_id')->value = 1; 
			$form->getField('place')->value = SettingConfig::get("city");
			$form->getField('nr')->value = self::number(0);		
			$form->getField('seller_name')->value = SettingConfig::get("firm");
			$form->getField('seller_nip')->value = SettingConfig::get("nip");
			$form->getField('seller_adres1')->value = "ul. ".SettingConfig::get("street")." ".SettingConfig::get("number");
			$form->getField('seller_adres2')->value = SettingConfig::get("code")." ".SettingConfig::get("city");
			$form->getField('seller_country')->value = "PL";

			if(Input::has("correct_id")){

				$invoice = ModelInvoice::find(Input::get("correct_id"));	

				if($invoice && in_array($invoice->type,[0,2,3,4]) && $invoice->xml && strlen($invoice->ksefNumber) > 0){

					$type = 1;
					if($invoice->type == 2) $type = 5;
					if($invoice->type == 3) $type = 6;

					$this->kor = 1;
					$this->xml = $invoice->xml;
					$xml = $this->xml;
					$form->getField('type')->value = 1;
					$form->getField('_kor_reason')->hidden = false;    
					$form->getField('_kor_type')->hidden = false;    	
					$form->getField('kor')->hidden = false; 
					$form->getField('nr')->value = self::number($type);
					$form->getField('user_id')->value = $invoice->user_id;
					//$form->getField('currency')->value = $invoice->currency;
					//$form->getField('exchange')->value = $invoice->exchange;
				}
			}

			if(Input::has("advance")){

				$form->getField('nr')->value = self::number(3);
				$invoice = ModelInvoice::where("ksefNumber",Input::get("advance"))->first();

				if($invoice && $invoice->xml && strlen($invoice->ksefNumber) > 0){

					$this->xml = $invoice->xml;
					$xml = $this->xml;
					$form->getField('_advance_nr')->value = $invoice->ksefNumber;
					$form->getField('user_id')->value = $invoice->user_id;
					//$form->getField('currency')->value = $invoice->currency;
					//$form->getField('exchange')->value = $invoice->exchange;
				}
			}

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

			if(Input::has("order_id")){

				$order = Order::find(Input::get("order_id"));
				if($order){

					$this->xml = $order->xml;
					$xml = $this->xml;
					//$form->getField('currency')->value = $order->currency;
					//$form->getField('exchange')->value = $order->exchange;
					$form->getField('buyer_name')->value = $order->buyer_name;
					$form->getField('buyer_nip')->value = $order->buyer_nip;
					$form->getField('buyer_adres1')->value = $order->buyer_adres1;
					$form->getField('buyer_adres2')->value = $order->buyer_adres2;
					$form->getField('buyer_email')->value = $order->buyer_email;
					$form->getField('buyer_phone')->value = $order->buyer_phone;
					$form->getField('seller_name')->value = $order->seller_name;
					$form->getField('seller_nip')->value = $order->seller_nip;
					$form->getField('seller_adres1')->value = $order->seller_adres1;
					$form->getField('seller_adres2')->value = $order->seller_adres2;
					$form->getField('seller_email')->value = $order->seller_email;
					$form->getField('seller_phone')->value = $order->seller_phone;

					$form->getField('_zam_nr')->value = $order->nr;
					$form->getField('_zam_date')->value = $order->create_at;

					$form->getField('place')->value = $order->place;

				}
											
			}

			if(Input::has("invoice_id")){

				$invoice = ModelInvoice::find(Input::get("invoice_id"));
				if($invoice && !in_array($invoice->type,[1,5,6])){

					$this->xml = $invoice->xml;
					$xml = $this->xml;
					$form->getField('nr')->value = self::number($invoice->type);
					$form->getField('type')->value = $invoice->type;
					//$form->getField('currency')->value = $invoice->currency;
					//$form->getField('exchange')->value = $invoice->exchange;
					$form->getField('buyer_name')->value = $invoice->buyer_name;
					$form->getField('buyer_nip')->value = $invoice->buyer_nip;
					$form->getField('buyer_adres1')->value = $invoice->buyer_adres1;
					$form->getField('buyer_adres2')->value = $invoice->buyer_adres2;
					$form->getField('buyer_email')->value = $invoice->buyer_email;
					$form->getField('buyer_phone')->value = $invoice->buyer_phone;
					$form->getField('seller_name')->value = $invoice->seller_name;
					$form->getField('seller_nip')->value = $invoice->seller_nip;
					$form->getField('seller_adres1')->value = $invoice->seller_adres1;
					$form->getField('seller_adres2')->value = $invoice->seller_adres2;
					$form->getField('seller_email')->value = $invoice->seller_email;
					$form->getField('seller_phone')->value = $invoice->seller_phone;

					$form->getField('place')->value = $invoice->place;

				}
											
			}


			if(Input::has("ksef_id")){

				$invoice = ModelKsef::find(Input::get("ksef_id"));
				if($invoice && !in_array($invoice->invoiceType,["Kor","KorZal","KorRoz"])){

					$type = array_search($invoice->invoiceType,array_keys(Util::getInvoiceType())) ?? 0;

					$this->xml = $invoice->xml;
					$xml = $this->xml;
					$form->getField('nr')->value = self::number($type);	
					$form->getField('type')->value = $type;
					//$form->getField('currency')->value = $invoice->currency;
			
				}
											
			}


		}

		if(SettingNumeration::get("invoice_block_number")) $form->getField('nr')->readOnly = true;

		if($xml){
		

			$xml = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);
			$json = json_encode($xml);
			$data = json_decode($json, true);
//dd($data);
			if($data) {

				if(!Input::has("ksef_id")){
					if(isset($data["Podmiot1"]["DaneIdentyfikacyjne"])){
						$form->getField('seller_name')->value = $data["Podmiot1"]["DaneIdentyfikacyjne"]["Nazwa"] ?? '';	
						$form->getField('seller_nip')->value = $data["Podmiot1"]["DaneIdentyfikacyjne"]["NIP"] ?? '';	
					}
					if(isset($data["Podmiot1"]["Adres"])){
						$form->getField('seller_adres1')->value = $data["Podmiot1"]["Adres"]["AdresL1"] ?? '';	
						$form->getField('seller_adres2')->value = $data["Podmiot1"]["Adres"]["AdresL2"] ?? '';	
						$form->getField('seller_country')->value = $data["Podmiot1"]["Adres"]["KodKraju"] ?? '';	
					}
					if(isset($data["Podmiot1"]["DaneKontaktowe"])){
						$form->getField('seller_email')->value = $data["Podmiot1"]["DaneKontaktowe"]["Email"] ?? "";	
						$form->getField('seller_phone')->value = $data["Podmiot1"]["DaneKontaktowe"]["Telefon"] ?? "";	
					}
				}


				if(isset($data["Podmiot2"]["DaneIdentyfikacyjne"])){

					if(isset($data["Podmiot2"]["DaneIdentyfikacyjne"]["Nazwa"]))
					$form->getField('buyer_name')->value = $data["Podmiot2"]["DaneIdentyfikacyjne"]["Nazwa"] ?? '';	
					if(isset($data["Podmiot2"]["DaneIdentyfikacyjne"]["NIP"])) {
						$form->getField('buyer_type')->value = 0;
						$form->getField('buyer_nip')->value = $data["Podmiot2"]["DaneIdentyfikacyjne"]["NIP"] ?? '';

						$user = User::where("firm_nip","=",$form->getField('buyer_nip')->value)->first();
						if($user) $form->getField('user_id')->value = $user->id;	
					}
					if(isset($data["Podmiot2"]["DaneIdentyfikacyjne"]["NrVatUE"])) {
						$form->getField('buyer_type')->value = 1;
						$form->getField('buyer_nip')->value = $data["Podmiot2"]["DaneIdentyfikacyjne"]["KodUE"].$data["Podmiot2"]["DaneIdentyfikacyjne"]["NrVatUE"]?? '';	
					}
					if(isset($data["Podmiot2"]["DaneIdentyfikacyjne"]["NrID"])) {
						$form->getField('buyer_type')->value = 2;
						$form->getField('buyer_nip')->value = $data["Podmiot2"]["DaneIdentyfikacyjne"]["NrID"] ?? '';	
					}
					if(isset($data["Podmiot2"]["DaneIdentyfikacyjne"]["BrakID"])) {
						$form->getField('buyer_type')->value = 3;
					}
			
				}
				if(isset($data["Podmiot2"]["Adres"])){
					$form->getField('buyer_adres1')->value = $data["Podmiot2"]["Adres"]["AdresL1"] ?? '';	
					$form->getField('buyer_adres2')->value = $data["Podmiot2"]["Adres"]["AdresL2"] ?? '';	
					$form->getField('buyer_country')->value = $data["Podmiot2"]["Adres"]["KodKraju"] ?? '';	
				}
				if(isset($data["Podmiot2"]["DaneKontaktowe"])){
					$form->getField('buyer_email')->value = $data["Podmiot2"]["DaneKontaktowe"]["Email"] ?? "";	
					$form->getField('buyer_phone')->value = $data["Podmiot2"]["DaneKontaktowe"]["Telefon"] ?? "";	
				}

				if(isset($data["Podmiot3"]))
				$form->getField('_addbuyer')->value = 1;

				if(isset($data["Podmiot3"]["DaneIdentyfikacyjne"])){
					$form->getField('_addbuyer_name')->value = $data["Podmiot3"]["DaneIdentyfikacyjne"]["Nazwa"] ?? '';	
					if(isset($data["Podmiot3"]["DaneIdentyfikacyjne"]["NIP"])) {
						$form->getField('_addbuyer_type')->value = 0;
						$form->getField('_addbuyer_nip')->value = $data["Podmiot3"]["DaneIdentyfikacyjne"]["NIP"] ?? '';	
					}
					if(isset($data["Podmiot3"]["DaneIdentyfikacyjne"]["NrVatUE"])) {
						$form->getField('_addbuyer_type')->value = 1;
						$form->getField('_addbuyer_nip')->value = $data["Podmiot3"]["DaneIdentyfikacyjne"]["KodUE"].$data["Podmiot3"]["DaneIdentyfikacyjne"]["NrVatUE"] ?? '';	
					}
					if(isset($data["Podmiot3"]["DaneIdentyfikacyjne"]["NrID"])) {
						$form->getField('_addbuyer_type')->value = 2;
						$form->getField('_addbuyer_nip')->value = $data["Podmiot3"]["DaneIdentyfikacyjne"]["NrID"] ?? '';	
					}
					if(isset($data["Podmiot3"]["DaneIdentyfikacyjne"]["BrakID"])) {
						$form->getField('_addbuyer_type')->value = 3;
					}
				
				}
				if(isset($data["Podmiot3"]["Adres"])){
					$form->getField('_addbuyer_adres1')->value = $data["Podmiot3"]["Adres"]["AdresL1"] ?? '';	
					$form->getField('_addbuyer_adres2')->value = $data["Podmiot3"]["Adres"]["AdresL2"] ?? '';	
					$form->getField('_addbuyer_country')->value = $data["Podmiot3"]["Adres"]["KodKraju"] ?? '';	
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

				//$form->getField('exchange')->value = $data["Fa"]["FaWiersz"][0]['KursWaluty'] ?? $form->getField('exchange')->value;

				$form->getField('place')->value = $data["Fa"]["P_1M"] ?? $form->getField('place')->value;

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

				$form->getField('_charge_amount')->value = $data["Fa"]["Rozliczenie"]["Obciazenia"]["Kwota"] ?? "";
				$form->getField('_charge_reason')->value = $data["Fa"]["Rozliczenie"]["Obciazenia"]["Powod"] ?? "";
				$form->getField('_deduct_amount')->value = $data["Fa"]["Rozliczenie"]["Odliczenia"]["Kwota"] ?? "";
				$form->getField('_deduct_reason')->value = $data["Fa"]["Rozliczenie"]["Odliczenia"]["Powod"] ?? "";

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
				if($form->getField('_zam_nr')->value == "") $form->getField('_zam_nr')->value = $data["Fa"]["WarunkiTransakcji"]["Zamowienia"]["NrZamowienia"] ?? "";
				if($form->getField('_zam_date')->value == "") $form->getField('_zam_date')->value = $data["Fa"]["WarunkiTransakcji"]["Zamowienia"]["DataZamowienia"] ?? "";
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
        $statuses = explode(PHP_EOL,SettingStatus::get("invoice_".Lang::getLocale()) ?? []);

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

    public function index_onBulkAction()
    {


        if (
            ($bulkAction = post('action')) &&
            ($checkedIds = post('checked')) &&
            is_array($checkedIds) &&
            count($checkedIds)
        ) {
        	$ids = [];
        	$ids['delete'] = [];
        	$ids['sendmultiple'] = [];
        	$ids['paid'] = [];
        	$ids['unpaid'] = [];
            foreach ($checkedIds as $Id) {           
                switch ($bulkAction) {
                    case 'delete':                        
                    	$ids['delete'][] = $Id;
                        break;
                    case 'sendmultiple':
                        $ids['sendmultiple'][] = $Id;
                    case 'paid':
                        $ids['paid'][] = $Id;
                    case 'unpaid':
                        $ids['unpaid'][] = $Id;  
                        break;
                }


            }

            if(sizeof($ids['delete']) > 0) self::onDeleteMultiple($ids['delete']);
            if(sizeof($ids['sendmultiple']) > 0) self::onKsefSendMultiple($ids['sendmultiple']);
            if(sizeof($ids['paid']) > 0) self::onPaidMultiple($ids['paid']);
            if(sizeof($ids['unpaid']) > 0) self::onUnpaidMultiple($ids['unpaid']);
        }
        else {
            Flash::error(Lang::get('eprog.manager::lang.selected_empty'));
        }

        return $this->listRefresh();
    }



    public function onExport()
    {

    	$url = config('cms.backendUri')."/eprog/manager/export/invoicexml";
    	if(isset($_POST['checked'])) $url .= "?checked=".implode(",",$_POST['checked']);
        return Redirect::to($url);

    }

    public function onDeleteMultiple($ids)
    {

    	 $del = 1;//ModelInvoice::whereIn("id",$ids)->delete();
    	 if($del) Flash::success(Lang::get('eprog.manager::lang.del_selected'));
    	
	}

	public function onPaidMultiple($ids)
	{

	     $del = ModelInvoice::whereIn("id",$ids)->update(["paid" => 1]);
	     if($del) Flash::success(Lang::get('eprog.manager::lang.process_success'));
	    
	}

	public function onUnpaidMultiple($ids)
	{

	     $del = ModelInvoice::whereIn("id",$ids)->update(["paid" => null]);
	     if($del) Flash::success(Lang::get('eprog.manager::lang.process_success'));
	    
	}


    public function onKsefSend()
    {

    	$faktura = Ksef::xml(); 
    	$invoice = ModelInvoice::find(Input::segment(6));  
    	$xml = Ksef::verifyXml($invoice->xml);
    	$result = Ksef::invoiceSend($xml);

    	if(isset($result['ksefNumber'])){    	 
    	 	if(Input::segment(6) > 0)
    	    DB::update("update  eprog_manager_invoice set ksef_at = '".Carbon::now()."',ksefNumber = '".$result['ksefNumber']."', referenceNumber = '".$result['referenceNumber']."', invoiceReferenceNumber = '".$result['invoiceReferenceNumber']."', status_id = 2  where id = '".Input::segment(6)."'");
    	    return  $result['ksefNumber'];   
    	}           
    	else
    	    throw new ValidationException(['my_field'=>e(trans('eprog.manager::lang.ksef.send_error'))]);
    	
    
    }

    public function onKsefSendMultiple($ids)
    {

    	$invoices = [];
    	foreach($ids as $id){
    		$invoice = ModelInvoice::find($id);
    		if($invoice->ksefNumber == "")
    	    $invoices[]  = $invoice->xml ?? '';
    	}
    	
    	if(sizeof($invoices) > 0) {
	    	$upo  = Ksef::invoiceSendMultiple($invoices);
	    	if($upo){
		    	$upoxml  = simplexml_load_string($upo, "SimpleXMLElement", LIBXML_NOCDATA);
		    	$json = json_encode($upoxml);
		    	$data = json_decode($json, true);
		    	$invoice = new ModelInvoice();

		    	if(isset($data['Dokument'][0])){
			    	foreach($data['Dokument'] as $doc)
			    		$invoice->where("nr","=",$doc["NumerFaktury"])->update(["ksefNumber" => $doc["NumerKSeFDokumentu"], "upo" => $upo, "status_id" => 2, "ksef_at" => date("Y-m-d H:i",strtotime($doc["DataPrzeslaniaDokumentu"]))]);
				}
				else{
					$doc = $data['Dokument'];
					$invoice->where("nr","=",$doc["NumerFaktury"])->update(["ksefNumber" => $doc["NumerKSeFDokumentu"], "upo" => $upo, "status_id" => 2, "ksef_at" => date("Y-m-d H:i",strtotime($doc["DataPrzeslaniaDokumentu"]))]);
				}
		  
		    	Flash::success(Lang::get('eprog.manager::lang.ksef.sendmultiple_success'));
	    	}
	    	
    	}

    
    }

    public function onKsefXml()
    {
    	$file = self::onKsefXmlGenerate(Input::segment(6));
    	if(file_exists($file)) return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getfile?file='.urlencode($file));
    }

    public static function onKsefXmlGenerate($id)
    {

    	  $invoice = ModelInvoice::find($id);  
    	  if($invoice && $invoice->ksefNumber){	    		
    			$file = storage_path('temp/public/'.str_replace("/","_",$invoice->nr).'_'.str_replace(".","",$invoice->seller_name).'.xml');
    			$xml = $invoice->xml;
    			file_put_contents($file, $xml);  
    			return $file;    			
    	  }
    	  else
    	  	Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));       	  	
		
    }

    public function onKsefPdf()
    {
    	$file = self::onKsefPdfGenerate(Input::segment(6));
    	if(file_exists($file)) return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getfile?file='.urlencode($file));
    }

    public static function onKsefPdfGenerate($id)
    {

	    $invoice = ModelInvoice::find($id);  
	    if(!$invoice->ksefNumber) $invoice->ksefNumber = "Offline";
	    if($invoice && $invoice->ksefNumber){	
	    	$file = storage_path('temp/public/'.str_replace("/","_",$invoice->nr).'_'.str_replace(".","",$invoice->seller_name).'.pdf');
			$pdf = KSef::generateInvoicePdf($invoice); 
			file_put_contents($file, $pdf);  
			return $file;    
	    }
	    else
	    	Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));

    }


    public function onKsefPdfOffline()
    {
    	$file = self::onKsefPdfOfflineGenerate(Input::segment(6));
    	if(file_exists($file)) return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getfile?file='.urlencode($file));
    }

    public static function onKsefPdfOfflineGenerate($id)
    {

	    $invoice = ModelInvoice::find($id);  
	    if(!$invoice->ksefNumber) $invoice->ksefNumber = "Offline";
	    if($invoice && $invoice->ksefNumber){	
	    	$file = storage_path('temp/public/offline_'.str_replace("/","_",$invoice->nr).'_'.str_replace(".","",$invoice->seller_name).'.pdf');
			$pdf = KSef::generateOfflinePdf($invoice); 
			file_put_contents($file, $pdf);  
			return $file;    		
	    }
	    else
	    	Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));

    }
    public function onKsefPdfConfirmation()
    {
    	$file = self::onKsefPdfConfirmationGenerate(Input::segment(6));
    	if(file_exists($file)) return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getfile?file='.urlencode($file));
   	}

    public static function onKsefPdfConfirmationGenerate($id)
    {

	    $invoice = ModelInvoice::find($id);  
	    if(!$invoice->ksefNumber) $invoice->ksefNumber = "confirmation";
	    if($invoice && $invoice->ksefNumber){	
	    	$file = storage_path('temp/public/potwierdzenie'.str_replace("/","_",$invoice->nr).'_'.str_replace(".","",$invoice->seller_name).'.pdf');
			$pdf = KSef::generateConfirmationPdf($invoice); 
			file_put_contents($file, $pdf);  
			return $file;    	
	    }
	    else
	    	Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));

    }

    public function onMyPdf()
    {
    	$file = self::onMyPdfGenerate(Input::segment(6));
    	if(file_exists($file)) return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getfile?file='.urlencode($file));
    }

    public static function onMyPdfGenerate($id)
    {

	    $invoice = ModelInvoice::find($id);  
	    if(!$invoice->ksefNumber) $invoice->ksefNumber = "Offline";
	    if($invoice && $invoice->ksefNumber){	
	    	$file = storage_path('temp/public/'.str_replace("/","_",$invoice->nr).'_'.str_replace(".","",$invoice->seller_name).'.pdf');
	    	$html = Ksef::invoiceMyHtml($invoice->xml,$invoice->nr);
	    	$pdf = SnappyPdf::loadHTML($html)->output(); 
			file_put_contents($file, $pdf);  
			return $file;    
	    }
	    else
	    	Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));

    }

    public function onKsefUpo()
    {
    	$file = self::onKsefUpoGenerate(Input::segment(6));
    	if(file_exists($file)) return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getfile?file='.urlencode($file));

    }
    public static function onKsefUpoGenerate($id)
    {

    	$invoice = ModelInvoice::find($id);  
    	if($invoice && $invoice->ksefNumber){	    		
    		$file = storage_path('temp/public/UPO_'.str_replace("/","_",$invoice->nr).'_'.str_replace(".","",$invoice->seller_name).'.pdf');
    		if(!$invoice->upo){
    			$upo = Ksef::invoiceUpo($invoice->referenceNumber,$invoice->invoiceReferenceNumber);
    			if(Input::segment(6)) DB::table('eprog_manager_invoice')->where('id', Input::segment(6))->update(['upo' => $upo]);
    			$invoice->upo = $upo;
    			$pdf = KSef::generateUpoPdf($invoice); 
    			return $file;   
    		}
    		else{
    			$pdf = KSef::generateUpoPdf($invoice); 
    			file_put_contents($file, $pdf);  
    			return $file;   	
    		}

    	}
    	else
    		Flash::error(e(trans('eprog.manager::lang.ksef.download_error'))); 

    }


    public static function number($type)
    {

	    	$types = ["VAT","KOR","ZAL","ROZ","UPR","KOR_ZAL","KOR_ROZ"];

	    	$invoice_type = SettingNumeration::get("invoice_type") ?? "month";

	    	$prefix = SettingNumeration::get("invoice_prefix") ?? "";
	    	$separator = SettingNumeration::get("invoice_separator") ?? "/";
	    	$sufix = SettingNumeration::get("invoice_sufix") ?? "";

	        $max = ModelInvoice::where("type","=",$type)->where("nr","!=","")->orderBy("id","desc")->first()->nr ?? null;
	        $max = preg_replace('/^'.preg_quote($prefix, '/').'/', '',$max);
	        $max = preg_replace('/'.preg_quote($sufix, '/'). '$/', '',$max);


	        if(isset($max)){
	            $max = explode($separator, $max);
	            settype($max[1], "integer");
	        }

	        $invoice_number = "";
	        $month = Util::invdate(Carbon::now(), 3);
	        $year = Util::invdate(Carbon::now(), 4);

	        if($invoice_type == "month"){
		        if(isset($max[2]) && $max[2] == $month)
		        	$number = Util::zero_first(++$max[1]);
		    	else
		    		$number = "01";

		        $invoice_number =  $prefix.$types[$type].$separator.$number.$separator.$month.$separator.$year.$sufix;
	    	}

		    if($invoice_type == "year"){
		        if(isset($max[2]) && $max[2] == $year)
		        	$number = Util::zero_first(++$max[1]);
		    	else
		    		$number = "01";

		        $invoice_number =  $prefix.$types[$type].$separator.$number.$separator.$year.$sufix;
			}


	        return $invoice_number;

    }
    

}