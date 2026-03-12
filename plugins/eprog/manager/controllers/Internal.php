<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Eprog\Manager\Models\Internal as ModelInternal;
use Eprog\Manager\Models\Internalvalue;
use Eprog\Manager\Models\SettingConfig;
use Eprog\Manager\Models\SettingNumeration;
use Eprog\Manager\Controllers\Taxfile;
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
use System\Models\File;
use Mpdf\Mpdf;

class Internal extends Controller
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
        BackendMenu::setContext('Eprog.Manager', 'accounting', 'internal');

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
                    $redirect_uri = (isset($_SERVER['HTTPS']) ? "https":"http")."://".$_SERVER["SERVER_NAME"]."/".config('cms.backendUri')."/eprog/manager/internal";
                    header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
                                                    
                }
            }
        }

        
    }

    public function listExtendQuery($query, $definition = null){

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
        
        if(!isset($_SESSION)) session_start();
 
        $xml = null;

        if(Input::segment(5) == "update") { 
        
            $internal = ModelInternal::find(Input::segment(6));    
            if($internal->xml)$xml = $internal->xml;

        }

        if(Input::segment(5) == "create") { 
                 
            $form->getField('place')->value = SettingConfig::get("city");
            $form->getField('nr')->value = self::number(0);




        }

        if(SettingNumeration::get("internal_block_number")) $form->getField('nr')->readOnly = true;



    }

    public function listExtendColumns($list)
    {
        $currency = SettingConfig::get("currency") == null ? "PLN" : SettingConfig::get("currency");
        $list->getColumn("amount")->label = e(trans("eprog.manager::lang.value"))." ". $currency;

  
    } 





    public function onExport()
    {

        $url = config('cms.backendUri')."/eprog/manager/export/internalxml";
        $arg = "";
        if(isset($_POST['checked'])) $arg .= "&checked=".implode(",",$_POST['checked']);
        if(isset($_POST['nip'])) $arg .= "&nip=".$_POST['nip'];
        if(strlen($arg) > 0) $arg[0] = "?";
        return Redirect::to($url.$arg);

    }


    public function onPdf()
    {
        $file = self::generatePdf(Input::segment(6));
        return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getfile?file='.urlencode($file));
    }



    public static function generatePdf($id)
    {

        $values = [];
        $internal = ModelInternal::find($id);  
        if($internal) $values = Internalvalue::where("internal_id","=",$id)->orderBy("id")->get();
      
        $nip = Session::get("selected.nip") ?? '';

        $firm = User::where("firm_nip",$nip)->first()->firm_name ?? '';
        if($nip == SettingConfig::get("nip")) $firm = SettingConfig::get("firm");

        $filename = "Dowód wewnętrzny ".str_replace("/","_",$internal->nr).".pdf";
        $orient = "P";

        $html = "<table class=\"vat\">";
        $html .= "<thead>
        <tr>
          <th colspan=\"10\" style=\"border:0;text-align:right;font-size:14px;font-weight:normal;padding-left:0px;padding-bottom:20px\">
             {$internal->place} ".date("Y-m-d",strtotime($internal->create_at))."
          </th>
        </tr>
        <tr>
          <th colspan=\"10\" style=\"border:0;text-align:left;font-size:16px;font-weight:normal;padding-left:0px;padding-bottom:20px\">
              Dowód wewnętrzny nr {$internal->nr} - {$firm} NIP: {$nip}
          </th>
        </tr>
        <tr>
          <th>Lp.</th>
          <th style=\"width:250pt\">Nazwa towaru/usługi</th>
          <th>Ilość</th>
          <th>Jednostka miary</th>
          <th>Cena jednostkowa PLN</th>
          <th>Wartość PLN</th>
        </tr>

        </thead><tbody>";

        $sum  = 0;
        $l = 1;    
        foreach ($values as $val) {

            $sum += round($val->quantity*$val->amount,2);
            $html .= "<tr>
              <td style=\"text-align:left\">".$l."</td>
              <td style=\"text-align:left\">".$val->product."</td>
              <td style=\"text-align:left\">".$val->quantity."</td>
              <td style=\"text-align:left\">".$val->measure."</td>
              <td style=\"text-align:right\">".Util::dkwota($val->amount)."</td>
              <td style=\"text-align:right\">".Util::dkwota($val->quantity*$val->amount)."</td>
            </tr>";

                $l++;   
        }
 
            $html .= "<tr>
             <td colspan=\"5\" style=\"text-align:right;border:0\">Razem:</td>
             <td style=\"text-align:right\">".Util::dkwota($sum)."</td>
            </tr>";

            if($internal->record)
            $html .= "<tr>
             <td colspan=\"6\" style=\"text-align:left;border:0\">Nr pozycji w księdze: ".$internal->record."</td>

            </tr>";

        $html .= "</tbody></table>";

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


    public function onProforma()
    {

        $internal = ModelInternal::find(Input::segment(6));  
        if($internal && $internal->nr){   
            $internal->nr = str_replace("/","_",$internal->nr);
            $xml = $internal->xml;      
            $html = Ksef::internalHtmlPro($xml,$internal->nr);
            $file = storage_path('temp/public/'.$internal->nr);
            $pdf = SnappyPdf::loadHTML($html)->output(); 
            file_put_contents($file, $pdf);  
            if(file_exists($file))
            return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getInternalPdf/'.$internal->nr);     
        }
        else
            Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));
            
    }





    public static function number($type)
    {

            $nip = Session::get("selected.nip") ?? '';
            $firm = User::where("firm_nip",$nip)->first()->firm_name ?? '';
            if($nip == SettingConfig::get("nip")) $firm = SettingConfig::get("firm");

            $types = ["DW"];

            $internal_type = SettingNumeration::get("internal_type") ?? "month";

            $prefix = SettingNumeration::get("internal_prefix") ?? "";
            $separator = SettingNumeration::get("internal_separator") ?? "/";
            $sufix = SettingNumeration::get("internal_sufix") ?? "";

            $max = ModelInternal::where("nip",$nip)->where("nr","!=","")->orderBy("id","desc")->first()->nr ?? null;
            $max = preg_replace('/^'.preg_quote($prefix, '/').'/', '',$max);
            $max = preg_replace('/'.preg_quote($sufix, '/'). '$/', '',$max);

            if(isset($max)){
                $max = explode($separator, $max);
                settype($max[1], "integer");
            }

            $internal_number = "";
            $month = Util::invdate(Carbon::now(), 3);
            $year = Util::invdate(Carbon::now(), 4);

            if($internal_type == "month"){
                if(isset($max[2]) && $max[2] == $month)
                    $number = Util::zero_first(++$max[1]);
                else
                    $number = "01";

                $internal_number =  $prefix.$types[$type].$separator.$number.$separator.$month.$separator.$year.$sufix;
            }

            if($internal_type == "year"){
                if(isset($max[2]) && $max[2] == $year)
                    $number = Util::zero_first(++$max[1]);
                else
                    $number = "01";

                $internal_number =  $prefix.$types[$type].$separator.$number.$separator.$year.$sufix;
            }


            return $internal_number;

    }


}