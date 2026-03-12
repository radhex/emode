<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Input;
use Redirect;
use Flash;
use October\Rain\Exception\ValidationException;
use Eprog\Manager\Models\Mailing as ModelMailing;
use Eprog\Manager\Models\Ksef as ModelKsef;
use Eprog\Manager\Models\SettingKsef;
use Eprog\Manager\Models\SettingConfig;
use Eprog\Manager\Models\Accounting;
use Eprog\Manager\Classes\Ksef as ClassKsef;
use RainLab\User\Models\User;
use RainLab\User\Models\UserGroup;
use RainLab\User\Models\UserGroups;
use Mail;
use Carbon\carbon;
use Artisan;
use Webklex\IMAP\Facades\Client;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Lang;
use Session;
use BackendAuth;
use Illuminate\Support\Facades\DB;
use Eprog\Manager\Classes\Util;

class Ksef extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    
    public $name = "ksef";

    public $folder;

    public $requiredPermissions = ['eprog.manager.access_ksef'];

    public function __construct()
    {

        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'ksef',  'ksef');
        $this->folder = '';
        
    }

    public function index()
    {

        $this->addJs('/plugins/rainlab/user/assets/js/bulk-actions.js');
        $this->asExtension('ListController')->index();

        if(!isset($_SERVER['HTTP_X_WINTER_REQUEST_HANDLER'])){
            Session::forget("widget.eprog_manager-Seller-Lists");
            Session::forget("widget.eprog_manager-Seller-Filter-listFilter");
            $this->listGetWidget()->setSort("issueDate", "desc");
        }

        //$this->listGetWidget()->setSort($this->listGetWidget()->getSortColumn(), $this->listGetWidget()->getSortDirection());
    }


    public function listExtendQuery($query, $definition = null)
    {

        $nip = str_replace("-","", SettingKsef::get("nip"));
        $nips = [$nip];
        $users =  User::orderBy("firm_name")->get();
        foreach($users as $user)
        $nips[] =   $user->firm_nip;
        if(Session::has("selected.nip") && in_array(Session::get("selected.nip"),$nips)) $nip = Session::get("selected.nip");
        $query->where("nip", $nip); 

        $subject = "Subject1";
        if(Session::has("selected.subject") && in_array(Session::get("selected.subject"),["Subject1","Subject2","Subject3","SubjectAuthorized"])) $subject = Session::get("selected.subject");
        $query->where("subject", $subject);

        $query->orderBy("issueDate","asc");

    }

    public function formExtendFields($form)
    {
        Util::checkExpired();
        Util::checkCapacity();


    }
    public function listExtendColumns($listWidget)
    {
        $subject = "Subject1";
        if(Input::filled("subject") && in_array(Input::get("subject"),["Subject1","Subject2","Subject3","SubjectAuthorized"])) $subject = Input::get("subject");

        if($subject == "Subject1"){
            $listWidget->removeColumn('sellerName');
            $listWidget->removeColumn('sellerNip');

        }
        else{
            $listWidget->removeColumn('buyerName');
            $listWidget->removeColumn('buyerIdentifierValue');
        }
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
            $ids['accounting'] = [];
            $ids['paid'] = [];
            $ids['unpaid'] = [];
            foreach ($checkedIds as $Id) {           
                switch ($bulkAction) {
                    case 'delete':
                        $ids['delete'][] = $Id;
                        break;
                    case 'accounting':
                        $ids['accounting'][] = $Id;                   
                        break;
                    case 'paid':
                        $ids['paid'][] = $Id;
                    case 'unpaid':
                        $ids['unpaid'][] = $Id;                
                        break;
                }
            }

            if(sizeof($ids['delete']) > 0) self::onDeleteMultiple($ids['delete']);
            if(sizeof($ids['accounting']) > 0) self::onAccountingMultiple($ids['accounting']);
            if(sizeof($ids['paid']) > 0) self::onPaidMultiple($ids['paid']);
            if(sizeof($ids['unpaid']) > 0) self::onUnpaidMultiple($ids['unpaid']);

        }
        else {
            Flash::error(Lang::get('eprog.manager::lang.selected_empty'));
        }

        return $this->listRefresh();
    }


    public function onDeleteMultiple($ids)
    {

         $del = ModelKsef::whereIn("id",$ids)->delete();
         if($del) Flash::success(Lang::get('eprog.manager::lang.del_selected'));
        
    }

    public function onAccountingMultiple($ids)
    {
        $count = 0;
        if(Input::filled("nip")) $count = self::accounting($ids, Input::get("nip"));
        Flash::success(e(trans('eprog.manager::lang.accounting_success'))." - ".$count);  

    }

    public function onPaidMultiple($ids)
    {

         $del = ModelKsef::whereIn("id",$ids)->update(["paid" => 1]);
         if($del) Flash::success(Lang::get('eprog.manager::lang.process_success'));
        
    }

    public function onUnpaidMultiple($ids)
    {

         $del = ModelKsef::whereIn("id",$ids)->update(["paid" => null]);
         if($del) Flash::success(Lang::get('eprog.manager::lang.process_success'));
        
    }


    public static function accounting($ids,$onip)
    {

        $sels = ModelKsef::whereIn("id",$ids)->orderBy("issueDate")->get();
        $count = 0;
        foreach($sels as $sel){

            $fa = json_decode(json_encode(simplexml_load_string(Util::removeNamespace($sel->xml))),TRUE);
            $mode = $sel->subject == "Subject1" ? 0 : 1;
            $type = array_search($sel->invoiceType, array_keys(Util::getInvoiceType()));
            $podmiot = $sel->subject == "Subject1" ? 'Podmiot2' : 'Podmiot1';
            $nip = $fa[$podmiot]['DaneIdentyfikacyjne']['NIP'] ?? $fa[$podmiot]['DaneIdentyfikacyjne']['NrVatUE'] ?? $fa[$podmiot]['DaneIdentyfikacyjne']['NrID'] ?? '';
            if(isset($fa[$podmiot]['DaneIdentyfikacyjne']['KodUE']) && isset($fa[$podmiot]['DaneIdentyfikacyjne']['NrVatUE'])) $nip = $fa[$podmiot]['DaneIdentyfikacyjne']['KodUE'].$fa[$podmiot]['DaneIdentyfikacyjne']['NrVatUE'];

            $exists = Accounting::where("nr",$sel->invoiceNumber)->where("nip",$onip)->where("client_nip",$nip)->where("mode",$mode)->where("type",$type)->count();
   
            if($exists < 1){
                
                if(isset($fa['Fa']['FaWiersz']) && isset($fa[$podmiot]['DaneIdentyfikacyjne']['Nazwa'])  && $nip != ""){         

                    $year = date("Y",strtotime($sel->saleDate));
                    $month = date("m",strtotime($sel->saleDate));                          
                    $kurs = $sel->currency != "PLN" && isset($fa['Fa']['FaWiersz']) ? (isset($fa['Fa']['FaWiersz'][0]) ? $fa['Fa']['FaWiersz'][0]['KursWaluty'] ?? 1 : $fa['Fa']['FaWiersz']['KursWaluty'] ?? 1) : 1;
                    $exchange = $sel->currency == "PLN" ? 1 : $kurs;
                    $lp = Accounting::where("year",$year)->where("month",$month)->where("nip",$onip)->max('lp') + 1;
                    $user_id = User::where("firm_nip",$nip)->first()->id ?? null;
              
                    $tax_lump =  User::where("firm_nip",$onip)->first()->tax_lump ?? "";
                    if($tax_lump == "") $tax_lump = SettingConfig::get("tax_lump") ?? "";

                    $tax_form =  User::where("firm_nip",$onip)->first()->tax_form ?? "";
                    if($tax_form  == "") $tax_form = SettingConfig::get("tax_form") ?? ""; 

                    if($tax_form == "lump" && $tax_lump  == "") throw new ValidationException(['my_field'=>e(trans('eprog.manager::lang.ksef.nolump_error'))]);
                    if($tax_form  == "") throw new ValidationException(['my_field'=>e(trans('eprog.manager::lang.ksef.notax_error'))]);
                
                    $vat_summary["net_23"] = isset($fa["Fa"]["P_13_1"]) && $fa["Fa"]["P_13_1"] != "0.00" ? round($fa["Fa"]["P_13_1"]*$exchange,2) : "0.00";
                    $vat_summary["vat_23"] = isset($fa["Fa"]["P_14_1"]) && $fa["Fa"]["P_14_1"] != "0.00" ? round($fa["Fa"]["P_14_1"]*$exchange,2) : "0.00";
                    $vat_summary["gross_23"] = $vat_summary["net_23"] + $vat_summary["vat_23"];
                    $vat_summary["net_8"] = isset($fa["Fa"]["P_13_2"]) && $fa["Fa"]["P_13_2"] != "0.00" ? round($fa["Fa"]["P_13_2"]*$exchange,2) : "0.00";
                    $vat_summary["vat_8"] = isset($fa["Fa"]["P_14_2"]) && $fa["Fa"]["P_14_2"] != "0.00" ? round($fa["Fa"]["P_14_2"]*$exchange,2) : "0.00";
                    $vat_summary["gross_8"] = $vat_summary["net_8"] + $vat_summary["vat_8"]; 
                    $vat_summary["net_5"] = isset($fa["Fa"]["P_13_3"]) && $fa["Fa"]["P_13_3"] != "0.00" ? round($fa["Fa"]["P_13_3"]*$exchange,2) : "0.00";
                    $vat_summary["vat_5"] = isset($fa["Fa"]["P_14_3"]) && $fa["Fa"]["P_14_3"] != "0.00" ? round($fa["Fa"]["P_14_3"]*$exchange,2) : "0.00";
                    $vat_summary["gross_5"] = $vat_summary["net_5"] + $vat_summary["vat_5"];

                    if($vat_summary["net_23"] != "0.00" && $vat_summary["vat_23"] != "0.00" && abs($vat_summary["net_23"]) < abs($vat_summary["vat_23"]) ) continue;
                    if($vat_summary["net_8"] != "0.00" && $vat_summary["vat_8"] != "0.00" && abs($vat_summary["net_8"]) < abs($vat_summary["vat_8"]) ) continue;
                    if($vat_summary["net_5"] != "0.00" && $vat_summary["vat_5"] != "0.00" && abs($vat_summary["net_5"]) < abs($vat_summary["vat_5"]) ) continue;

                    if($vat_summary["gross_23"] != "0.00" && $vat_summary["vat_23"] != "0.00" && abs($vat_summary["gross_23"]) < abs($vat_summary["vat_23"]) ) continue;
                    if($vat_summary["gross_8"] != "0.00" && $vat_summary["vat_8"] != "0.00" && abs($vat_summary["gross_8"]) < abs($vat_summary["vat_8"]) ) continue;
                    if($vat_summary["gross_5"] != "0.00" && $vat_summary["vat_5"] != "0.00" && abs($vat_summary["gross_5"]) < abs($vat_summary["vat_5"]) ) continue;

                    $zero_kr = isset($fa["Fa"]["P_13_6_1"]) && $fa["Fa"]["P_13_6_1"] != 0 ? round($fa["Fa"]["P_13_6_1"]*$exchange,2) : "0.00";
                    $zero_wdt = isset($fa["Fa"]["P_13_6_2"]) && $fa["Fa"]["P_13_6_2"] != 0 ? round($fa["Fa"]["P_13_6_2"]*$exchange,2) : "0.00";
                    $zero_ex = isset($fa["Fa"]["P_13_6_3"]) && $fa["Fa"]["P_13_6_3"] != 0 ? round($fa["Fa"]["P_13_6_3"]*$exchange,2) : "0.00";
                    $zero = $zero_kr+$zero_wdt+$zero_ex;

                    $p134 = isset($fa["Fa"]["P_13_4"]) && $fa["Fa"]["P_13_4"] != 0 ? round($fa["Fa"]["P_13_4"]*$exchange,2) : "0.00";
                    $p134vat = isset($fa["Fa"]["P_13_4"]) && $fa["Fa"]["P_13_4"] != 0 ? round($fa["Fa"]["P_13_4"]*$exchange*4/100,2) : "0.00";

                    $p135 = isset($fa["Fa"]["P_13_5"]) && $fa["Fa"]["P_13_5"] != 0 ? round($fa["Fa"]["P_13_5"]*$exchange,2) : "0.00";
                    $np1 = isset($fa["Fa"]["P_13_6_8"]) && $fa["Fa"]["P_13_6_8"] != "0.00" ? round($fa["Fa"]["P_13_6_8"]*$exchange,2) : "0.00";
                    $np2 = isset($fa["Fa"]["P_13_6_9"]) && $fa["Fa"]["P_13_6_9"] != "0.00" ? round($fa["Fa"]["P_13_6_9"]*$exchange,2) : "0.00";
                    $np = $np1 + $np2 + $p134 + $p135;
                    $npvat = "0.00";
                    $npvat += $p134vat;

                    $vat_summary["net_0"] = round($zero,2);
                    $vat_summary["vat_0"] = "0.00";
                    $vat_summary["gross_0"] = $vat_summary["net_0"];
                    $vat_summary["net_zw"] = isset($fa["Fa"]["P_13_7"]) && $fa["Fa"]["P_13_7"] != "0.00" ? round($fa["Fa"]["P_13_7"]*$exchange,2) : "0.00";
                    $vat_summary["vat_zw"] = "0.00";
                    $vat_summary["gross_zw"] = $vat_summary["net_zw"];
                    $vat_summary["net_np"] = round($np,2);
                    $vat_summary["vat_np"] = $npvat;
                    $vat_summary["gross_np"] =  $vat_summary["net_np"] + $vat_summary["vat_np"];                
                    $vat_summary["net_sum"] = $vat_summary["net_23"] + $vat_summary["net_8"] + $vat_summary["net_5"] +  $vat_summary["net_0"] +  $vat_summary["net_zw"] +  $vat_summary["net_np"];
                    $vat_summary["vat_sum"] = $vat_summary["vat_23"] + $vat_summary["vat_8"] + $vat_summary["vat_5"] +  $vat_summary["vat_0"] +  $vat_summary["vat_zw"] +  $vat_summary["vat_np"];
                    $vat_summary["gross_sum"] = $vat_summary["gross_23"] + $vat_summary["gross_8"] + $vat_summary["gross_5"] +  $vat_summary["gross_0"] +  $vat_summary["gross_zw"] +  $vat_summary["gross_np"];

                    $date_document = [];
                    $date_document['describe'] = isset($fa['Fa']['FaWiersz'][0]['P_7']) ? $fa['Fa']['FaWiersz'][0]['P_7'] ?? '' : $fa['Fa']['FaWiersz']['P_7'] ?? '';
                    $date_document['gtu']  = [];
                    $date_document['proc']  = [];
                    $date_document['margin']  = 0;
                    $date_document['lump']  = !$mode ? $tax_lump : '';
                    $date_document['vat_summary'] = $vat_summary;

                    $kpir = [];    
                    if($mode == 0){
                        if($tax_form != "lump")
                            $kpir["kpir9"] = $vat_summary["net_sum"];
                        else
                            $kpir["ewpz9"] = $vat_summary["net_sum"];                  
                    }
                    else{
                        if($tax_form != "lump")
                            $kpir["kpir15"] = $vat_summary["net_sum"];
                        else
                            $kpir["ewpz12"] = $vat_summary["net_sum"];
                    }

                    $date_document['kpir'] = $kpir;
                    if(!isset($fa["Fa"]["FaWiersz"][0])) $fa["Fa"]["FaWiersz"] = [$fa["Fa"]["FaWiersz"]];
                    foreach($fa["Fa"]["FaWiersz"] as $wiersz){
                       if(isset($wiersz["GTU"])) $date_document['gtu'][$wiersz["GTU"]] = 1;
                       if(!isset($wiersz["P_12"]) && isset($wiersz["P_11"])) $date_document['margin'] += $wiersz["P_11"];
                    }
                    ksort($date_document['gtu']);
                    if($date_document['margin'] == 0) $date_document['margin'] = "";
                    if(isset($fa["Fa"]["Adnotacje"]["PMarzy"]["P_PMarzy_2"])) $date_document['proc']['MR_T'] = 1;
                    if(isset($fa["Fa"]["Adnotacje"]["PMarzy"]["P_PMarzy_3_1"]) || isset($fa["Fa"]["Adnotacje"]["PMarzy"]["P_PMarzy_3_2"]) || isset($fa["Fa"]["Adnotacje"]["PMarzy"]["P_PMarzy_3_3"])) $date_document['proc']['MR_UZ'] = 1;

                    $create = [
                        "year" => $year,
                        "month" => $month,
                        "nip" => $onip,
                        "month" => $month,
                        "user_id" => $user_id,
                        "admin_id" => BackendAuth::user()->id,
                        "ksef_id" => $sel->id,
                        "lp" => $lp,
                        "prefix" => "FV",
                        "nr" => $sel->invoiceNumber,
                        "nr_ksef" => $sel->ksefNumber,
                        "mode" => $mode,
                        "type" => $type,
                        "create_at" => $sel->saleDate,
                        "vat_at" => $sel->saleDate,
                        "netto" => $sel->netAmount,
                        "vat" => $sel->vatAmount,
                        "vat_register" => $mode ? 2 : 1,
                        "brutto" => $sel->grossAmount,
                        "currency" => $sel->currency, 
                        "exchange" => $exchange,
                        "tax_form" => $tax_form,
                        "client_name" => substr($fa[$podmiot]['DaneIdentyfikacyjne']['Nazwa'] ?? '',0,255),  
                        "client_nip" => $nip,  
                        "client_country" => $fa[$podmiot]['Adres']['KodKraju'] ??  '',    
                        "client_adres1" => $fa[$podmiot]['Adres']['AdresL1'] ??  '',   
                        "client_adres2" =>  $fa[$podmiot]['Adres']['AdresL2'] ??  '',   
                        "client_email" =>  $fa[$podmiot]['DaneKontaktowe']['Email'] ??  '',
                        "client_phone" => $fa[$podmiot]['DaneKontaktowe']['Telefon'] ??  '',
                        "data_document" => json_encode($date_document),
                        'xml' => $sel->xml,
                        "created_at" => Carbon::now(),
                        "updated_at" => Carbon::now(),
                     ];

                     $id = DB::table('eprog_manager_accounting')->insertGetId($create);
                     if($id){
                        DB::statement("update eprog_manager_ksef set accounting = ".$id ." where id = '".$sel->id."'");
                        $count++;
                     }

                     $rows = DB::table('eprog_manager_accounting')->where("year",$year)->where("month",$month)->where("nip",$onip)->orderBy('vat_at', 'asc')->get();
                     $lp = 1;
                     foreach ($rows as $row) {
                         DB::table('eprog_manager_accounting')
                             ->where('id', $row->id) 
                             ->update(['lp' => $lp]);
                         $lp++;
                     }
                     
               }
            }

        }

        return $count; 
    }

    public function onKsefXml()
    {
        $file = self::onKsefXmlGenerate(Input::segment(6));
        if(file_exists($file)) return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getfile?file='.urlencode($file));
    }

    public static function onKsefXmlGenerate($id)
    {
        $invoice = ModelKsef::find($id);  
        if($invoice && $invoice->ksefNumber){             
            $file = storage_path('temp/public/'.str_replace("/","_",$invoice->invoiceNumber).'_'.str_replace(".","",$invoice->sellerName).'.xml');
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

        $invoice = ModelKsef::find($id);  
        if($invoice && $invoice->ksefNumber){   
            $file = storage_path('temp/public/'.str_replace("/","_",$invoice->invoiceNumber).'_'.str_replace(".","",$invoice->sellerName).'.pdf');
            $pdf = ClassKsef::generateInvoicePdf($invoice); 
            file_put_contents($file, $pdf);  
            return $file;    
        }
        else
            Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));
            
    }

    public function onKsefAccounting()
    {

            $invoice = ModelKsef::find(Input::segment(6)); 
            if($invoice && $invoice->ksefNumber){   
                $ids = [$invoice->id];
                $count = self::accounting($ids,$invoice->nip);
                if($count > 0)
                Flash::success(e(trans('eprog.manager::lang.accounting_success_one')));
            }
            
    }


    public function onKsefDownload()
    {

        $nip = str_replace("-","", SettingKsef::get("nip"));
        $nips = [$nip];
        $users =  User::orderBy("firm_name")->get();
        foreach($users as $user)
        $nips[] =   $user->firm_nip;
        if(Session::has("selected.nip") && in_array(Session::get("selected.nip"),$nips)) $nip = Session::get("selected.nip");

        $subject = "Subject1";
        if(Session::has("selected.subject") && in_array(Session::get("selected.subject"),["Subject1","Subject2","Subject3","SubjectAuthorized"])) $subject = Session::get("selected.subject");

        Session::forget("ksef");
        $export = ClassKsef::exportInvoices($nip, $subject, post("from"), post("to"));
        Session::forget("ksef");
        Session::put("selected.from",post("from"));
        Session::put("selected.to",post("to"));
        
        return [$export[0], date("Y-m-d", strtotime($export[1]))];
    }

    public function onClear()
    {

        $nip = str_replace("-","", SettingKsef::get("nip"));
        $nips = [$nip];
        $users =  User::orderBy("firm_name")->get();
        foreach($users as $user)
        $nips[] =   $user->firm_nip;
        if(Session::has("selected.nip") && in_array(Session::get("selected.nip"),$nips)) $nip = Session::get("selected.nip");

        $subject = "Subject1";
        if(Session::has("selected.subject") && in_array(Session::get("selected.subject"),["Subject1","Subject2","Subject3","SubjectAuthorized"])) $subject = Session::get("selected.subject");

        ModelKsef::where("nip",$nip)->where("subject",$subject)->delete();

    }

}