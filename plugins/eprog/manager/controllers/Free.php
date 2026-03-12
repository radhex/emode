<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Eprog\Manager\Models\Invoice as ModelInvoice;
use Eprog\Manager\Models\SettingConfig;
use Eprog\Manager\Models\SettingInvoice;
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
use BackendAuth;
use Winter\Storm\Flash\FlashBag;

class Free extends Controller
{


	public $implement = ['Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $kor = 0;
    public $xml = null;
    public $referenceNumber = "";
    public $ksefNumber = "";
    public $name = "free";

    public $requiredPermissions = ['eprog.manager.access_free'];


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

    	//$model = $this->formGetModel();

    	//$this->asExtension('FormController')->formValidate($model);
    	

    	return false;
      
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

    	if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_free')) return;

    	if(!isset($_SESSION)) session_start();
 
    	$xml = null;
    	$form->getField('kor')->hidden = true;
    	$form->getField('_kor_reason')->hidden = true;  
    	$form->getField('_kor_type')->hidden = true;
    	/*
    	$form->getField('buyer_nip')->value = "5260309174";
    	$form->getField('buyer_name')->value = "Auchan Sp. z o.o.";
    	$form->getField('buyer_adres1')->value = "ul. Puławska 46";
    	$form->getField('buyer_adres2')->value = "05-500 Piaseczno";
		*/
    	$form->getField('seller_nip')->value = Session::get("ksef.nip") ?? '';


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
		
			

			$form->getField('currency')->value = SettingConfig::get("crrency") ?? 'PLN';
			$form->getField('exchange')->value = 1;
			/*			
			$form->getField('seller_name')->value = SettingConfig::get("firm");
			$form->getField('seller_nip')->value = SettingConfig::get("nip");
			$form->getField('seller_adres1')->value = "ul. ".SettingConfig::get("street")." ".SettingConfig::get("number");
			$form->getField('seller_adres2')->value = SettingConfig::get("code")." ".SettingConfig::get("city");
			$form->getField('seller_country')->value = "PL";
			*/

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
					$form->getField('currency')->value = $invoice->currency;
					$form->getField('exchange')->value = $invoice->exchange;
				}
			}


		}

		if(SettingInvoice::get("block_number")) $form->getField('nr')->readOnly = true;

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

				$form->getField('exchange')->value = $data["Fa"]["FaWiersz"][0]['KursWaluty'] ?? $form->getField('exchange')->value;

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
            foreach ($checkedIds as $Id) {           
                switch ($bulkAction) {
                    case 'delete':
                        $message = $this->connect()->query()->getMessageByUid($Id)->move(config("imap.folders.trash.action"));
                        break;

                    case 'offline':
                        $ids[] = $Id;
                        break;
                }


            }

            if(sizeof($ids) > 0) self::onKsefSendMultiple($ids);
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

    public function onKsefSend()
    {


    	$faktura = Ksef::xml(); 
    	$xml = Ksef::verifyXml($faktura);
    	$result = Ksef::invoiceSend($xml);

    	if(isset($result['ksefNumber'])){    

    		Session::put("free.ksefNumber", $result['ksefNumber']);	 
    		Session::put("free.referenceNumber", $result['referenceNumber']);	
    		Session::put("free.invoiceReferenceNumber", $result['invoiceReferenceNumber']);	
    		Session::put("free.xml", $xml);	 

			return ["ksefNumber" => $result['ksefNumber']];
    


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
			    		$invoice->where("nr","=",$doc["NumerFaktury"])->update(["ksefNumber" => $doc["NumerKSeFDokumentu"], "upo" => $upo, "ksef_at" => date("Y-m-d H:i",strtotime($doc["DataPrzeslaniaDokumentu"]))]);
				}
				else{
					$doc = $data['Dokument'];
					$invoice->where("nr","=",$doc["NumerFaktury"])->update(["ksefNumber" => $doc["NumerKSeFDokumentu"], "upo" => $upo, "ksef_at" => date("Y-m-d H:i",strtotime($doc["DataPrzeslaniaDokumentu"]))]);
				}
		  
		    	Flash::success(Lang::get('eprog.manager::lang.ksef.sendmultiple_success'));
	    	}
	    	
    	}

    
    }

    public function onKsefXml()
    {

    	  $ksefNumber = Session::get("free.ksefNumber");
    	  $xml = Session::get("free.xml");
    	  if($ksefNumber){	    		
    			$file = storage_path('temp/public/xml_'.$ksefNumber);
    			file_put_contents($file, $xml);  
    			if(file_exists($file))
    			return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getInvoiceXml/'.$ksefNumber);	
    	  }
    	  else
    	  	Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));       	  	
		
    }


    public function onKsefPdf()
    {

	    $ksefNumber = Session::get("free.ksefNumber");
	    $xml = Session::get("free.xml");
	    if($ksefNumber){	
			$file = storage_path('temp/public/pdf_'.$ksefNumber);			
			$invoice = new ModelInvoice();
			$invoice->ksefNumber = $ksefNumber;
			$invoice->xml = $xml;
			$pdf = KSef::generateInvoicePdf($invoice);
			file_put_contents($file, $pdf);  
			if(file_exists($file))
			return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getInvoicePdf/'.$ksefNumber);		
	    }
	    else
	    	Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));

    }


    public function onKsefUpo()
    {

    	$ksefNumber = Session::get("free.ksefNumber");
    	$xml = Session::get("free.xml");
    	if($ksefNumber){	    		
    		$file = storage_path('temp/public/upo_'.$ksefNumber);
    		if(!Session::get("free.upo")){
    			$upo = Ksef::invoiceUpo(Session::get("free.referenceNumber") ?? '',Session::get("free.invoiceReferenceNumber") ?? '');
    			if($upo)
    			Session::put("free.upo", $upo);	 
    		}
    		else
    			$upo = Session::get("free.upo");
    		$invoice = new ModelInvoice();
    		$invoice->ksefNumber = $ksefNumber;
    		$invoice->xml = $xml;
    		$invoice->upo = $upo;
    		$pdf = KSef::generateUpoPdf($invoice); 
    		file_put_contents($file, $pdf);  
    		if(file_exists($file))
    		return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getInvoiceUpo/'.$ksefNumber);	
    	}
    	else
    		Flash::error(e(trans('eprog.manager::lang.ksef.download_error'))); 


    	/*
			$invoice = ModelInvoice::find(Input::segment(6));  
			if($invoice && $invoice->ksefNumber){	    		
				$file = storage_path('temp/public/upo_'.$invoice->ksefNumber);
				$upo = Ksef::invoiceUpo($invoice->referenceNumber,$invoice->invoiceReferenceNumber);
				file_put_contents($file, $upo);  
				if(file_exists($file))
				return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getInvoiceUpo/'.$invoice->ksefNumber);	
			}
			else
				Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));   


    	    $invoice = ModelInvoice::find(Input::segment(6));  
    	    if($invoice && $invoice->ksefNumber){	    	
    			$html = Ksef::invoiceUpo(file_get_contents("invoices/".$invoice->ksefNumber."/upo.xml"));
    			$file = storage_path('temp/public/upo_'.$invoice->ksefNumber);
    			$pdf = SnappyPdf::loadHTML($html)->output(); 
    			file_put_contents($file, $pdf);  
    			if(file_exists($file))
    			return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getInvoiceUpo/'.$invoice->ksefNumber);		
    	    }
    	    else
    	    	Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));


		  $invoice = ModelInvoice::find(Input::segment(6));  
		  if($invoice && $invoice->ksefNumber){	    		
				$file = storage_path('temp/public/upo_'.$invoice->ksefNumber);
				file_put_contents($file, file_get_contents("invoices/".$invoice->ksefNumber."/upo.xml"));  
				if(file_exists($file))
				return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getInvoiceUpo/'.$invoice->ksefNumber);	
		  }
		  else
		  	Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));
		  	*/

    }


    public static function number($type)
    {

	    	$types = ["VAT","KOR","ZAL","ROZ","UPR","KOR_ZAL","KOR_ROZ"];

	    	$invoice_type = SettingInvoice::get("type") ?? "month";

	    	$prefix = SettingInvoice::get("prefix") ?? "";
	    	$separator = SettingInvoice::get("separator") ?? "/";
	    	$sufix = SettingInvoice::get("sufix") ?? "";

	        $max = ModelInvoice::where("type","=",$type)->orderBy("id","desc")->first()->nr ?? null;
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