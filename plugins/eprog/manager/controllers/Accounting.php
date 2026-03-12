<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Eprog\Manager\Models\Accounting as ModelAccounting;
use Eprog\Manager\Models\SettingAccounting;
use Eprog\Manager\Models\SettingConfig;
use Eprog\Manager\Classes\Util;
use Eprog\Manager\Classes\Jpk;
use Eprog\Manager\Models\Invoice;
use Eprog\Manager\Models\Invoicevalue;
use Eprog\Manager\Models\Internal;
use Eprog\Manager\Models\Advance;
use Eprog\Manager\Models\Internalvalue;
use Eprog\Manager\Models\Vat;
use Eprog\Manager\Controllers\Taxfile;
use Rainlab\User\Models\User;
use Eprog\Manager\Models\SettingStatus;
use October\Rain\Exception\ValidationException;
use ApplicationException;
use System\Models\File;
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
use Mpdf\Mpdf;


class Accounting extends Controller
{


    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $kor = 0;
    public $xml = null;
    public $referenceNumber = "";
    public $ksefNumber = "";

    public $requiredPermissions = ['eprog.manager.access_accounting'];


    public function __construct()
    {

        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'accounting');

        
    }

    public function index()
    {

        $this->addJs('/plugins/rainlab/user/assets/js/bulk-actions.js');
        $this->asExtension('ListController')->index();
    }


    public function listExtendQuery($query, $definition = null){

    

        $year = Session::has("selected.year") ? Session::get("selected.year") : date("Y",time());
        $month = Session::has("selected.month") ? Session::get("selected.month") : date("m",time());
        $nip = Session::has("selected.nip") ? Session::get("selected.nip") : SettingConfig::get("nip"); 
 
        if($year && $month && $nip)
            $query->where("year", $year)->where("month", $month)->where("nip", $nip);  
        else 
            $query->where("id",0); 

        $query->orderByRaw('CAST(lp AS UNSIGNED) DESC');


    }

    public function formExtendFields($form)
    {

        Util::checkExpired();
        Util::checkCapacity();

        if(!isset($_SESSION)) session_start();
 

        if(Input::segment(5) == "update") { 
  
            $accounting = ModelAccounting::find(Input::segment(6));   
            if(!$accounting->xml){
        
                $form->getField('nr_ksef')->hidden = true;

            }
            else
                $form->getField('selltype')->hidden = true;

            if($accounting->mode){
                $form->getField('selltype')->hidden = true;
       
            }
        }

        if(Input::segment(5) == "create") { 
            
            $year = Session::has("selected.year") ? Session::get("selected.year") : date("Y",time());
            $month = Session::has("selected.month") ? Session::get("selected.month") : date("m",time());
            $nip = Session::has("selected.nip") ? Session::get("selected.nip") : SettingConfig::get("nip"); 

            $nip = Session::get("selected.nip") ?? '';
            $firm = User::where("firm_nip",$nip)->first() ?? '';
            if(!$firm && $nip != SettingConfig::get("nip"))  throw new ApplicationException('Nieprawidłowy NIP');           
  
     
            $form->getField('year')->value =  $year;
            $form->getField('month')->value =  $month ; 
            $form->getField('currency')->value = SettingConfig::get("currency") ?? 'PLN';
    
            $form->getField('nr_ksef')->hidden = true;

            $lp =  ModelAccounting::where("year", $year)->where("month", $month)->where("nip", $nip)->orderBy("lp","desc")->first()->lp ?? 0;  
            $form->getField('lp')->value = $lp+1;
            $form->getField('prefix')->value = "FV";

            $form->getField('tax_form')->value = SettingConfig::get("tax_form") ?? '';


            if(Input::has("invoice_id")){
               
                $invoice = Invoice::find(Input::get("invoice_id"));
                if($invoice){
                    $year = date("Y", strtotime($invoice->make_at));
                    $month = date("n", strtotime($invoice->make_at));
                    $lp =  ModelAccounting::where("year", $year)->where("month", $month)->where("nip", $nip)->orderBy("lp","desc")->first()->lp ?? 0;  
                    $form->getField('lp')->value = $lp+1;
                    $form->getField('year')->value = $year; 
                    $form->getField('month')->value = $month;
                    $form->getField('nr')->value = $invoice->nr;
                    $form->getField('create_at')->value = $invoice->make_at;
                    $form->getField('vat_at')->value = $invoice->make_at;
                    $form->getField('user_id')->value = $invoice->user_id;
                    $form->getField('client_name')->value = $invoice->buyer_name;
                    $form->getField('client_nip')->value = $invoice->buyer_nip;
                    $form->getField('client_adres1')->value = $invoice->buyer_adres1;
                    $form->getField('client_adres2')->value = $invoice->buyer_adres2;
                    $form->getField('client_country')->value = $invoice->buyer_country;

                    $values = Invoicevalue::where("invoice_id",$invoice->id)->get();
                    
                    $desc = "";
                    $n23 = 0; $n8 = 0; $n5= 0; $n0= 0 ;$nzw = 0; $nnp = 0;$nma = 0;
                    $v23 = 0; $v8 = 0; $v5= 0; $v0= 0 ;$vzw = 0; $vnp = 0;$vma = 0;
                    $b23 = 0; $b8 = 0; $b5= 0; $b0= 0 ;$bzw = 0; $bnp = 0;$bma = 0;

                    foreach($values as $value){
                     
                        if($value->netto){
                            if($value->vat == "23"){ 
                                $n23 += round($value->netto*$value->quantity*($value->exchange ?? 1),2);
                                $v23 += round($value->netto*$value->quantity*($value->exchange ?? 1)*23/100,2);
                                $b23 =  $n23 + $v23; 
                            }
                            if($value->vat == "8"){ 
                                $n8 += round($value->netto*$value->quantity*($value->exchange ?? 1),2); 
                                $v8 += round($value->netto*$value->quantity*($value->exchange ?? 1)*8/100,2); 
                                $b8 =  $n8 + $v8; 
                            }
                            if($value->vat == "5"){ 
                                $n5 += round($value->netto*$value->quantity*($value->exchange ?? 1),2); 
                                $v5 += round($value->netto*$value->quantity*($value->exchange ?? 1)*5/100,2); 
                                $b5 =  $n5 + $v5; 
                            }

                            if(in_array($value->vat,["0 KR","0 WDT","0 EX"])) $n0 += $value->netto*$value->quantity*($value->exchange ?? 1); 
                            if(in_array($value->vat,["zw","oo"])) $nzw += $value->netto*$value->quantity*($value->exchange ?? 1);
                            if(in_array($value->vat,["np I","np II"])) $nnp += $value->netto*$value->quantity*($value->exchange ?? 1);
                            if($value->vat == "ma") $nma += $value->netto*$value->quantity*($value->exchange ?? 1);  
                            $b0 = $n0;  $bzw = $nzw;  $bnp = $nnp; $ma = $nma; 
                        } 
                        if($value->brutto){
                            if($value->vat == "23"){ 
                                $b23 += round($value->brutto*$value->quantity*($value->exchange ?? 1),2);
                                $v23 += round($value->brutto*$value->quantity*($value->exchange ?? 1)*23/(100 + 23),2);
                                $n23 =  $b23 - $v23; 
                            }
                            if($value->vat == "8"){ 
                                $b8 += round($value->brutto*$value->quantity*($value->exchange ?? 1),2);
                                $v8 += round($value->brutto*$value->quantity*($value->exchange ?? 1)*8/(100 + 8),2);
                                $n8 =  $b8 - $v8; 
                            }
                            if($value->vat == "5"){ 
                                $b5 += round($value->brutto*$value->quantity*($value->exchange ?? 1),2);
                                $v5 += round($value->brutto*$value->quantity*($value->exchange ?? 1)*5/(100 + 5),2);
                                $n5 =  $b5 - $v5; 
                            }

                            if(in_array($value->vat,["0 KR","0 WDT","0 EX"])) $b0 += $value->brutto*$value->quantity*($value->exchange ?? 1); 
                            if(in_array($value->vat,["zw","oo"])) $bzw += $value->brutto*$value->quantity*($value->exchange ?? 1);
                            if(in_array($value->vat,["np I","np II"])) $bnp += $value->brutto*$value->quantity*($value->exchange ?? 1);
                            if($value->vat == "ma") $bma += $value->brutto*$value->quantity*($value->exchange ?? 1);  
                            $n0 = $b0;  $nzw = $bzw;  $nnp = $bnp; $ma = $bma;
                        } 
                    }
             
                    $this->vars['data_document'] = [
                        'vat_summary' => [
                            'net_23' => $n23,
                            'vat_23' => $v23,
                            'gross_23' => $b23,
                            'net_8' => $n8,
                            'vat_8' => $v8,
                            'gross_8' => $b8,
                            'net_5' => $n5,
                            'vat_5' => $v5,
                            'gross_5' => $b5,
                            'net_0'  => $n0,
                            'vat_0'  => 0,
                            'gross_0'  => $b0,
                            'net_zw' => $nzw,
                            'vat_zw'  => 0,
                            'gross_zw'  => $bzw,
                            'net_np' => $nnp,
                            'vat_np'  => 0,
                            'gross_np'  => $bnp,
                        ]
                    ];
                    $this->vars['data_document']['describe'] = $values[0]->product ?? '';
                    $this->vars['data_document']['margin'] = $ma;
                }

          }

          if(Input::has("internal_id")){
                $internal = Internal::find(Input::get("internal_id"));
           
                if($internal){
                    $year = date("Y", strtotime($invoice->create_at));
                    $month = date("n", strtotime($invoice->create_at));
                    $lp =  ModelAccounting::where("year", $year)->where("month", $month)->where("nip", $nip)->orderBy("lp","desc")->first()->lp ?? 0;  
                    $form->getField('nr')->value = $internal->nr;
                    $form->getField('create_at')->value = $internal->create_at;
                    $form->getField('vat_at')->value = $internal->create_at;
                    $amount = 0;
                    $values = Internalvalue::where("internal_id",$internal->id)->get();

                    foreach($values as $value)
                        $amount += round($value->amount*$value->quantity,2); 
            
                    $this->vars['data_document'] = [
                        'kpir' => [
                            'other_income' => $internal->record == 10 ? $amount : 0,
                            'side_costs' => $internal->record == 13 ? $amount : 0,
                            'remuneration"' => $internal->record == 14 ? $amount : 0,
                            'other_expenses' => $internal->record == 15 ? $amount : 0,
                            'other' => $internal->record == 17 ? $amount : 0,
                            'research' => $internal->record == 18 ? $amount : 0,

                        ]
                    ];
                    $this->vars['data_document']['describe'] = $values[0]->product ?? '';
         
                }
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



    }

    public function listFilterExtendScopes($scope)
    {


    
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
            $ids['approve'] = [];
            $ids['undo'] = [];
            foreach ($checkedIds as $Id) {           
                switch ($bulkAction) {
                    case 'delete':
                        $ids['delete'][] = $Id;
                        break;
                    case 'approve':
                        $ids['approve'][] = $Id;
                        break;
                    case 'undo':
                        $ids['undo'][] = $Id;
                        break;
                }
            }

            if(sizeof($ids['delete']) > 0) self::onDeleteMultiple($ids['delete']);
            if(sizeof($ids['approve']) > 0) self::onApproveMultiple($ids['approve']);
            if(sizeof($ids['undo']) > 0) self::onUndoMultiple($ids['undo']);

        }
        else {
            Flash::error(Lang::get('eprog.manager::lang.selected_empty'));
        }

        return $this->listRefresh();
    }


    public function onDeleteMultiple($ids)
    {

         $del = ModelAccounting::whereIn("id",$ids)->delete();
         if($del){
            DB::statement("update eprog_manager_ksef set accounting = null where accounting in (".implode(",",$ids).")");
            Flash::success(Lang::get('eprog.manager::lang.del_selected'));
         } 
        
    }

    public function onApproveMultiple($ids)
    {

         $app = ModelAccounting::whereIn("id",$ids)->update(["approve" => 1]);
         if($app) Flash::success(Lang::get('eprog.manager::lang.approve_success'));
        
    }

    public function onUndoMultiple($ids)
    {

         $app = ModelAccounting::whereIn("id",$ids)->update(["approve" => null]);
         if($app) Flash::success(Lang::get('eprog.manager::lang.approve_undosuccess'));
        
    }


    public function onExport()
    {
    
        $url = config('cms.backendUri')."/eprog/manager/export/accountingxml";
        $arg = "";
        if(isset($_POST['checked'])) $arg .= "&checked=".implode(",",$_POST['checked']);
        if(isset($_POST['year'])) $arg .= "&year=".$_POST['year'];
        if(isset($_POST['month'])) $arg .= "&month=".$_POST['month'];
        if(isset($_POST['nip'])) $arg .= "&nip=".$_POST['nip'];
        if(strlen($arg) > 0) $arg[0] = "?";
        return Redirect::to($url.$arg);

    }
    public function onJpk()
    {
        $year = post("year");
        $month = post("month");
        $nip = post("nip");
        $jpk = post("jpk");

        if($jpk == "jpkv7m")
            return self::pkV7M($year,$month,$nip);

    }


    public static function pkV7M($year,$month, $nip)
    {

        $wersja =  "JPK_V7M (2)"; 
        $wariant = 2;
        $kodvat = "VAT-7 (22)";
        $wariantvat = 22;
        $xmlnsetd = "http://crd.gov.pl/xml/schematy/dziedzinowe/mf/2021/06/08/eD/DefinicjeTypy/";
        $xmlns = "http://crd.gov.pl/wzor/2021/12/27/11148/";
        if($year >= 2026 && $month > 1){
            $wersja =  "JPK_V7M (3)"; 
            $wariant = 3;
            $kodvat = "VAT-7 (23)";
            $wariantvat = 23;
            $xmlnsetd = "http://crd.gov.pl/xml/schematy/dziedzinowe/mf/2022/09/13/eD/DefinicjeTypy/";
            $xmlns = "http://crd.gov.pl/wzor/2025/12/19/14090/";
        }

        if(SettingConfig::get("nip") == $nip){
            $taxtype = SettingConfig::get("tax_type") ?? 0;
            $name =  SettingConfig::get("name") ?? "";
            $surname =  SettingConfig::get("surname") ?? "";
            $birthday =  SettingConfig::get("birthday") ?? "";
            $email =  SettingConfig::get("email") ?? "";
            $firm =  SettingConfig::get("firm") ?? "";
            $taxoffice = SettingConfig::get("tax_office") ?? "";
        }  
        else{
            $user = User::where("firm_nip",$nip)->first();
            $taxtype = $user->tax_type ?? 0;
            $name =  $user->name ?? "";
            $surname =  $user->surname ?? "";
            $birthday =   $user->birthday ?? "";
            $email =   $user->email ?? "";
            $firm =  $user->firm_name  ?? "";
            $taxoffice = $user->tax_office ?? "";            
        }

        if($name == "" || $surname == "" || $birthday == "" || $firm == "")
        throw new ValidationException(['my_field'=>$year]);

        $sells = ModelAccounting::where("year",$year)->where("month",$month)->where("nip",$nip)->where("approve",1)->where("mode",0)->where("vat_register",">",0)->orderBy('lp','asc')->get();
        $buys = ModelAccounting::where("year",$year)->where("month",$month)->where("nip",$nip)->where("approve",1)->where("mode",1)->where("vat_register",">",0)->orderBy('lp','asc')->get();

        $jpk = new \DOMDocument('1.0', 'UTF-8'); $jpk->preservWhiteSpace = true;
        $jpk->formatOutput = true; 

        $root = $jpk->appendChild($jpk->createElement("JPK"));
        $attr1 = $jpk->createAttribute('xmlns:etd');
        $attr1->value = $xmlnsetd;
        $root->appendChild($attr1);
        $attr2 = $jpk->createAttribute('xmlns');
        $attr2->value = $xmlns;
        $root->appendChild($attr2);

        $naglowek = $root->appendChild($jpk->createElement("Naglowek"));

        $KodFormularza = $naglowek->appendChild($jpk->createElement("KodFormularza","JPK_VAT"));
        $kodSystemowy = $jpk->createAttribute('kodSystemowy');
        $kodSystemowy->value = $wersja;
        $KodFormularza->appendChild($kodSystemowy);
        $wersjaSchemy = $jpk->createAttribute('wersjaSchemy');
        $wersjaSchemy->value = "1-0E";
        $KodFormularza->appendChild($wersjaSchemy);

        $naglowek->appendChild($jpk->createElement("WariantFormularza",$wariant));
        $naglowek->appendChild($jpk->createElement("DataWytworzeniaJPK",date("Y-m-d",time())."T".date("H:i:s",time())));
        $naglowek->appendChild($jpk->createElement("NazwaSystemu","Emode"));
        $celzlozenia = $naglowek->appendChild($jpk->createElement("CelZlozenia","1"));
        $poz = $jpk->createAttribute('poz');
        $poz ->value = "P_7";
        $celzlozenia->appendChild($poz);
        $naglowek->appendChild($jpk->createElement("KodUrzedu",$taxoffice));
        $naglowek->appendChild($jpk->createElement("Rok",$year));
        $naglowek->appendChild($jpk->createElement("Miesiac",sprintf("%02d", $month)));

        $podmiot1 = $root->appendChild($jpk->createElement("Podmiot1"));
        $rola = $jpk->createAttribute('rola');
        $rola->value = "Podatnik";
        $podmiot1->appendChild($rola);

        if(!$taxtype){ 
            $osoba = $podmiot1->appendChild($jpk->createElement("OsobaFizyczna"));
            $osoba->appendChild($jpk->createElement("etd:NIP",$nip));
            $osoba->appendChild($jpk->createElement("etd:ImiePierwsze",$name));
            $osoba->appendChild($jpk->createElement("etd:Nazwisko",$surname));
            $osoba->appendChild($jpk->createElement("etd:DataUrodzenia",$birthday));
        }
        else{
            $osoba = $podmiot1->appendChild($jpk->createElement("OsobaNiefizyczna"));
            $osoba->appendChild($jpk->createElement("NIP",$nip));
            $osoba->appendChild($jpk->createElement("PelnaNazwa",$firm));
        }
        $email = $osoba->appendChild($jpk->createElement("Email",$email));

        $deklaracja = $root->appendChild($jpk->createElement("Deklaracja"));

        $naglowek = $deklaracja->appendChild($jpk->createElement("Naglowek"));
        $kodformularza = $naglowek->appendChild($jpk->createElement("KodFormularzaDekl","VAT-7"));
        $kodsystemowy = $jpk->createAttribute('kodSystemowy');
        $kodsystemowy->value = $kodvat;
        $kodformularza->appendChild($kodsystemowy);
        $kodpodatku = $jpk->createAttribute('kodPodatku');
        $kodpodatku->value = "VAT";
        $kodformularza->appendChild($kodpodatku);
        $rodzajzobowiazania = $jpk->createAttribute('rodzajZobowiazania');
        $rodzajzobowiazania->value = "Z";
        $kodformularza->appendChild($rodzajzobowiazania);
        $wersjaschemy = $jpk->createAttribute('wersjaSchemy');
        $wersjaschemy->value = "1-0E";
        $kodformularza->appendChild($wersjaschemy);
        $wariantformularza = $naglowek->appendChild($jpk->createElement("WariantFormularzaDekl",$wariantvat));

        $ewidencja = $jpk->createElement('Ewidencja');

        for ($i = 10; $i <= 69; $i++) ${"p".$i} = 0;
        $p360 = 0;

        $lp = 1;
        $sumnetto = 0; $sumvat = 0;
        foreach($sells as $sell){

            for($i = 10; $i <= 36; $i++) 
            ${"k".$i} = 0;
            $k360 = 0;
            
            $gtus = json_decode($sell->data_document,true)['gtu'] ?? [];
            $procs = json_decode($sell->data_document,true)['proc'] ?? [];
            $vats = json_decode($sell->data_document,true)['vat_summary'] ?? [];
            $marza = json_decode($sell->data_document,true)['margin'] ?? "";
            $correct = json_decode($sell->data_document,true)['correct'] ?? 0;
            $type = Vat::find($sell->vat_register)->type ?? 1;

            $sprzedaz = $ewidencja->appendChild($jpk->createElement("SprzedazWiersz"));
            $sprzedaz->appendChild($jpk->createElement("LpSprzedazy",$lp));
            $sprzedaz->appendChild($jpk->createElement("KodKrajuNadaniaTIN","PL"));
            
            if($sell->selltype  != 1)
                $sprzedaz->appendChild($jpk->createElement("NrKontrahenta",$sell->client_nip));
            else
                $sprzedaz->appendChild($jpk->createElement("NrKontrahenta","0000000000"));
            $sprzedaz->appendChild($jpk->createElement("NazwaKontrahenta",htmlspecialchars(substr($sell->client_name,0,255), ENT_XML1 | ENT_QUOTES, 'UTF-8')));
            $sprzedaz->appendChild($jpk->createElement("DowodSprzedazy",$sell->prefix ? $sell->prefix." ".$sell->nr : $sell->nr));
            $sprzedaz->appendChild($jpk->createElement("DataWystawienia",date("Y-m-d",strtotime($sell->create_at))));
            if($sell->create_at != $sell->vat_at)
            $sprzedaz->appendChild($jpk->createElement("DataSprzedazy",date("Y-m-d",strtotime($sell->vat_at))));
    
            if($wariant > 2){
                if($sell->nr_ksef)
                $sprzedaz->appendChild($jpk->createElement("NrKSeF",$sell->nr_ksef));
                else{
                    if($sell->off)
                        $sprzedaz->appendChild($jpk->createElement("OFF","1"));
                    else{
                        if($sell->selltype  == 1)
                            $sprzedaz->appendChild($jpk->createElement("DI","1"));
                        else
                            $sprzedaz->appendChild($jpk->createElement("BFK","1"));
                    }
                }
            }

            if($sell->selltype  == 1) $sprzedaz->appendChild($jpk->createElement("TypDokumentu","RO"));
            if($sell->selltype  == 2) $sprzedaz->appendChild($jpk->createElement("TypDokumentu","WEW"));
            if($sell->selltype  == 3) $sprzedaz->appendChild($jpk->createElement("TypDokumentu","FP"));

            foreach($gtus as $k => $v) $sprzedaz->appendChild($jpk->createElement($k,"1"));
            foreach($procs as $k => $v){
                if($k == "MR_T") $p63 = 1; 
                if($k == "MR_UZ") $p64 = 1; 
                $sprzedaz->appendChild($jpk->createElement($k,"1"));
            }
        
            $base = $vats['net_zw'] + $vats['net_0'] + $vats['net_5'] + $vats['net_8'] + $vats['net_23'];
            $basetax = $vats['vat_5'] + $vats['vat_8'] + $vats['vat_23'];
            $sumnetto += $base;
            $sumvat += $basetax;

            if($type == 1) $k10 = $vats['net_zw'];
            if($type == 15) $k11 = $base;
            if($type == 17) $k12 = $base;
            if($type == 1) $k13 = $vats['net_0'];
            if($type == 19) $k14 = $base;
            if($type == 1) $k15 = $vats['net_5'];
            if($type == 1) $k16 = $vats['vat_5'];
            if($type == 1) $k17 = $vats['net_8'];
            if($type == 1) $k18 = $vats['vat_8'];
            if($type == 1) $k19 = $vats['net_23'];
            if($type == 1) $k20 = $vats['vat_23'];
            if($type == 7) $k21 = $base;
            if($type == 8) $k22 = $base;
            if($type == 9) { $k23 = $base; $k24 = $basetax;}
            if($type == 16) {$k25 = $base; $k26 = $basetax;}
            if($type == 10) {$k27 = $base; $k28 = $basetax;}
            if($type == 18) {$k29 = $base; $k30 = $basetax;}
            if($type == 12) {$k31 = $base; $k32 = $basetax;}
            if($type == 20) $k33 = $basetax;
            if($type == 21) $k34 =  $base;
            if($type == 22) $k35 = $basetax;
            if($type == 23) $k36 = $basetax;
            if($type == 28) $k360 = $basetax;

            $sumvat += $k16 + $k18 + $k20 + $k24 + $k26 + $k28 + $k30 + $k32 + $k33 + $k34 - $k35 - $k36 - $k360;

            $p10 += $k10;
            $p11 += $k11;
            $p12 += $k12;
            $p13 += $k13;
            $p14 += $k14;
            $p15 += $k15;
            $p16 += $k16;
            $p17 += $k17;
            $p18 += $k18;
            $p19 += $k19;
            $p20 += $k20;
            $p21 += $k21;
            $p22 += $k22;
            $p23 += $k23;
            $p24 += $k24;
            $p25 += $k25;
            $p26 += $k26;
            $p27 += $k27;
            $p28 += $k28;
            $p29 += $k29;
            $p30 += $k30;
            $p31 += $k31;
            $p32 += $k32;
            $p33 += $k33;
            $p34 += $k34;
            $p35 += $k35;
            $p36 += $k36;
            if($wariant > 2) $p360 += $k360;

            if($correct){
                $p68 += $base; $p69 += $basetax;
                $sprzedaz->appendChild($jpk->createElement("KorektaPodstawyOpodt","1"));
                $sprzedaz->appendChild($jpk->createElement("TerminPlatnosci",date("Y-m-d",strtotime($sell->create_at))));
            }
            if($k10 != 0) $sprzedaz->appendChild($jpk->createElement("K_10", number_format(round($k10,2),2,".","")));
            if($k11 != 0) $sprzedaz->appendChild($jpk->createElement("K_11", number_format(round($k11,2),2,".","")));
            if($k12 != 0) $sprzedaz->appendChild($jpk->createElement("K_12", number_format(round($k12,2),2,".","")));
            if($k13 != 0) $sprzedaz->appendChild($jpk->createElement("K_13", number_format(round($k13,2),2,".","")));
            if($k14 != 0) $sprzedaz->appendChild($jpk->createElement("K_14", number_format(round($k14,2),2,".","")));
            if($k15 != 0) $sprzedaz->appendChild($jpk->createElement("K_15", number_format(round($k15,2),2,".","")));
            if($k16 != 0 || $k16 == 0 && $k15 != 0) $sprzedaz->appendChild($jpk->createElement("K_16", number_format(round($k16,2),2,".","")));
            if($k17 != 0) $sprzedaz->appendChild($jpk->createElement("K_17", number_format(round($k17,2),2,".","")));
            if($k18 != 0 || $k18 == 0 && $k17 != 0) $sprzedaz->appendChild($jpk->createElement("K_18", number_format(round($k18,2),2,".","")));
            if($k19 != 0) $sprzedaz->appendChild($jpk->createElement("K_19", number_format(round($k19,2),2,".","")));
            if($k20 != 0 || $k20 == 0 && $k19 != 0) $sprzedaz->appendChild($jpk->createElement("K_20", number_format(round($k20,2),2,".","")));
            if($k21 != 0) $sprzedaz->appendChild($jpk->createElement("K_21", number_format(round($k21,2),2,".","")));
            if($k22 != 0) $sprzedaz->appendChild($jpk->createElement("K_22", number_format(round($k22,2),2,".","")));
            if($k23 != 0) $sprzedaz->appendChild($jpk->createElement("K_23", number_format(round($k23,2),2,".","")));
            if($k24 != 0 || $k24 == 0 && $k23 != 0) $sprzedaz->appendChild($jpk->createElement("K_24", number_format(round($k24,2),2,".","")));
            if($k25 != 0) $sprzedaz->appendChild($jpk->createElement("K_25", number_format(round($k25,2),2,".","")));
            if($k26 != 0 || $k26 == 0 && $k25 != 0) $sprzedaz->appendChild($jpk->createElement("K_26", number_format(round($k26,2),2,".","")));
            if($k27 != 0) $sprzedaz->appendChild($jpk->createElement("K_27", number_format(round($k27,2),2,".","")));
            if($k28 != 0 || $k28 == 0 && $k27 != 0) $sprzedaz->appendChild($jpk->createElement("K_28", number_format(round($k28,2),2,".","")));
            if($k29 != 0) $sprzedaz->appendChild($jpk->createElement("K_29", number_format(round($k29,2),2,".","")));
            if($k30 != 0 || $k30 == 0 && $k29 != 0) $sprzedaz->appendChild($jpk->createElement("K_30", number_format(round($k30,2),2,".","")));
            if($k31 != 0) $sprzedaz->appendChild($jpk->createElement("K_31", number_format(round($k31,2),2,".","")));
            if($k32 != 0 || $k32 == 0 && $k31 != 0) $sprzedaz->appendChild($jpk->createElement("K_32", number_format(round($k32,2),2,".","")));
            if($k33 != 0) $sprzedaz->appendChild($jpk->createElement("K_33", number_format(round($k33,2),2,".","")));
            if($k34 != 0) $sprzedaz->appendChild($jpk->createElement("K_34", number_format(round($k34,2),2,".","")));
            if($k35 != 0) $sprzedaz->appendChild($jpk->createElement("K_35", number_format(round($k35,2),2,".","")));
            if($k36 != 0) $sprzedaz->appendChild($jpk->createElement("K_36", number_format(round($k36,2),2,".","")));
            if($k360 != 0 && $wariant > 2) $sprzedaz->appendChild($jpk->createElement("K_360", number_format(round($k360,2),2,".","")));


            if($marza != "") $sprzedaz->appendChild($jpk->createElement("SprzedazVAT_Marza",number_format(round(floatval($marza),2),2,".","")));

            $lp++;
        }
    
        $sprzedazctrl = $ewidencja->appendChild($jpk->createElement("SprzedazCtrl"));
        $sprzedazctrl->appendChild($jpk->createElement("LiczbaWierszySprzedazy",$lp-1));
        $sprzedazctrl->appendChild($jpk->createElement("PodatekNalezny",number_format(round($sumvat,2),2,".","")));

        $p37 = round($p10) + round($p11) + round($p13) + round($p15) + round($p17) + round($p19) + round($p21) + round($p22) + round($p23) + round($p25) + round($p27)  + round($p29)  + round($p31);
        $p38 = round($p16) + round($p18) + round($p20) + round($p24) + round($p26) + round($p28) + round($p30) + round($p32) + round($p33) + round($p34) - round($p35) - round($p36) - round($p360);


        $lp = 1;
        $sumnetto = 0; $sumvat = 0;

        foreach($buys as $buy){

            $k40 = 0; $k41 = 0; $k42 = 0; $k43 = 0; $k44 = 0; $k45 = 0; $k46 = 0; $k47 = 0;

            $vats = json_decode($buy->data_document,true)['vat_summary'];
            $marza = json_decode($buy->data_document,true)['margin'] ?? "";
            $type = Vat::find($buy->vat_register)->type ?? 2;


            $net = $vats['net_23'] + $vats['net_8'] + $vats['net_5']; 
            $vat = $vats['vat_23'] + $vats['vat_8'] + $vats['vat_5'];

            if($type == 2){ $k42 = $net; $k43 = $vat; }
            if($type == 4){ $k40 = $net; $k41 = $vat; }
            if($type == 24){ $k44 = $vat; }
            if($type == 25){ $k45 = $vat; }      
            if($type == 26){ $k46 = $vat; }
            if($type == 27){ $k47 = $vat; }

            if($k40 != 0 || $k41 != 0 || $k42 != 0 || $k43 != 0 || $k44 != 0 || $k45 != 0 || $k46 != 0 || $k47 != 0 || $marza != "") {

                $zakup = $ewidencja->appendChild($jpk->createElement("ZakupWiersz"));
                $zakup->appendChild($jpk->createElement("LpZakupu",$lp));
                $zakup->appendChild($jpk->createElement("KodKrajuNadaniaTIN","PL"));
                $zakup->appendChild($jpk->createElement("NrDostawcy",$buy->client_nip));
                $zakup->appendChild($jpk->createElement("NazwaDostawcy",htmlspecialchars(substr($buy->client_name,0,255), ENT_XML1 | ENT_QUOTES, 'UTF-8')));
                $zakup->appendChild($jpk->createElement("DowodZakupu",$buy->prefix ? $buy->prefix." ".$buy->nr : $buy->nr));
                $zakup->appendChild($jpk->createElement("DataZakupu",date("Y-m-d",strtotime($buy->create_at))));
                if($buy->create_at != $buy->vat_at)
                $zakup->appendChild($jpk->createElement("DataWplywu",date("Y-m-d",strtotime($buy->vat_at))));
 
                if($wariant > 2){
                    if($buy->nr_ksef)
                    $zakup->appendChild($jpk->createElement("NrKSeF",$buy->nr_ksef));
                    else{
                        if($buy->off)
                            $zakup->appendChild($jpk->createElement("OFF","1"));
                        else{
                            if($buy->selltype  == 1)
                                $zakup->appendChild($jpk->createElement("DI","1"));
                            else
                                $zakup->appendChild($jpk->createElement("BFK","1"));
                        }
                    }
                }

                if($k40 != 0) $zakup->appendChild($jpk->createElement("K_40",number_format(round($k40,2),2,".","")));
                if($k41 != 0) $zakup->appendChild($jpk->createElement("K_41",number_format(round($k41,2),2,".","")));
                if($k42 != 0) $zakup->appendChild($jpk->createElement("K_42",number_format(round($k42,2),2,".","")));
                if($k43 != 0) $zakup->appendChild($jpk->createElement("K_43",number_format(round($k43,2),2,".","")));
                if($k44 != 0) $zakup->appendChild($jpk->createElement("K_44",number_format(round($k44,2),2,".","")));
                if($k45 != 0) $zakup->appendChild($jpk->createElement("K_45",number_format(round($k45,2),2,".","")));
                if($k46 != 0) $zakup->appendChild($jpk->createElement("K_46",number_format(round($k46,2),2,".","")));
                if($k47 != 0) $zakup->appendChild($jpk->createElement("K_47",number_format(round($k47,2),2,".","")));

                if($marza != "") $zakup->appendChild($jpk->createElement("ZakupVAT_Marza",number_format(round(floatval($marza),2),2,".","")));

                $lp++;
            }

            $p40 += $k40;
            $p41 += $k41;
            $p42 += $k42;
            $p43 += $k43;
            $p44 += $k44;
            $p45 += $k45;
            $p46 += $k46;
            $p47 += $k47;
           
        }
  
        $p48 = $p41 + $p43 + $p44 + $p45 + $p46 + $p47;
        $p51 = $p38 - $p48;

        $zakupctrl = $ewidencja->appendChild($jpk->createElement("ZakupCtrl"));
        $zakupctrl->appendChild($jpk->createElement("LiczbaWierszyZakupow",$lp-1));
        $zakupctrl->appendChild($jpk->createElement("PodatekNaliczony",number_format(round($p48,2),2,".","")));

        $pozycje = $deklaracja->appendChild($jpk->createElement("PozycjeSzczegolowe"));

        if($p10 != 0) $pozycje->appendChild($jpk->createElement("P_10", round($p10)));
        if($p11 != 0) $pozycje->appendChild($jpk->createElement("P_11", round($p11)));
        if($p12 != 0) $pozycje->appendChild($jpk->createElement("P_12", round($p12)));
        if($p13 != 0) $pozycje->appendChild($jpk->createElement("P_13", round($p13)));
        if($p14 != 0) $pozycje->appendChild($jpk->createElement("P_14", round($p14)));
        if($p15 != 0) $pozycje->appendChild($jpk->createElement("P_15", round($p15)));
        if($p16 != 0) $pozycje->appendChild($jpk->createElement("P_16", round($p16)));
        if($p17 != 0) $pozycje->appendChild($jpk->createElement("P_17", round($p17)));
        if($p18 != 0) $pozycje->appendChild($jpk->createElement("P_18", round($p18)));
        if($p19 != 0) $pozycje->appendChild($jpk->createElement("P_19", round($p19)));
        if($p20 != 0) $pozycje->appendChild($jpk->createElement("P_20", round($p20)));
        if($p21 != 0) $pozycje->appendChild($jpk->createElement("P_21", round($p21)));
        if($p22 != 0) $pozycje->appendChild($jpk->createElement("P_22", round($p22)));
        if($p23 != 0) $pozycje->appendChild($jpk->createElement("P_23", round($p23)));
        if($p24 != 0) $pozycje->appendChild($jpk->createElement("P_24", round($p24)));
        if($p25 != 0) $pozycje->appendChild($jpk->createElement("P_25", round($p25)));
        if($p26 != 0) $pozycje->appendChild($jpk->createElement("P_26", round($p26)));
        if($p27 != 0) $pozycje->appendChild($jpk->createElement("P_27", round($p27)));
        if($p28 != 0) $pozycje->appendChild($jpk->createElement("P_28", round($p28)));
        if($p29 != 0) $pozycje->appendChild($jpk->createElement("P_29", round($p29)));
        if($p30 != 0) $pozycje->appendChild($jpk->createElement("P_30", round($p30)));
        if($p31 != 0) $pozycje->appendChild($jpk->createElement("P_31", round($p31)));
        if($p32 != 0) $pozycje->appendChild($jpk->createElement("P_32", round($p32)));
        if($p33 != 0) $pozycje->appendChild($jpk->createElement("P_33", round($p33)));
        if($p34 != 0) $pozycje->appendChild($jpk->createElement("P_34", round($p34)));
        if($p35 != 0) $pozycje->appendChild($jpk->createElement("P_35", round($p35)));
        if($p36 != 0) $pozycje->appendChild($jpk->createElement("P_36", round($p36)));
        if($p360 != 0 && $wariant > 2) $pozycje->appendChild($jpk->createElement("P_360", round($p360)));
        if($p37 != 0) $pozycje->appendChild($jpk->createElement("P_37",round($p37)));
                      $pozycje->appendChild($jpk->createElement("P_38",round($p38)));

        if($p40 != 0) $pozycje->appendChild($jpk->createElement("P_40", round($p40)));
        if($p41 != 0) $pozycje->appendChild($jpk->createElement("P_41", round($p41)));
        if($p42 != 0) $pozycje->appendChild($jpk->createElement("P_42", round($p42)));
        if($p43 != 0) $pozycje->appendChild($jpk->createElement("P_43", round($p43)));
        if($p44 != 0) $pozycje->appendChild($jpk->createElement("P_44", round($p44)));
        if($p45 != 0) $pozycje->appendChild($jpk->createElement("P_45", round($p45)));
        if($p46 != 0) $pozycje->appendChild($jpk->createElement("P_46", round($p46)));
        if($p47 != 0) $pozycje->appendChild($jpk->createElement("P_47", round($p47)));

        if($p48 != 0) $pozycje->appendChild($jpk->createElement("P_48",round($p48)));
                       $pozycje->appendChild($jpk->createElement("P_51",round($p51) > 0 ? round($p51) : 0));
        if($p51 < 0)   $pozycje->appendChild($jpk->createElement("P_53",abs(round($p51))));   
        if($p51 < 0)   $pozycje->appendChild($jpk->createElement("P_62",abs(round($p51))));  
        if($p63 > 0)   $pozycje->appendChild($jpk->createElement("P_63","1"));  
        if($p64 > 0)   $pozycje->appendChild($jpk->createElement("P_64","1"));
                   
       
        if($p68 < 0) $pozycje->appendChild($jpk->createElement("P_68", round($p68)));
        if($p69 < 0) $pozycje->appendChild($jpk->createElement("P_69", round($p69)));        
        
        $deklaracja->appendChild($jpk->createElement("Pouczenia","1"));

        $root->appendChild($ewidencja);

        $xml = $jpk->saveXML(); 

        $verifyxml =  Jpk::verifyJpkXml($xml, $wersja);

        Session::flash("jpk.".$nip.".name", "JPK_V7M za ".sprintf("%02d", $month)."/".$year);
        Session::flash("jpk.".$nip.".version",$wersja);
        Session::flash("jpk.".$nip.".period", sprintf("%02d", $month)."/".$year);
        Session::flash("jpk.".$nip.".xml",$verifyxml);
        //dd($verify);
        //throw new ValidationException(['my_field'=>$verify]);
      
        return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/jpk/create');     
    
    }


    public function onPdf()
    {

        $accounting = ModelAccounting::find(Input::segment(6));  
        if($accounting && $accounting->nr){   
            $accounting->nr = str_replace("/","_",$accounting->nr);
            $xml = $accounting->xml;      
            $html = Ksef::accountingHtml($xml,$accounting->nr);
            $file = storage_path('temp/public/'.$accounting->nr);
            $pdf = SnappyPdf::loadHTML($html)->output(); 
            file_put_contents($file, $pdf);  
            if(file_exists($file))
            return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getAccountingPdf/'.$accounting->nr);     
        }
        else
            Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));
            
    }

    public function onProforma()
    {

        $accounting = ModelAccounting::find(Input::segment(6));  
        if($accounting && $accounting->nr){   
            $accounting->nr = str_replace("/","_",$accounting->nr);
            $xml = $accounting->xml;      
            $html = Ksef::accountingHtmlPro($xml,$accounting->nr);
            $file = storage_path('temp/public/'.$accounting->nr);
            $pdf = SnappyPdf::loadHTML($html)->output(); 
            file_put_contents($file, $pdf);  
            if(file_exists($file))
            return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getAccountingPdf/'.$accounting->nr);     
        }
        else
            Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));
            
    }

    public function onVat()
    {


        return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/vat');     


    }

    public function onDoc()
    {
        $file = self::onprepareDoc(post("doc"));
        return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getfile?file='.urlencode($file));
    }

    public static function onprepareDoc($doc)
    {
    
       
        if($doc == "kpir"){
            $html = self::kpir_pdf();
            $filename = trans("eprog.manager::lang.kpir").".pdf";
            $orient = "L";
        }
        if($doc == "skpir"){
            $html = self::skpir_pdf();
            $filename = trans("eprog.manager::lang.skpir").".pdf";
            $orient = "P";
        }
        if($doc == "ewp"){
            $html = self::ewp_pdf();
            $filename = trans("eprog.manager::lang.ewp").".pdf";
            $orient = "L";
        }
        if($doc == "ewpz"){
            $html = self::ewpz_pdf();
            $filename = trans("eprog.manager::lang.ewpz").".pdf";
            $orient = "L";
        }
        if($doc == "vat"){
            $html = self::vat_pdf();
            $filename = trans("eprog.manager::lang.rvat").".pdf";
            $orient = "P";
        }
        if($doc == "lump"){
            $html = self::lump_pdf();
            $filename = trans("eprog.manager::lang.slump").".pdf";
            $orient = "P";
        }


        $tempDir = storage_path('temp/public');
        if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

        ini_set('memory_limit','1024M');
        ini_set('max_execution_time',600);

        $mpdf = new Mpdf([
            'tempDir' => $tempDir,
            'format' => 'A4-'.$orient,
            'margin_top' => 10,
            'margin_bottom' => 20,
            'default_font' => 'DejaVu Sans',
        ]);

        $mpdf->WriteHTML(file_get_contents('plugins/eprog/manager/assets/css/pdf.css'), \Mpdf\HTMLParserMode::HEADER_CSS);
        $mpdf->SetHTMLFooter('<div style="font-weight:normal; font-family:Arial; font-size:8pt; border-top:none; padding-top:2mm;">Wygenerowane w Emode (emode.pl) - Strona {PAGENO} / {nbpg}</div>');
        $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);


        $file = $tempDir.'/'.$filename;
        $mpdf->Output($file,'F');

        return $file;

    }


    public static function kpir_pdf()
    {


        $year = Session::get("selected.year") ?? '';
        $month = Session::get("selected.month") ?? '';
        $nip = Session::get("selected.nip") ?? '';

        $firm = User::where("firm_nip",$nip)->first()->firm_name ?? '';
        if($nip == SettingConfig::get("nip")) $firm = SettingConfig::get("firm");

        $sells = ModelAccounting::where("year",$year)->where("month",$month)->where("nip",$nip)->where("approve",1)->orderBy('lp','asc')->get();


        $html = "<table class=\"vat\">";
        $html .= "<thead>
        <tr>
            <th colspan=\"20\" style=\"border:0;text-align:left;font-size:18px;font-weight:normal;padding-left:0px;padding-bottom:20px\">
                Podatkowa Księga Przychodów i Rozchodów (KPiR) za ".sprintf('%02d', $month)."/{$year} - {$firm} NIP: {$nip}
            </th>
        </tr>

        <tr>
            <th rowspan=\"2\">Lp.</th>
            <th rowspan=\"2\">Data</th>
            <th colspan=\"2\" style=\"text-align:center\">Oznaczenie dowodu księgowego</th>
            <th colspan=\"3\" style=\"text-align:center\">Kontrahent</th>
            <th rowspan=\"2\" style=\"width:80pt\">Opis zdarzenia gospodarczego</th>
            <th colspan=\"3\" style=\"text-align:center\">Sprzedaż</th>
            <th rowspan=\"2\" style=\"width:60pt\">Zakup towarów handlowych i materiałów według cen zakupu</th>
            <th rowspan=\"2\" style=\"width:60pt\">Koszty uboczne zakupu</th>
            <th colspan=\"4\" style=\"text-align:center\">Wydatki</th>
            <th colspan=\"2\" style=\"text-align:center\">Koszty działalności badawczo-rozwojowej</th>

            <th rowspan=\"2\" style=\"width:60pt\">Uwagi</th>
        </tr>

        <tr>
            <th style=\"width:80pt\">numer KSeF</th>
            <th>numer dowodu księgowego</th>

            <th style=\"width:60pt\">identyfikator podatkowy</th>
            <th style=\"width:110pt\">imię i nazwisko (nazwa firmy)</th>
            <th style=\"width:110pt\">adres</th>

            <th style=\"width:60pt\">wartość sprzedanych towarów i usług</th>
            <th style=\"width:60pt\">pozostałe przychody</th>
            <th style=\"width:60pt\">razem przychód (9+10)</th>

            <th style=\"width:60pt\">wynagrodzenie w gotówce i naturze</th>
            <th style=\"width:60pt\">pozostałe wydatki</th>
            <th style=\"width:60pt\">razem wydatki<br>(14 + 15)</th>
            <th style=\"width:60pt\">inne</th>

            <th style=\"width:60pt\">opis kosztu</th>
            <th style=\"width:60pt\">wartość</th>
        </tr>

        <tr>
            <th>1</th>
            <th>2</th>
            <th>3</th>
            <th>4</th>
            <th>5</th>
            <th>6</th>
            <th>7</th>
            <th>8</th>
            <th>9</th>
            <th>10</th>
            <th>11</th>
            <th>12</th>
            <th>13</th>
            <th>14</th>
            <th>15</th>
            <th>16</th>
            <th>17</th>
            <th colspan=\"2\">18</th>
            <th>20</th>
        </tr>
        </thead><tbody>";

        $l = 1;
        $t9 = 0;
        $t10 = 0;
        $t11 = 0;
        $t12 = 0;
        $t13 = 0;
        $t14 = 0;
        $t15 = 0;
        $t16 = 0;
        $t17 = 0;
        $t18b = 0;

        $summaryByRate = []; 

        foreach ($sells as $sell) {

            $doc    = json_decode($sell->data_document, true);
            $vat    = is_array($doc['vat_summary'] ?? null) ? $doc['vat_summary'] : [];
            $kpir    = is_array($doc['kpir'] ?? null) ? $doc['kpir'] : [];
            $p9 = !$sell->mode ? (float)($kpir['kpir9'] ?? 0) : 0;
            $p10 = (float)($kpir['kpir10'] ?? 0);
            $p11 = $p9 + $p10;
            $p12 = (float)($kpir['kpir12'] ?? 0);
            $p13 = (float)($kpir['kpir13'] ?? 0);
            $p14 = (float)($kpir['kpir14'] ?? 0);
            $p15 = (float)($kpir['kpir15'] ?? 0);
            $p16 = $p14 + $p15;
            $p17 = (float)($kpir['kpir17'] ?? 0);
            $p18a = $kpir['kpir18a'] ?? '';
            $p18b = (float)($kpir['kpir18b'] ?? 0);
            $p19 = $kpir['kpir19'] ?? '';


            if (!$sell->mode) {
                $t9 += $p9;
            } else {
                $t12 += $p12;
            }

            $t10 += $p10;
            $t11 += $p11;
            $t13 += $p13;
            $t14 += $p14;
            $t15 += $p15;
            $t16 += $p16;
            $t17 += $p17;
            $t18b += $p18b;


            $d9 = $p9 != 0 ? Util::dkwota($p9) : "<center>-</center>";
            $d10 = $p10 != 0 ? Util::dkwota($p10) : "<center>-</center>"; 
            $d11 = $p11 !=  0 ? Util::dkwota($p11) : "<center>-</center>"; 
            $d12  = $p12 != 0  ? Util::dkwota($p12) : "<center>-</center>";
            $d13 = $p13 !=  0 ? Util::dkwota($p13) : "<center>-</center>"; 
            $d14 = $p14 !=  0 ? Util::dkwota($p14) : "<center>-</center>"; 
            $d15 = $p15 !=  0 ? Util::dkwota($p15) : "<center>-</center>"; 
            $d16 = $p16 !=  0 ? Util::dkwota($p16) : "<center>-</center>"; 
            $d17 = $p17 !=  0 ? Util::dkwota($p17) : "<center>-</center>"; 
            $d18a = $p18a;
            $d18b = $p18b !=  0 ? Util::dkwota($p18b) : "<center>-</center>"; 
            $d19 = $p19;
    
            $clientAddress = '';
            if (!empty($sell->client_adres1)) $clientAddress .= $sell->client_adres1 . "<br>";
            if (!empty($sell->client_adres2)) $clientAddress .= $sell->client_adres2 . "<br>";

            if($p9 == 0 && $p10 == 0 && $p12 == 0 && $p13 == 0 && $p14 == 0 && $p15 == 0 && $p17 == 0 && $p18a == "" && $p18b == 0 && $p19 == "") continue;
            $html .= "<tr>
                <td style=\"text-align:center\">$l</td>
                <td>".date("Y-m-d", strtotime($sell->create_at))."</td>
                <td>{$sell->nr_ksef}</td>
                <td>".($sell->prefix ? $sell->prefix." ".$sell->nr : $sell->nr)."</td>
                <td>{$sell->client_nip}</td>
                <td>{$sell->client_name}</td>
                <td>{$clientAddress}</td>
                <td>{$doc['describe']}</td>
                <td style=\"text-align:right\">$d9</td>
                <td style=\"text-align:right\">$d10</td>
                <td style=\"text-align:right\">$d11</td>
                <td style=\"text-align:right\">$d12</td>
                <td style=\"text-align:right\">$d13</td>
                <td style=\"text-align:right\">$d14</td>
                <td style=\"text-align:right\">$d15</td>
                <td style=\"text-align:right\">$d16</td>
                <td style=\"text-align:right\">$d17</td>
                <td style=\"text-align:left\">$d18a</td>
                <td style=\"text-align:right\">$d18b</td>
                <td style=\"text-align:left\">$d19</td>
            </tr>";

            $l++;
        }


        $html .= "<tr>
            <td colspan=\"8\" align=\"right\">Razem:</td>
            <td style=\"text-align:right\">".Util::dkwota($t9)."</td>
            <td style=\"text-align:right\">".Util::dkwota($t10)."</td>
            <td style=\"text-align:right\">".Util::dkwota($t11)."</td>
            <td style=\"text-align:right\">".Util::dkwota($t12)."</td>
            <td style=\"text-align:right\">".Util::dkwota($t13)."</td>
            <td style=\"text-align:right\">".Util::dkwota($t14)."</td>
            <td style=\"text-align:right\">".Util::dkwota($t15)."</td>
            <td style=\"text-align:right\">".Util::dkwota($t16)."</td>
            <td style=\"text-align:right\">".Util::dkwota($t17)."</td>
            <td style=\"text-align:left\"></td>
            <td style=\"text-align:right\">".Util::dkwota($t18b)."</td>
            <td style=\"text-align:left\"></td>
        </tr>";


        $html .= "</tbody></table>";

        return $html;
     

    }


    public static function skpir_pdf()
    {    



        $year = Session::get("selected.year") ?? '';
        $month = Session::get("selected.month") ?? '';
        $nip = Session::get("selected.nip") ?? '';

        $firm = User::where("firm_nip",$nip)->first()->firm_name ?? '';
        if($nip == SettingConfig::get("nip")) $firm = SettingConfig::get("firm");

        $tax_form = User::where("firm_nip",$nip)->first()->tax_form ?? '';
        if($nip == SettingConfig::get("nip")) $tax_form = SettingConfig::get("tax_form");

        $sells = ModelAccounting::where("year",$year)->where("month",$month)->where("nip",$nip)->where("approve",1)->orderBy('lp','asc')->get();


        $html = "<table class=\"vat\">";
        $html .= "<thead>
        <tr>
            <th colspan=\"20\" style=\"border:0;text-align:left;font-size:18px;font-weight:normal;padding-left:0px;padding-bottom:20px\">
                Podsumowanie KPiR za ".sprintf('%02d', $month)."/{$year} - {$firm} NIP: {$nip}
            </th>
        </tr>

        <tr>
            <th rowspan=\"2\" style=\"text-align:center\">Miesiąc</th>
            <th colspan=\"3\" style=\"text-align:center\">Sprzedaż</th>
            <th rowspan=\"2\" style=\"width:60pt\">Zakup towarów handlowych i materiałów według cen zakupu</th>
            <th rowspan=\"2\" style=\"width:60pt\">Koszty uboczne zakupu</th>
            <th colspan=\"4\" style=\"text-align:center\">Wydatki</th>
            <th style=\"text-align:center\">Koszty działalności badawczo-rozwojowej</th>
            <th rowspan=\"2\" style=\"width:60pt\">Koszty razem<br>(12 + 13 + 16)</th>
            <th rowspan=\"2\" style=\"width:60pt\">Dochód</th>
        </tr>

        <tr>
  
            <th style=\"width:60pt\">wartość sprzedanych towarów i usług</th>
            <th style=\"width:60pt\">pozostałe przychody</th>
            <th style=\"width:60pt\">razem przychód (9+10)</th>

            <th style=\"width:60pt\">wynagrodzenie w gotówce i naturze</th>
            <th style=\"width:60pt\">pozostałe wydatki</th>
            <th style=\"width:60pt\">razem wydatki<br>(14 + 15)</th>
            <th style=\"width:60pt\">inne</th>


        </tr>

        <tr>
            <th></th>
            <th>9</th>
            <th>10</th>
            <th>11</th>
            <th>12</th>
            <th>13</th>
            <th>14</th>
            <th>15</th>
            <th>16</th>
            <th>17</th>
            <th>18</th>
            <th>-</th>
            <th>-</th>
        </tr>
        </thead><tbody>";

        $l = 1;
        $t9 = 0;
        $t10 = 0;
        $t11 = 0;
        $t12 = 0;
        $t13 = 0;
        $t14 = 0;
        $t15 = 0;
        $t16 = 0;
        $t17 = 0;
        $t18 = 0;
        $tkr = 0;
        $td = 0;

        $summaryByRate = []; 

        for($i=1;$i<=$month;$i++){ 


            $p9 =  ModelAccounting::where("year", $year)->where("month","=", $i)->where("nip", $nip)->where("mode", 0)->where("approve", 1)->select(DB::raw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.kpir.kpir9')) AS DECIMAL(15,2))) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p10 = ModelAccounting::where("year", $year)->where("month","=", $i)->where("nip", $nip)->where("mode", 0)->where("approve", 1)->select(DB::raw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.kpir.kpir10')) AS DECIMAL(15,2))) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p11 = $p9 + $p10;
            $p12 = ModelAccounting::where("year", $year)->where("month","=", $i)->where("nip", $nip)->where("mode", 1)->where("approve", 1)->select(DB::raw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.kpir.kpir12')) AS DECIMAL(15,2))) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p13 = ModelAccounting::where("year", $year)->where("month","=", $i)->where("nip", $nip)->where("mode", 1)->where("approve", 1)->select(DB::raw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.kpir.kpir13')) AS DECIMAL(15,2))) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p14 = ModelAccounting::where("year", $year)->where("month","=", $i)->where("nip", $nip)->where("mode", 1)->where("approve", 1)->select(DB::raw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.kpir.kpir14')) AS DECIMAL(15,2))) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p15 = ModelAccounting::where("year", $year)->where("month","=", $i)->where("nip", $nip)->where("mode", 1)->where("approve", 1)->select(DB::raw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.kpir.kpir15')) AS DECIMAL(15,2))) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p16 = $p14 + $p15;
            $p17 = ModelAccounting::where("year", $year)->where("month","=", $i)->where("nip", $nip)->where("mode", 1)->where("approve", 1)->select(DB::raw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.kpir.kpir17')) AS DECIMAL(15,2))) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p18 = ModelAccounting::where("year", $year)->where("month","=", $i)->where("nip", $nip)->where("mode", 1)->where("approve", 1)->select(DB::raw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.kpir.kpir18b')) AS DECIMAL(15,2))) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $pkr =  $p12 + $p13 + $p16;
            $pd = $p11 - $pkr;

            $t9 += $p9;
            $t10 += $p10;
            $t11 += $p11;
            $t13 += $p13;
            $t14 += $p14;
            $t15 += $p15;
            $t16 += $p16;
            $t17 += $p17;
            $t18 += $p18;
            $tkr += $pkr;
            $td += $pd;



            $d9 =  Util::dkwota($p9);
            $d10 = Util::dkwota($p10); 
            $d11 = Util::dkwota($p11); 
            $d12 = Util::dkwota($p12);
            $d13 = Util::dkwota($p13); 
            $d14 = Util::dkwota($p14); 
            $d15 = Util::dkwota($p15); 
            $d16 = Util::dkwota($p16); 
            $d17 = Util::dkwota($p17); 
            $d18 = Util::dkwota($p18); 
            $dkr = Util::dkwota($pkr); 
            $dd  = Util::dkwota($pd); 

        
            $clientAddress = '';
            if (!empty($sell->client_adres1)) $clientAddress .= $sell->client_adres1 . "<br>";
            if (!empty($sell->client_adres2)) $clientAddress .= $sell->client_adres2 . "<br>";

            $html .= "<tr>
                <td style=\"text-align:center\">$i</td>
                <td style=\"text-align:right\">$d9</td>
                <td style=\"text-align:right\">$d10</td>
                <td style=\"text-align:right\">$d11</td>
                <td style=\"text-align:right\">$d12</td>
                <td style=\"text-align:right\">$d13</td>
                <td style=\"text-align:right\">$d14</td>
                <td style=\"text-align:right\">$d15</td>
                <td style=\"text-align:right\">$d16</td>
                <td style=\"text-align:right\">$d17</td>
                <td style=\"text-align:right\">$d18</td>
                <td style=\"text-align:right\">$dkr</td>
                <td style=\"text-align:right\">$dd</td>
            </tr>";

            $l++;
        }


        $html .= "<tr>
            <td style=\"text-align:right\">Razem</td>
            <td style=\"text-align:right\">".Util::dkwota($t9)."</td>
            <td style=\"text-align:right\">".Util::dkwota($t10)."</td>
            <td style=\"text-align:right\">".Util::dkwota($t11)."</td>
            <td style=\"text-align:right\">".Util::dkwota($t12)."</td>
            <td style=\"text-align:right\">".Util::dkwota($t13)."</td>
            <td style=\"text-align:right\">".Util::dkwota($t14)."</td>
            <td style=\"text-align:right\">".Util::dkwota($t15)."</td>
            <td style=\"text-align:right\">".Util::dkwota($t16)."</td>
            <td style=\"text-align:right\">".Util::dkwota($t17)."</td>
            <td style=\"text-align:right\">".Util::dkwota($t18)."</td>
            <td style=\"text-align:right\">".Util::dkwota($tkr)."</td>
            <td style=\"text-align:right\">".Util::dkwota($td)."</td>

        </tr>";


        $html .= "</tbody></table>";


        $sumvat = 0;
        $html .= "<br><br>";
        $html .= "<table class=\"vat\">";
        $html .= "<thead>
        <tr>
            <th colspan=\"".($month+2)."\" style=\"border:0;text-align:left;font-size:18px;font-weight:normal;padding-left:0px;padding-bottom:20px\">
                Zestawienie podatku VAT
        </tr>
        <tr>";
        $html .= "<th style=\"width:45pt\">Miesiąc</th>";
        $html .= "<th style=\"width:80pt\">Podatek VAT</th>";
        $html .= "</tr>";
        $html .= "<tbody>";

        for($i=1;$i<=$month;$i++){ 

            for ($k = 10; $k <= 69; $k++) ${"p".$k} = 0;
            $p360 = 0;

            $p16 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 1)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            
            $p18 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 1)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;;
            $p20 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 1)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p24 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 9)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p26 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 16)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p28 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 10)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p30 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 18)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p32 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 12)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p33 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 20)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p34 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 1)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.net_zw')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.net_0')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.net_5')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.net_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.net_23')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p35 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 22)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p36 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 23)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p360 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 28)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;

            $p38 = round($p16) + round($p18) + round($p20) + round($p24) + round($p26) + round($p28) + round($p30) + round($p32) + round($p33) + round($p34) - round($p35) - round($p36) - round($p360);

            $p41 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 1)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 4)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p43 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 1)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 2)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p44 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 1)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 24)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p45 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 1)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 25)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p46 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 1)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 26)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p47 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 1)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 27)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;

            $p48 = $p41 + $p43 + $p44 + $p45 + $p46 + $p47;
            $p51 = $p38 - $p48;

            $vat = $p51;
            //if($vat < 0) $vat = 0;
            $sumvat += $vat;
            $html .= "<tr><td  style=\"text-align:center\">".$i."</td><td style=\"text-align:right;white-space: nowrap;\">".number_format(round($vat),2,","," ")."</td>";  
            //$html .= "<td style=\"text-align:right;white-space: nowrap;\">".number_format(round($sell),2,","," ")."</td>";  
            //$html .= "<td style=\"text-align:right;white-space: nowrap;\">".number_format(round($buy),2,","," ")."</td>";  

        
        }   
        $html .= "<tr><td  style=\"text-align:center\">Razem</td><td style=\"text-align:right;white-space: nowrap;\">".number_format(round($sumvat),2,","," ")."</td>";      
        $html .= "</tr>";
        $html .= "</tbody></table>";


        $sumadvance = 0;
        $html .= "<br><br>";
        $html .= "<table class=\"vat\">";
        $html .= "<thead>
        <tr>
            <th colspan=\"".($month+3)."\" style=\"border:0;text-align:left;font-size:18px;font-weight:normal;padding-left:0px;padding-bottom:20px\">
                Zestawienie zaliczek na podatek dochodowy
        </tr>
        <tr>
        <th style=\"width:45pt\">Miesiąc</th>";
        for($i=1;$i<=$month;$i++) 
        $html .= "<th style=\"width:45pt\">".$i."</th>";
        $html .= "<th style=\"width:45pt\">Razem</th>";
        $html .= "</tr>";
        $html .= "</tr>";
        $html .= "<tbody>
        <tr>
        <td style=\"text-align:center;\">Zaliczka</td>";
        for($i=1;$i<=$month;$i++){ 
            $advance  = json_decode(Advance::where("year",$year)->where("month",$i)->where("nip",$nip)->first()->data ?? '', true);
            $advance = $advance["advance"][$tax_form ]["amount"] ?? 0;
            $sumadvance += $advance;
            $html .= "<td style=\"text-align:center;white-space: nowrap;\">".number_format($advance,2,","," ")."</td>";  
        }   
        $html .= "<td style=\"text-align:center;white-space: nowrap;\">".number_format($sumadvance,2,","," ")."</td>";    
        $html .= "</tr>";
        $html .= "</tbody></table>";


        return $html;
    }


    public static function ewp_pdf()
    {
        $year = Session::get("selected.year") ?? '';
        $month = Session::get("selected.month") ?? '';
        $nip = Session::get("selected.nip") ?? '';

        $firm = User::where("firm_nip",$nip)->first()->firm_name ?? '';
        if($nip == SettingConfig::get("nip")) {
            $firm = SettingConfig::get("firm");
        }

        $rates = Util::getTaxLump();

        $sells = ModelAccounting::where("year",$year)
            ->where("month",$month)
            ->where("nip",$nip)
            ->where("mode",0)
            ->where("approve",1)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.lump')) != ''")
            ->orderBy('lp','asc')
            ->get();

        $colspanRates = count($rates);
        $totalColumns = 6 + $colspanRates + 2;

        $html = "<table class=\"vat\">";
        $html .= "<thead>
        <tr>
            <th colspan=\"{$totalColumns}\" style=\"border:0;text-align:left;font-size:18px;font-weight:normal;padding-left:0px;padding-bottom:20px\">
                Ewidencja przychodów za ".sprintf('%02d', $month)."/{$year} - {$firm} NIP: {$nip}
            </th>
        </tr>
        <tr>
            <th rowspan=\"2\">Lp.</th>
            <th rowspan=\"2\">Data wpisu</th>
            <th rowspan=\"2\">Data uzyskania przychodu</th>
            <th colspan=\"2\" style=\"text-align:center\">Oznaczenie dowodu księgowego</th>
            <th rowspan=\"2\" style=\"width:60pt\">Identyfikator podatkowy</th>
            <th colspan=\"{$colspanRates}\" style=\"text-align:center\">
                Przychody objęte ryczałtem od przychodów ewidencjonowanych według stawki
            </th>
            <th rowspan=\"2\" style=\"width:60pt\">Ogółem przychody</th>
            <th rowspan=\"2\" style=\"width:80pt\">Uwagi</th>
        </tr>
        <tr>
            <th style=\"width:80pt\">Numer KSeF</th>
            <th>Numer dokumentu</th>";

        foreach($rates as $label){
            $html .= "<th style=\"width:50pt\">{$label}</th>";
        }

        $html .= "</tr>
        <tr>
            <th>1</th>
            <th>2</th>
            <th>3</th>
            <th>4</th>
            <th>5</th>
            <th>6</th>";

        $colNumber = 7;
        for($i=0; $i<$colspanRates; $i++){
            $html .= "<th>".($colNumber++)."</th>";
        }

        $html .= "<th>".($colNumber++)."</th>
                  <th>".($colNumber++)."</th>
        </tr>
        </thead><tbody>";

        $l = 1;
        $totals = array_fill_keys(array_keys($rates), 0);
        $totalSum = 0;

        foreach ($sells as $sell) {

            $doc  = json_decode($sell->data_document, true);
            $vat  = is_array($doc['vat_summary'] ?? null) ? $doc['vat_summary'] : [];
            $kpir = is_array($doc['kpir'] ?? null) ? $doc['kpir'] : [];
            $lump = $doc['lump'] ?? '';

            $income = (float)($kpir['ewpz9'] ?? 0);

            $html .= "<tr>";
            $html .= "<td style=\"text-align:center\">$l</td>";
            $html .= "<td>".date("Y-m-d", strtotime($sell->create_at))."</td>";
            $html .= "<td>".date("Y-m-d", strtotime($sell->create_at))."</td>";
            $html .= "<td>{$sell->nr_ksef}</td>";
            $html .= "<td>".($sell->prefix ? $sell->prefix." ".$sell->nr : $sell->nr)."</td>";
            $html .= "<td>{$sell->client_nip}</td>";

            $rowSum = 0;

            foreach($rates as $key => $label){

                $value = ($lump == $key) ? $income : 0;
                $rowSum += $value;
                $totals[$key] += $value;

                $display = $value != 0 ? Util::dkwota($value) : '<center>-</center>';
                $html .= "<td style=\"text-align:right\">{$display}</td>";
            }

            $rowSum = round($rowSum,2);
            $totalSum += $rowSum;

            $displaySum = $rowSum != 0 ? Util::dkwota($rowSum) : '<center>-</center>';
            $comments = $kpir['comments'] ?? '';

            $html .= "<td style=\"text-align:right\">{$displaySum}</td>";
            $html .= "<td style=\"text-align:left\">{$comments}</td>";
            $html .= "</tr>";

            $l++;
        }

        $html .= "<tr>";
        $html .= "<td colspan=\"6\" align=\"right\">Razem:</td>";

        foreach($totals as $value){
            $html .= "<td style=\"text-align:right\">".($value > 0 ? Util::dkwota($value):'<center>-</center>')."</td>";
        }

        $html .= "<td style=\"text-align:right\">".($totalSum > 0 ? Util::dkwota($totalSum):'<center>-</center>')."</td>";
        $html .= "<td></td>";
        $html .= "</tr>";

        $html .= "</tbody></table>";

        return $html;

    }


    public static function ewpz_pdf()
    {

        $year = Session::get("selected.year") ?? '';
        $month = Session::get("selected.month") ?? '';
        $nip = Session::get("selected.nip") ?? '';

        $firm = User::where("firm_nip",$nip)->first()->firm_name ?? '';
        if($nip == SettingConfig::get("nip")) $firm = SettingConfig::get("firm");

        $sells = ModelAccounting::where("year",$year)->where("month",$month)->where("nip",$nip)->where("approve",1)->orderBy('lp','asc')->get();

        $html = "<table class=\"vat\">";
        $html .= "<thead>
        <tr>
            <th colspan=\"10\" style=\"border:0;text-align:left;font-size:18px;font-weight:normal;padding-left:0px;padding-bottom:20px\">Ewidencja przychodów, zakupów i wydatków za ".sprintf('%02d', $month)."/{$year} - {$firm} NIP: {$nip}</th>
        </tr>
        <tr>
            <th rowspan=\"2\">Lp.</th>
            <th rowspan=\"2\">Data</th>
            <th colspan=\"2\" style=\"text-align:center\">Oznaczenie dowodu księgowego</th>
            <th colspan=\"3\" style=\"width:110pt\">Kontrahent</th>
            <th rowspan=\"2\" style=\"width:110pt\">Opis zdarzenia</th>
            <th colspan=\"3\" style=\"width:80pt\">Przychód</th>
            <th rowspan=\"2\" style=\"width:60pt\">Zakup</th>
            <th rowspan=\"2\" style=\"width:60pt\">Wydatki</th>
        </tr>
        <tr>
            <th style=\"width:80pt\">numer KSeF</th>
            <th style=\"width:80pt\">numer dokumentu</th>
            <th style=\"width:60pt\">identyfikator podatkowy</th>
            <th style=\"width:110pt\">imię i nazwisko (nazwa firmy)</th>
            <th style=\"width:110pt\">adres</th>
            <th style=\"width:80pt\">wartość przychodu</th>
            <th style=\"width:60pt\">stawka ryczałtu [%]</th>
            <th style=\"width:80pt\">kwota ryczałtu</th>
        </tr>
        <tr>
            <th>1</th>
            <th>2</th>
            <th>3</th>
            <th>4</th>
            <th>5</th>
            <th>6</th>
            <th>7</th>
            <th>8</th>
            <th>9</th>
            <th>10</th>
            <th>11</th>
            <th>12</th>
            <th>13</th>
        </tr>
        </thead><tbody>";

        $l = 1;
        $totalIncome = 0;
        $totalTax    = 0;
        $totalShops  = 0;
        $totalExpenses  = 0;

        $summaryByRate = []; 

        foreach ($sells as $sell) {

            $doc    = json_decode($sell->data_document, true);
            $vat    = is_array($doc['vat_summary'] ?? null) ? $doc['vat_summary'] : [];
            $kpir   = is_array($doc['kpir'] ?? null) ? $doc['kpir'] : [];

            $amount = (float)($kpir['ewpz9'] ?? 0);
            $shops  = (float)($kpir['ewpz12'] ?? 0);

            $expenses = (float)($kpir['ewpz13'] ?? 0);

            //if (!$amount) { $l++; continue; }

            $lump = (float)$doc['lump'] ?? '';
            $rate = $lump / 100;
            $tax  = round($amount * $rate, 2);

            if (!$sell->mode) {

        
                    $totalIncome += $amount;
                    $totalTax    += $tax;
        
                if ($lump > 0) {
                    if (!isset($summaryByRate[(string)$lump])) {
                        $summaryByRate[(string)$lump] = ['income' => 0, 'tax' => 0];
                    }
                    $summaryByRate[(string)$lump]['income'] += $amount;
                    $summaryByRate[(string)$lump]['tax']    += $tax;


                }

            } else {
                $totalShops += $shops;
            }
            
            $totalExpenses += $expenses;

            $amount_ = !$sell->mode ? Util::dkwota($amount) : "<center>-</center>";
            $rate_   = !$sell->mode ? Util::rate($lump) : "<center>-</center>";
            $tax_    = !$sell->mode ? Util::dkwota($tax) : "<center>-</center>";
            $shops_  = $sell->mode ? Util::dkwota($shops) : "<center>-</center>";
            $expenses_  = $expenses != 0 ? Util::dkwota($expenses) : "<center>-</center>";

            $clientAddress = '';
            if (!empty($sell->client_adres1)) $clientAddress .= $sell->client_adres1 . "<br>";
            if (!empty($sell->client_adres2)) $clientAddress .= $sell->client_adres2 . "<br>";

            if($amount == 0 && $shops == 0 && $tax == 0 && $expenses == 0) continue;
            $html .= "<tr>
                <td style=\"text-align:center\">$l</td>
                <td>".date("Y-m-d", strtotime($sell->create_at))."</td>
                <td>{$sell->nr_ksef}</td>
                <td>".($sell->prefix ? $sell->prefix." ".$sell->nr : $sell->nr)."</td>
                <td>{$sell->client_nip}</td>
                <td>{$sell->client_name}</td>
                <td>{$clientAddress}</td>
                <td>{$doc['describe']}</td>
                <td style=\"text-align:right\">$amount_</td>
                <td style=\"text-align:right\">$rate_</td>
                <td style=\"text-align:right\">$tax_</td>
                <td style=\"text-align:right\">$shops_</td>
                <td style=\"text-align:right\">$expenses_</td>
            </tr>";

            $l++;
        }


        $html .= "<tr>
            <td colspan=\"8\" align=\"right\">Razem:</td>
            <td style=\"text-align:right\">".($totalIncome > 0 ? Util::dkwota($totalIncome):'<center>-</center>')."</td>
            <td style=\"text-align:right\"><center>-</center></td>
            <td style=\"text-align:right\">".($totalTax > 0 ? Util::dkwota($totalTax):'<center>-</center>')."</td>
            <td style=\"text-align:right\">".($totalShops > 0 ? Util::dkwota($totalShops):'<center>-</center>')."</td>
            <td style=\"text-align:right\">".($totalExpenses > 0 ? Util::dkwota($totalExpenses):'<center>-</center>')."</td>
        </tr>";


        ksort($summaryByRate, SORT_NUMERIC);

        $lastValidKey = null;
        foreach ($summaryByRate as $lump => $row)
            $lastValidKey = $lump;

        foreach ($summaryByRate as $lump => $row) {
            $html .= "<tr>
                <td colspan=\"8\" align=\"right\" class=\"".($lump != $lastValidKey ? "nob" : "bob")."\"></td>
                <td style=\"text-align:right\">".($row['income'] > 0 ? Util::dkwota($row['income']):'<center>-</center>')."</td>
                <td style=\"text-align:right\">".($lump > 0 ? Util::rate($lump):'<center>-</center>')."</td>
                <td style=\"text-align:right\">".($row['tax'] > 0 ? Util::dkwota($row['tax']):'<center>-</center>')."</td>
                <td style=\"text-align:right\"><center>-</center></td>
                <td style=\"text-align:right\"><center>-</center></td>
            </tr>";
        }

        $html .= "</tbody></table>";

        return $html;
    }



    public static function lump_pdf()
    {    

        $year = Session::get("selected.year") ?? '';
        $month = Session::get("selected.month") ?? '';
        $nip = Session::get("selected.nip") ?? '';

        $firm = User::where("firm_nip",$nip)->first()->firm_name ?? '';
        if($nip == SettingConfig::get("nip")) {
            $firm = SettingConfig::get("firm");
        }

        $rates = Util::getTaxLump();

        $colspan = count($rates);

        $html = "<table class=\"vat\">";
        $html .= "<thead>
        <tr>
            <th colspan=\"".($colspan+2)."\" style=\"border:0;text-align:left;font-size:18px;font-weight:normal;padding-left:0px;padding-bottom:20px\">
                Podsumowanie ryczałtu za ".sprintf('%02d', $month)."/{$year} - {$firm} NIP: {$nip}
            </th>
        </tr>
        <tr>
            <th rowspan=\"2\">Miesiąc</th>
            <th colspan=\"{$colspan}\" style=\"text-align:center\">Przychód według stawki</th>
            <th rowspan=\"2\" style=\"width:100pt\">Ogółem przychody</th>
        </tr>
        <tr>";


        foreach($rates as $label){
            $html .= "<th>{$label}</th>";
        }

        $html .= "</tr></thead><tbody>";

 
        $yearSums = array_fill_keys(array_keys($rates), 0);
        $yearTotal = 0;

        for($i=1;$i<=$month;$i++) {

            $sum = ModelAccounting::where("year", $year)
                ->where("month", $i)
                ->where("nip", $nip)
                ->where("mode", 0)
                ->where("approve", 1)
                ->select(DB::raw("
                    JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.lump')) as lump, 
                    SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.vat_summary.net_sum')) AS DECIMAL(15,2))) as total_net
                "))
                ->groupBy(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.lump'))"))
                ->pluck('total_net', 'lump')
                ->toArray();

            $html .= "<tr>";
            $html .= "<td style=\"text-align:center\">$i</td>";

            $monthTotal = 0;

            foreach($rates as $key => $label){

                $value = $sum[$key] ?? 0;
                $monthTotal += $value;
                $yearSums[$key] += $value;

                $html .= "<td style=\"text-align:right\">".Util::dkwota($value)."</td>";
            }

            $monthTotal = round($monthTotal,2);
            $yearTotal += $monthTotal;

            $html .= "<td style=\"text-align:right\">".Util::dkwota($monthTotal)."</td>";
            $html .= "</tr>";
        }


        $html .= "<tr>";
        $html .= "<td style=\"text-align:center\">Razem</td>";

        foreach($yearSums as $value){
            $html .= "<td style=\"text-align:right\">".Util::dkwota($value)."</td>";
        }

        $html .= "<td style=\"text-align:right\">".Util::dkwota($yearTotal)."</td>";
        $html .= "</tr>";

        $html .= "</tbody></table>";


        $sumvat = 0;
        $html .= "<br><br>";
        $html .= "<table class=\"vat\">";
        $html .= "<thead>
        <tr>
            <th colspan=\"".($month+2)."\" style=\"border:0;text-align:left;font-size:18px;font-weight:normal;padding-left:0px;padding-bottom:20px\">
                Zestawienie podatku VAT
        </tr>
        <tr>";
        $html .= "<th style=\"width:45pt\">Miesiąc</th>";
        $html .= "<th style=\"width:80pt\">Podatek VAT</th>";
        $html .= "</tr>";
        $html .= "<tbody>";

        for($i=1;$i<=$month;$i++){ 

            for ($k = 10; $k <= 69; $k++) ${"p".$k} = 0;
            $p360 = 0;

            $p16 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 1)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            
            $p18 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 1)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;;
            $p20 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 1)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p24 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 9)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p26 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 16)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p28 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 10)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p30 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 18)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p32 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 12)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p33 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 20)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p34 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 1)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.net_zw')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.net_0')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.net_5')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.net_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.net_23')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p35 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 22)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p36 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 23)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p360 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 0)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 28)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;

            $p38 = round($p16) + round($p18) + round($p20) + round($p24) + round($p26) + round($p28) + round($p30) + round($p32) + round($p33) + round($p34) - round($p35) - round($p36) - round($p360);

            $p41 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 1)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 4)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p43 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 1)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 2)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p44 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 1)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 24)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p45 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 1)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 25)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p46 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 1)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 26)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $p47 = DB::table('eprog_manager_accounting')->leftJoin('eprog_manager_vat', 'eprog_manager_accounting.vat_register', '=', 'eprog_manager_vat.id')->where('eprog_manager_accounting.year', $year)->where('eprog_manager_accounting.month', $i)->where('eprog_manager_accounting.nip', $nip)->where('eprog_manager_accounting.mode', 1)->where('eprog_manager_accounting.approve', 1)->where('eprog_manager_accounting.vat_register', '>', 0)->where('eprog_manager_vat.type', 27)->select(DB::raw("SUM(COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2)),0)+COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(eprog_manager_accounting.data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2)),0)) as sum"))->pluck('sum')->toArray()[0] ?? 0;

            $p48 = $p41 + $p43 + $p44 + $p45 + $p46 + $p47;
            $p51 = $p38 - $p48;

            $vat = $p51;
            //if($vat < 0) $vat = 0;
            $sumvat += $vat;
            $html .= "<tr><td  style=\"text-align:center\">".$i."</td><td style=\"text-align:right;white-space: nowrap;\">".number_format(round($vat),2,","," ")."</td>";  
            //$html .= "<td style=\"text-align:right;white-space: nowrap;\">".number_format(round($sell),2,","," ")."</td>";  
            //$html .= "<td style=\"text-align:right;white-space: nowrap;\">".number_format(round($buy),2,","," ")."</td>";  

        
        }   
        $html .= "<tr><td  style=\"text-align:center\">Razem</td><td style=\"text-align:right;white-space: nowrap;\">".number_format(round($sumvat),2,","," ")."</td>";      
        $html .= "</tr>";
        $html .= "</tbody></table>";


        $sumadvance = 0;
        $html .= "<br><br>";
        $html .= "<table class=\"vat\">";
        $html .= "<thead>
        <tr>
            <th colspan=\"".($month+3)."\" style=\"border:0;text-align:left;font-size:18px;font-weight:normal;padding-left:0px;padding-bottom:20px\">
                Zestawienie zaliczek na podatek dochodowy
        </tr>
        <tr>
        <th style=\"width:45pt\">Miesiąc</th>";
        for($i=1;$i<=$month;$i++) 
        $html .= "<th style=\"width:45pt\">".$i."</th>";
        $html .= "<th style=\"width:45pt\">Razem</th>";
        $html .= "</tr>";
        $html .= "<tbody>
        <tr>
        <td style=\"text-align:center;\">Zaliczka</td>";
        for($i=1;$i<=$month;$i++){ 
            $advance  = json_decode(Advance::where("year",$year)->where("month",$i)->where("nip",$nip)->first()->data ?? '', true);
            $advance = $advance["advance"]["lump"]["amount"] ?? 0;
            $sumadvance += $advance;
            $html .= "<td style=\"text-align:center;white-space: nowrap;\">".number_format($advance,2,","," ")."</td>";  
        }   
        $html .= "<td style=\"text-align:center;white-space: nowrap;\">".number_format($sumadvance,2,","," ")."</td>";    
        $html .= "</tr>";
        $html .= "</tbody></table>";


        return $html;
    }



    public static function vat_pdf()
    {

        $year = Session::get("selected.year") ?? '';
        $month = Session::get("selected.month") ?? '';
        $nip = Session::get("selected.nip") ?? '';

        $firm = User::where("firm_nip",$nip)->first()->firm_name ?? '';
        if($nip == SettingConfig::get("nip")) $firm = SettingConfig::get("firm");

        $type = Util::getVatType();

        $html = "<table class=\"vat\">";
        $html .= "<thead>
        <tr>
            <th colspan=\"12\" style=\"border:0;text-align:left;font-size:18px;font-weight:normal;padding-left:0px;padding-bottom:20px\">
                Rejestry VAT za ".sprintf('%02d', $month)."/{$year} - {$firm} NIP: {$nip}
            </th>
        </tr>
        </thead></table>";

        $html .= "<table class=\"vat\">";
        $html .= "<thead>
        <tr>
            <th colspan=\"10\" style=\"border:0;text-align:left;font-size:18px;font-weight:normal;padding-left:0px;padding-bottom:20px\">
                Zestawienie rejestrów VAT - podatek należny
            </th>
        </tr>
        <tr>
            <th rowspan=\"2\" colspan=\"2\" style=\"width:110pt\">Nazwa i typ rejestru VAT</th>
            <th colspan=\"4\" style=\"width:110pt\">Podatek należny</th>

        </tr>
        <tr>
            <th style=\"width:60pt\">netto</th>
            <th style=\"width:10pt\">stawka VAT</th>
            <th style=\"width:60pt\">kwota VAT</th>
            <th style=\"width:60pt\">brutto</th>
        </tr>

        </thead><tbody>";
  

        $registers = Vat::where("disp",1)->where("mode",0)->get();

        $snet = 0; $svat = 0; $sgross = 0;
        foreach($registers as $register){

            $net23 = ModelAccounting::where("year", $year)->where("month","=", $month)->where("nip", $nip)->where("mode", 0)->where("approve", 1)->where("vat_register",$register->id)->select(DB::raw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.vat_summary.net_23')) AS DECIMAL(15,2))) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $net8 = ModelAccounting::where("year", $year)->where("month","=", $month)->where("nip", $nip)->where("mode", 0)->where("approve", 1)->where("vat_register",$register->id)->select(DB::raw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.vat_summary.net_8')) AS DECIMAL(15,2))) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $net5 = ModelAccounting::where("year", $year)->where("month","=", $month)->where("nip", $nip)->where("mode", 0)->where("approve", 1)->where("vat_register",$register->id)->select(DB::raw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.vat_summary.net_5')) AS DECIMAL(15,2))) as sum"))->pluck('sum')->toArray()[0] ?? 0;

            $vat23 = ModelAccounting::where("year", $year)->where("month","=", $month)->where("nip", $nip)->where("mode", 0)->where("approve", 1)->where("vat_register",$register->id)->select(DB::raw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2))) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $vat8 = ModelAccounting::where("year", $year)->where("month","=", $month)->where("nip", $nip)->where("mode", 0)->where("approve", 1)->where("vat_register",$register->id)->select(DB::raw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2))) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $vat5 = ModelAccounting::where("year", $year)->where("month","=", $month)->where("nip", $nip)->where("mode", 0)->where("approve", 1)->where("vat_register",$register->id)->select(DB::raw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2))) as sum"))->pluck('sum')->toArray()[0] ?? 0;

            $gross23 = $net23 + $vat23;
            $gross8 = $net8 + $vat8;
            $gross5 = $net5 + $vat5;

            $net = $net23 + $net8 + $net5; 
            $vat = $vat23 + $vat8 + $vat5; 
            $gross = $gross23 + $gross8 + $gross5; 

            $snet += $net;
            $svat += $vat;
            $sgross += $gross;

            $html .= "<tr>";
            $html .= "<td style=\"text-align:center\" rowspan=\"3\">$register->name</td>";
            $html .= "<td style=\"text-align:center\" rowspan=\"3\">".($type[$register->type] ?? '')."</td>";
            $html .= "<td style=\"text-align:right\">".($net23 != 0 ? Util ::dkwota($net23) : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:center\">23</td>";
            $html .= "<td style=\"text-align:right\">".($net23 != 0 ? Util ::dkwota($vat23) : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:right\">".($net23 != 0 ? Util ::dkwota($gross23) : "<center>-<center>")."</td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td style=\"text-align:right\">".($net8 != 0 ? Util ::dkwota($net8) : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:center\">8</td>";
            $html .= "<td style=\"text-align:right\">".($net8 != 0 ? Util ::dkwota($vat8) : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:right\">".($net8 != 0 ? Util ::dkwota($gross8) : "<center>-<center>")."</td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td style=\"text-align:right\">".($net5 != 0 ? Util ::dkwota($net5) : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:center\">5</td>";
            $html .= "<td style=\"text-align:right\">".($net5 != 0 ? Util ::dkwota($vat5) : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:right\">".($net5 != 0 ? Util ::dkwota($gross5) : "<center>-<center>")."</td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td style=\"text-align:right\"></td>";
            $html .= "<td style=\"text-align:right\">Razem należności</td>";
            $html .= "<td style=\"text-align:right\">".($net != 0 ? Util ::dkwota($net) : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:center\">-</td>";
            $html .= "<td style=\"text-align:right\">".($net != 0 ? Util ::dkwota($vat) : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:right\">".($net != 0 ? Util ::dkwota($gross) : "<center>-<center>")."</td>";
            $html .= "</tr>";


        }

        if(count($registers) >= 1){
            $html .= "<tr>";
            $html .= "<td style=\"text-align:right\" colspan=\"2\">Razem</td>";
            $html .= "<td style=\"text-align:right\">".($snet != 0 ? Util ::dkwota($snet)  : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:center\">-</td>";
            $html .= "<td style=\"text-align:right\">".($svat != 0 ? Util ::dkwota($svat)  : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:right\">".($sgross != 0 ? Util ::dkwota($sgross)  : "<center>-<center>")."</td>";
            $html .= "</tr>";
        }


        $html .= "</tbody></table>";

        $html .= "<br><br><table class=\"vat\">";
        $html .= "<thead>
        <tr>
            <th colspan=\"12\" style=\"border:0;text-align:left;font-size:18px;font-weight:normal;padding-left:0px;padding-bottom:20px\">
                Zestawienie rejestrów VAT - podatek naliczony
            </th>
        </tr>
        <tr>
            <th rowspan=\"2\" colspan=\"2\" style=\"width:110pt\">Nazwa i typ rejestru VAT</th>
            <th colspan=\"2\">Nie podlegające odliczeniu, opodatkowane stawką 0%, zwolnione</th>
            <th colspan=\"4\">Opodatkowane, związane wyłącznie ze sprzedażą opodatkowaną</th>
        </tr>
        <tr>
            <th style=\"width:60pt\">kwota</th>
            <th style=\"width:60pt\">nie odl. VAT</th>
            <th style=\"width:60pt\">netto</th>
            <th style=\"width:10pt\">stawka VAT</th>
            <th style=\"width:60pt\">kwota VAT</th>
            <th style=\"width:60pt\">brutto</th>
        </tr>

        </thead><tbody>";

        $registers = Vat::where("disp",1)->where("mode",1)->get();

        $snet = 0; $svat = 0; $sgross = 0; $snet0 = 0; $svatnp = 0;
        foreach($registers as $register){

            $net0 = ModelAccounting::where("year",$year)->where("month","=",$month)->where("nip",$nip)->where("mode",1)->where("approve",1)->where("vat_register",$register->id)->select(DB::raw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.vat_summary.net_0')) AS DECIMAL(15,2)) + CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.vat_summary.net_zw')) AS DECIMAL(15,2)) + CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.vat_summary.net_np')) AS DECIMAL(15,2))) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $vatnp = ModelAccounting::where("year", $year)->where("month","=", $month)->where("nip", $nip)->where("mode", 1)->where("approve", 1)->where("vat_register",$register->id)->select(DB::raw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.vat_summary.vat_np')) AS DECIMAL(15,2))) as sum"))->pluck('sum')->toArray()[0] ?? 0;

            $net23 = ModelAccounting::where("year", $year)->where("month","=", $month)->where("nip", $nip)->where("mode", 1)->where("approve", 1)->where("vat_register",$register->id)->select(DB::raw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.vat_summary.net_23')) AS DECIMAL(15,2))) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $net8 = ModelAccounting::where("year", $year)->where("month","=", $month)->where("nip", $nip)->where("mode", 1)->where("approve", 1)->where("vat_register",$register->id)->select(DB::raw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.vat_summary.net_8')) AS DECIMAL(15,2))) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $net5 = ModelAccounting::where("year", $year)->where("month","=", $month)->where("nip", $nip)->where("mode", 1)->where("approve", 1)->where("vat_register",$register->id)->select(DB::raw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.vat_summary.net_5')) AS DECIMAL(15,2))) as sum"))->pluck('sum')->toArray()[0] ?? 0;

            $vat23 = ModelAccounting::where("year", $year)->where("month","=", $month)->where("nip", $nip)->where("mode", 1)->where("approve", 1)->where("vat_register",$register->id)->select(DB::raw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.vat_summary.vat_23')) AS DECIMAL(15,2))) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $vat8 = ModelAccounting::where("year", $year)->where("month","=", $month)->where("nip", $nip)->where("mode", 1)->where("approve", 1)->where("vat_register",$register->id)->select(DB::raw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.vat_summary.vat_8')) AS DECIMAL(15,2))) as sum"))->pluck('sum')->toArray()[0] ?? 0;
            $vat5 = ModelAccounting::where("year", $year)->where("month","=", $month)->where("nip", $nip)->where("mode", 1)->where("approve", 1)->where("vat_register",$register->id)->select(DB::raw("SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(data_document,'$.vat_summary.vat_5')) AS DECIMAL(15,2))) as sum"))->pluck('sum')->toArray()[0] ?? 0;

            $gross23 = $net23 + $vat23;
            $gross8 = $net8 + $vat8;
            $gross5 = $net5 + $vat5;

            $net = $net23 + $net8 + $net5; 
            $vat = $vat23 + $vat8 + $vat5; 
            $gross = $gross23 + $gross8 + $gross5; 

            $snet += $net;
            $svat += $vat;
            $sgross += $gross;


            $snet0 += $net0;
            $svatnp += $vatnp;

            $html .= "<tr>";
            $html .= "<td style=\"text-align:center\" rowspan=\"3\">$register->name</td>";
            $html .= "<td style=\"text-align:center\" rowspan=\"3\">".($type[$register->type] ?? '')."</td>";
            $html .= "<td style=\"text-align:right\"  rowspan=\"3\">".($net0 != 0 ? Util ::dkwota($net0) : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:right\"  rowspan=\"3\">".($vatnp != 0 ? Util ::dkwota($vatnp) : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:right\">".($net23 != 0 ? Util ::dkwota($net23) : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:center\">23</td>";
            $html .= "<td style=\"text-align:right\">".($net23 != 0 ? Util ::dkwota($vat23) : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:right\">".($net23 != 0 ? Util ::dkwota($gross23) : "<center>-<center>")."</td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td style=\"text-align:right\">".($net8 != 0 ? Util ::dkwota($net8) : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:center\">8</td>";
            $html .= "<td style=\"text-align:right\">".($net8 != 0 ? Util ::dkwota($vat8) : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:right\">".($net8 != 0 ? Util ::dkwota($gross8) : "<center>-<center>")."</td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td style=\"text-align:right\">".($net5 != 0 ? Util ::dkwota($net5) : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:center\">5</td>";
            $html .= "<td style=\"text-align:right\">".($net5 != 0 ? Util ::dkwota($vat5) : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:right\">".($net5 != 0 ? Util ::dkwota($gross5) : "<center>-<center>")."</td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td style=\"text-align:right\" colspan=\"4\">Razem nabycia</td>";
            $html .= "<td style=\"text-align:right\">".($net != 0 ? Util ::dkwota($net) : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:center\">-</td>";
            $html .= "<td style=\"text-align:right\">".($net != 0 ? Util ::dkwota($vat) : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:right\">".($net != 0 ? Util ::dkwota($gross) : "<center>-<center>")."</td>";
            $html .= "</tr>";

        }

        if(count($registers) > 1){
            $html .= "<tr>";
            $html .= "<td style=\"text-align:right\" colspan=\"2\">Razem</td>";
            $html .= "<td style=\"text-align:right\">".($snet0 != 0 ? Util ::dkwota($snet0)  : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:right\">".($svatnp != 0 ? Util ::dkwota($svatnp)  : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:right\">".($snet != 0 ? Util ::dkwota($snet)  : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:center\">-</td>";
            $html .= "<td style=\"text-align:right\">".($svat != 0 ? Util ::dkwota($svat)  : "<center>-<center>")."</td>";
            $html .= "<td style=\"text-align:right\">".($sgross != 0 ? Util ::dkwota($sgross)  : "<center>-<center>")."</td>";
            $html .= "</tr>";
        }

        $html .= "</tbody></table>";

        $registers = Vat::where("disp",1)->get();
 
        foreach($registers as $register){


            //************************************************************************** Sprzedaz

            if($register->mode == 0){

                $sells = ModelAccounting::where("year",$year)->where("month",$month)->where("nip",$nip)->where("approve",1)->where("mode",0)->where("vat_register",">",0)->where("vat_register",$register->id)->orderBy('lp','asc')->get();

                $html .= "<br><br><table class=\"vat\">";
                $html .= "<thead>
                <tr>
                    <th colspan=\"10\" style=\"border:0;text-align:left;font-size:18px;font-weight:normal;padding-left:0px;padding-bottom:20px\">
                        Rejestr VAT (podatek należny): ".$register->name." (".$type[$register->type ?? ''].")
                    </th>
                </tr>
                <tr>
                    <th rowspan=\"2\">Lp.</th>
                    <th rowspan=\"2\">Data</th>
                    <th rowspan=\"2\">Data VAT</th>
                    <th rowspan=\"2\" style=\"width:80pt\">Numer KSeF</th>
                    <th rowspan=\"2\" style=\"width:80pt\">Numer dokumentu</th>
                    <th rowspan=\"2\" style=\"width:110pt\">Dane kontrahenta</th>
                    <th colspan=\"4\" style=\"width:110pt\">Podatek należny</th>
                    <th rowspan=\"2\">Ozn. VAT</th>
                </tr>
                <tr>
                    <th style=\"width:60pt\">netto</th>
                    <th style=\"width:10pt\">stawka VAT</th>
                    <th style=\"width:60pt\">kwota VAT</th>
                    <th style=\"width:60pt\">brutto</th>
                </tr>
                </thead><tbody>";

                $totalNet = 0;
                $totalVat = 0;
                $totalGross = 0;
                $vatTotals = [];
                $l = 1;

                foreach ($sells as $sell) {
                    $vatDate = strtotime($sell->vat_at);
                    $rates = [Util::getVatRate("23",$accounting->vat_at ?? null),Util::getVatRate("8",$accounting->vat_at ?? null),Util::getVatRate("5",$accounting->vat_at ?? null),"0","zw","np"];
                    $doc = json_decode($sell->data_document,true) ?: [];
                    $vat = is_array($doc['vat_summary'] ?? null) ? $doc['vat_summary'] : [];
                    $gtus = is_array($doc['gtu'] ?? null) ? $doc['gtu'] : [];
                    $procs = is_array($doc['proc'] ?? null) ? $doc['proc'] : [];

                    $validRates = [];
                    foreach ($rates as $rate) {
                        $net = (float)($vat["net_$rate"] ?? 0);
                        $vat_v = (float)($vat["vat_$rate"] ?? 0);
                        $gross = (float)($vat["gross_$rate"] ?? 0);
                        if($net || $vat_v || $gross) $validRates[] = $rate;
                    }

                    if(empty($validRates)) { $l++; continue; }
                    $showSum = count($validRates) > 1;
                    $rowspan = count($validRates) + ($showSum ? 1 : 0);
                    $first = true;
                    $netSum=0; $vatSum=0; $grossSum=0;
                    foreach($validRates as $rate) { $netSum+=(float)($vat["net_$rate"]??0); $vatSum+=(float)($vat["vat_$rate"]??0); $grossSum+=(float)($vat["gross_$rate"]??0); }

                    $clientAddress='';
                    if(!empty($sell->client_adres1)) $clientAddress .= $sell->client_adres1."<br>";
                    if(!empty($sell->client_adres2)) $clientAddress .= $sell->client_adres2."<br>";

                    foreach($validRates as $rate){
                        $net=(float)($vat["net_$rate"]??0);
                        $vat_v=(float)($vat["vat_$rate"]??0);
                        $gross=(float)($vat["gross_$rate"]??0);

                        $totalNet += $net;
                        $totalVat += $vat_v;
                        $totalGross += $gross;
                        if(!isset($vatTotals[$rate])) $vatTotals[$rate]=['net'=>0,'vat'=>0,'gross'=>0];
                        $vatTotals[$rate]['net']+=$net;
                        $vatTotals[$rate]['vat']+=$vat_v;
                        $vatTotals[$rate]['gross']+=$gross;

                        $html .= "<tr>";
                        if($first){
                            $html .= "<td rowspan=\"$rowspan\" style=\"text-align:center\">$l</td>";
                            $html .= "<td rowspan=\"$rowspan\">".date("Y-m-d",strtotime($sell->create_at))."</td>";
                            $html .= "<td rowspan=\"$rowspan\">".date("Y-m-d",strtotime($sell->vat_at))."</td>";
                            $html .= "<td rowspan=\"$rowspan\">{$sell->nr_ksef}</td>";
                            $html .= "<td rowspan=\"$rowspan\">".($sell->prefix ? $sell->prefix." ".$sell->nr : $sell->nr)."</td>";
                            $html .= "<td rowspan=\"$rowspan\">{$sell->client_name}<br>{$clientAddress}NIP: {$sell->client_nip}</td>";
                        }
                        $html .= "<td style=\"text-align:right\">".($net==0?'<center>-</center>':Util::dkwota($net))."</td>";
                        $html .= "<td style=\"text-align:center\">$rate</td>";
                        $html .= "<td style=\"text-align:right\">".($vat_v==0?'<center>-</center>':Util::dkwota($vat_v))."</td>";
                        $html .= "<td style=\"text-align:right\">".($gross==0?'<center>-</center>':Util::dkwota($gross))."</td>";

                        if($first){
                            $ozn="";
                            foreach($gtus as $k=>$v) $ozn.=$k."<br>";
                            foreach($procs as $k=>$v) $ozn.=$k."<br>";
                            $html .= "<td rowspan=\"$rowspan\">".(empty($ozn)?'':rtrim($ozn,"<br>"))."</td>";
                            $first=false;
                        }
                        $html .= "</tr>";
                    }

                    if($showSum){
                        $html .= "<tr>
                            <td style=\"text-align:right\">".($netSum==0?'<center>-</center>':Util::dkwota($netSum))."</td>
                            <td style=\"text-align:center\">Razem</td>
                            <td style=\"text-align:right\">".($vatSum==0?'<center>-</center>':Util::dkwota($vatSum))."</td>
                            <td style=\"text-align:right\">".($grossSum==0?'<center>-</center>':Util::dkwota($grossSum))."</td>
                        </tr>";
                    }
                    $l++;
                }

                $html .= "<tr>
                    <td colspan=\"6\" style=\"text-align:right;font-weight:bold\">Razem:</td>
                    <td style=\"text-align:right\">".($totalNet==0?'<center>-</center>':Util::dkwota($totalNet))."</td>
                    <td></td>
                    <td style=\"text-align:right\">".($totalVat==0?'<center>-</center>':Util::dkwota($totalVat))."</td>
                    <td style=\"text-align:right\">".($totalGross==0?'<center>-</center>':Util::dkwota($totalGross))."</td>
                    <td></td>
                </tr>";


                krsort($vatTotals);
                foreach($vatTotals as $r=>$sums){
                    if($sums['net']==0) continue;
                    $html .= "<tr>
                        <td colspan=\"6\" style=\"text-align:right;font-weight:bold\"></td>
                        <td style=\"text-align:right\">".($sums['net']==0?'<center>-</center>':Util::dkwota($sums['net']))."</td>
                        <td style=\"text-align:center\">$r</td>
                        <td style=\"text-align:right\">".($sums['vat']==0?'<center>-</center>':Util::dkwota($sums['vat']))."</td>
                        <td style=\"text-align:right\">".($sums['gross']==0?'<center>-</center>':Util::dkwota($sums['gross']))."</td>
                        <td></td>
                    </tr>";
                }

                $html .= "</tbody></table>";
                $html .= "";

            }


            //************************************************************************** Nabycie

            if($register->mode == 1){

                $buys = ModelAccounting::where("year",$year)->where("month",$month)->where("nip",$nip)->where("approve",1)->where("mode",1)->where("vat_register",">",0)->where("vat_register",$register->id)->orderBy('lp','asc')->get();
                $html .= "<br><br><table class=\"vat\">";
                $html .= "<thead>
                <tr>
                    <th colspan=\"12\" style=\"border:0;text-align:left;font-size:18px;font-weight:normal;padding-left:0px;padding-bottom:20px\">
                        Rejestr VAT (podatek naliczony): ".$register->name." (".$type[$register->type ?? ''].")
                    </th>
                </tr>
                <tr>
                    <th rowspan=\"2\">Lp.</th>
                    <th rowspan=\"2\">Data</th>
                    <th rowspan=\"2\">Data VAT</th>
                    <th rowspan=\"2\" style=\"width:80pt\">Numer KseF</th>
                    <th rowspan=\"2\" style=\"width:80pt\">Numer dokumentu</th>
                    <th rowspan=\"2\" style=\"width:110pt\">Dane kontrahenta</th>
                    <th colspan=\"2\">Nie podlegające odliczeniu, opodatkowane stawką 0%, zwolnione</th>
                    <th colspan=\"4\">Opodatkowane, związane wyłącznie ze sprzedażą opodatkowaną</th>
                    <th rowspan=\"2\">Ozn. VAT</th>
                </tr>
                <tr>
                    <th style=\"width:60pt\">kwota</th>
                    <th style=\"width:60pt\">nie odl. VAT</th>
                    <th style=\"width:60pt\">netto</th>
                    <th style=\"width:10pt\">stawka VAT</th>
                    <th style=\"width:60pt\">kwota VAT</th>
                    <th style=\"width:60pt\">brutto</th>
                </tr>
                </thead><tbody>";

                $totalNet0 = 0;
                $totalVat0 = 0;
                $totalNet = 0;
                $totalVat = 0;
                $totalGross = 0;
                $vatTotals = [];

                foreach($buys as $buy){
                    $vatDate = strtotime($buy->vat_at);
                    $ratesForSummary = [Util::getVatRate("23",$accounting->vat_at ?? null),Util::getVatRate("8",$accounting->vat_at ?? null),Util::getVatRate("5",$accounting->vat_at ?? null)];
                    $doc=json_decode($buy->data_document,true) ?: [];
                    $vat=is_array($doc['vat_summary']??null) ? $doc['vat_summary'] : [];
                    $gtus=is_array($doc['gtu']??null) ? $doc['gtu'] : [];
                    $procs=is_array($doc['proc']??null) ? $doc['proc'] : [];

                    $net0 = (float)($vat['net_0']??0)+(float)($vat['net_zw']??0)+(float)($vat['net_np']??0);
                    $vat0 = (float)($vat['vat_np']??0);
                    $totalNet0+=$net0;
                    $totalVat0+=$vat0;

                    $validRates=[];
                    foreach($ratesForSummary as $rate){
                        $net=(float)($vat["net_$rate"]??0);
                        $vat_v=(float)($vat["vat_$rate"]??0);
                        $gross=(float)($vat["gross_$rate"]??0);
                        if($net||$vat_v||$gross) $validRates[]=$rate;
                    }
                    if(empty($validRates)) $validRates[]=null;
                    $showSum = count(array_filter($validRates))>1;
                    $rowspan = count($validRates)+($showSum?1:0);
                    $first=true;
                    $netSum=0; $vatSum=0; $grossSum=0;

                    $clientAddress='';
                    if(!empty($buy->client_adres1)) $clientAddress .= $buy->client_adres1."<br>";
                    if(!empty($buy->client_adres2)) $clientAddress .= $buy->client_adres2."<br>";

                    foreach($validRates as $rate){
                        $html.="<tr>";
                        if($first){
                            $html.="<td rowspan=\"$rowspan\" style=\"text-align:center\">".$buy->lp."</td>";
                            $html.="<td rowspan=\"$rowspan\">".date("Y-m-d",strtotime($buy->create_at))."</td>";
                            $html.="<td rowspan=\"$rowspan\">".date("Y-m-d",strtotime($buy->vat_at))."</td>";
                            $html.="<td rowspan=\"$rowspan\">{$buy->nr_ksef}</td>";
                            $html.="<td rowspan=\"$rowspan\">".($buy->prefix ? $buy->prefix." ".$buy->nr : $buy->nr)."</td>";
                            $html.="<td rowspan=\"$rowspan\">{$buy->client_name}<br>{$clientAddress}NIP: {$buy->client_nip}</td>";
                            $html.="<td rowspan=\"$rowspan\" style=\"text-align:right\">".($net0==0?'<center>-</center>':Util::dkwota($net0))."</td>";
                            $html.="<td rowspan=\"$rowspan\" style=\"text-align:right\">".($vat0==0?'<center>-</center>':Util::dkwota($vat0))."</td>";
                            $first=false;
                        }

                        if($rate!==null){
                            $net=(float)($vat["net_$rate"]??0);
                            $vat_v=(float)($vat["vat_$rate"]??0);
                            $gross=(float)($vat["gross_$rate"]??0);

                            $totalNet+=$net;
                            $totalVat+=$vat_v;
                            $totalGross+=$gross;
                            if(!isset($vatTotals[$rate])) $vatTotals[$rate]=['net'=>0,'vat'=>0,'gross'=>0];
                            $vatTotals[$rate]['net']+=$net;
                            $vatTotals[$rate]['vat']+=$vat_v;
                            $vatTotals[$rate]['gross']+=$gross;

                            $netSum+=$net; $vatSum+=$vat_v; $grossSum+=$gross;
                            $html.="<td style=\"text-align:right\">".($net==0?'<center>-</center>':Util::dkwota($net))."</td>";
                            $html.="<td style=\"text-align:center\">$rate</td>";
                            $html.="<td style=\"text-align:right\">".($vat_v==0?'<center>-</center>':Util::dkwota($vat_v))."</td>";
                            $html.="<td style=\"text-align:right\">".($gross==0?'<center>-</center>':Util::dkwota($gross))."</td>";
                        }else{
                            $html.="<td style=\"text-align:center\">-</td><td style=\"text-align:center\">-</td><td style=\"text-align:center\">-</td><td style=\"text-align:center\">-</td>";
                        }

                        if($rate===$validRates[0]){
                            $ozn="";
                            foreach($gtus as $k=>$v) $ozn.=$k."<br>";
                            foreach($procs as $k=>$v) $ozn.=$k."<br>";
                            $html.="<td rowspan=\"$rowspan\">".(empty($ozn)?'':rtrim($ozn,"<br>"))."</td>";
                        }
                        $html.="</tr>";
                    }

                    if($showSum){
                        $html.="<tr>
                            <td style=\"text-align:right\">".($netSum==0?'<center>-</center>':Util::dkwota($netSum))."</td>
                            <td style=\"text-align:right\">Razem</td>
                            <td style=\"text-align:right\">".($vatSum==0?'<center>-</center>':Util::dkwota($vatSum))."</td>
                            <td style=\"text-align:right\">".($grossSum==0?'<center>-</center>':Util::dkwota($grossSum))."</td>
                        </tr>";
                    }
                }

                $html.="<tr>
                    <td colspan=\"6\" style=\"text-align:right;font-weight:bold\">Razem:</td>
                    <td style=\"text-align:right\">".($totalNet0==0?'<center>-</center>':Util::dkwota($totalNet0))."</td>
                    <td style=\"text-align:right\">".($totalVat0==0?'<center>-</center>':Util::dkwota($totalVat0))."</td>
                    <td style=\"text-align:right\">".($totalNet==0?'<center>-</center>':Util::dkwota($totalNet))."</td>
                    <td></td>
                    <td style=\"text-align:right\">".($totalVat==0?'<center>-</center>':Util::dkwota($totalVat))."</td>
                    <td style=\"text-align:right\">".($totalGross==0?'<center>-</center>':Util::dkwota($totalGross))."</td>
                    <td></td>
                </tr>";

                krsort($vatTotals);
                foreach($vatTotals as $r=>$sums){
                    if($sums['net']==0) continue;
                    $html.="<tr>
                        <td colspan=\"8\" style=\"text-align:right;font-weight:bold\"></td>
                        <td style=\"text-align:right\">".($sums['net']==0?'<center>-</center>':Util::dkwota($sums['net']))."</td>
                        <td style=\"text-align:center\">$r</td>
                        <td style=\"text-align:right\">".($sums['vat']==0?'<center>-</center>':Util::dkwota($sums['vat']))."</td>
                        <td style=\"text-align:right\">".($sums['gross']==0?'<center>-</center>':Util::dkwota($sums['gross']))."</td>
                        <td></td>
                    </tr>";
                }

                $html.="</tbody></table>";
           }     
        }
        return $html;


    }


}