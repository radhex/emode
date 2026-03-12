<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use Rainlab\User\Models\User;
use Eprog\Manager\Models\SettingConfig;
use Eprog\Manager\Models\Payroll as ModelPayroll;
use Eprog\Manager\Models\SettingNumeration;
use Eprog\Manager\Classes\Util;
use October\Rain\Exception\ValidationException;
use Carbon\Carbon;
use BackendMenu;
use Redirect;
use Session;
use Mpdf\Mpdf;
use Input;

class Payroll extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = ['eprog.manager.manage_accounting'];

    public function __construct(){

        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'accounting', 'payroll');
        
    }

    public function listExtendQuery($query, $definition = null)
    {
      
        $nip = Session::has("selected.nip") ? Session::get("selected.nip") : SettingConfig::get("nip"); 

        if($nip)
            $query->where("nip", $nip);  
        else 
            $query->where("id",0); 

        $query->orderBy("id", "asc"); 


    }

    public function formExtendFields($form)
    {
        Util::checkExpired();
        Util::checkCapacity();
        
        if(!isset($_SESSION)) session_start();
    
        if(Input::segment(5) == "create") { 
                 
            $form->getField('nr')->value = self::number(0);

        }


        if(SettingNumeration::get("payroll_block_number")) $form->getField('nr')->readOnly = true;


    }

    public function onExport()
    {

        $url = config('cms.backendUri')."/eprog/manager/export/payrollxml";
        $arg = "";
        if(isset($_POST['checked'])) $arg .= "&checked=".implode(",",$_POST['checked']);        if(isset($_POST['year'])) $arg .= "&year=".$_POST['year'];
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
    

        $nip = Session::get("selected.nip") ?? '';
        $year = Session::get("selected.year") ?? '';
        $month = Session::get("selected.month") ?? '';

        $firm = User::where("firm_nip",$nip)->first()->firm_name ?? '';
        if($nip == SettingConfig::get("nip")) $firm = SettingConfig::get("firm");

        $payroll = ModelPayroll::find($id);
        $data = json_decode($payroll->data, true) ?? [];

        $filename = "Lista płac ".str_replace("/","_",$payroll->nr).".pdf";
        $orient = "L";


;
        $html = "<table class=\"vat\">";
        $html .= "<thead>
        <tr>
             <th colspan=\"21\" style=\"border:0;text-align:left;font-size:16px;font-weight:normal;padding-left:0px;padding-bottom:20px\">
                Lista płac nr {$payroll->nr} za okres {$payroll->period} - {$firm} NIP: {$nip} &nbsp;&nbsp;&nbsp;&nbsp;Data wypłaty: ".date("Y-m-d",strtotime($payroll->pay_at))."
            </th>
        </tr>
        <tr>
            <th rowspan=\"2\" style=\"width:40pt\">LP</th>
            <th rowspan=\"2\" style=\"width:120pt\">Nazwisko i imię</th>
            <th colspan=\"3\">Składniki wynagrodzenia za czas pracy </th>
            <th rowspan=\"2\">Podst. wymiaru składek ubezp. społecznych</th>
            <th rowspan=\"2\">Wynagr. za czas niezdolności do pracy</th>
            <th rowspan=\"2\">Razem przychód</th>
            <th colspan=\"2\">PPK</th>
            <th colspan=\"4\">Odliczenia od dochodu </th>
            <th rowspan=\"2\">Podst. wymiaru składek ubezp. zdrowotnego</th>
            <th rowspan=\"2\">Koszty uzyskania przychodu</th>
            <th rowspan=\"2\">Podstawa opodatkowania</th>
            <th rowspan=\"2\">Potrącona zaliczka na podatek dochodowy</th>
            <th rowspan=\"2\">Ubezpieczenie zdrowotne</th>
            <th rowspan=\"2\">Należna zaliczka na podatek dochodowy</th>
            <th rowspan=\"2\">Do wypłaty</th>
        </tr>
        <tr>
            <th>Wynagrodzenie zasadnicze</th>
            <th></th>
            <th></th>
            <th>Składka pracownika</th>
            <th>Składka pracodawcy</th>
            <th>Ubezp. emerytalne</th>
            <th>Ubezp. rentowe</th>
            <th>Ubezp. chorobowe</th>
            <th>Razem składki na ub. społ.</th>
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
            <th>18</th>
            <th>19</th>
            <th>20</th>
            <th>21</th>
        </tr>
        </thead><tbody>";

        foreach ($data as $dat) {

            $html .= "<tr>

                <td style=\"text-align:center\">".$dat['p1']."</td>
                <td style=\"text-align:left\">".$dat['p2']."</td>
                <td style=\"text-align:right\">".number_format((float)($dat['p3'] ?? 0),2,","," ")."</td>
                <td style=\"text-align:right\">".number_format((float)($dat['p4'] ?? 0),2,","," ")."</td>
                <td style=\"text-align:right\">".number_format((float)($dat['p5'] ?? 0),2,","," ")."</td>
                <td style=\"text-align:right\">".number_format((float)($dat['p6'] ?? 0),2,","," ")."</td>
                <td style=\"text-align:right\">".number_format((float)($dat['p7'] ?? 0),2,","," ")."</td>
                <td style=\"text-align:right\">".number_format((float)($dat['p8'] ?? 0),2,","," ")."</td>
                <td style=\"text-align:right\">".number_format((float)($dat['p9'] ?? 0),2,","," ")."</td>
                <td style=\"text-align:right\">".number_format((float)($dat['p10'] ?? 0),2,","," ")."</td>
                <td style=\"text-align:right\">".number_format((float)($dat['p11'] ?? 0),2,","," ")."</td>
                <td style=\"text-align:right\">".number_format((float)($dat['p12'] ?? 0),2,","," ")."</td>
                <td style=\"text-align:right\">".number_format((float)($dat['p13'] ?? 0),2,","," ")."</td>
                <td style=\"text-align:right\">".number_format((float)($dat['p14'] ?? 0),2,","," ")."</td>
                <td style=\"text-align:right\">".number_format((float)($dat['p15'] ?? 0),2,","," ")."</td>
                <td style=\"text-align:right\">".number_format((float)($dat['p16'] ?? 0),2,","," ")."</td>
                <td style=\"text-align:right\">".number_format((float)($dat['p17'] ?? 0),2,","," ")."</td>
                <td style=\"text-align:right\">".number_format((float)($dat['p18'] ?? 0),2,","," ")."</td>
                <td style=\"text-align:right\">".number_format((float)($dat['p19'] ?? 0),2,","," ")."</td>
                <td style=\"text-align:right\">".number_format((float)($dat['p20'] ?? 0),2,","," ")."</td>
                <td style=\"text-align:right\">".number_format((float)($dat['p21'] ?? 0),2,","," ")."</td>

            </tr>";
       
        }


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

    public static function number($type)
    {

            $nip = Session::get("selected.nip") ?? '';
            $firm = User::where("firm_nip",$nip)->first()->firm_name ?? '';
            if($nip == SettingConfig::get("nip")) $firm = SettingConfig::get("firm");

            $types = ["LP"];

            $payroll_type = SettingNumeration::get("payroll_type") ?? "month";

            $prefix = SettingNumeration::get("payroll_prefix") ?? "";
            $separator = SettingNumeration::get("payroll_separator") ?? "/";
            $sufix = SettingNumeration::get("payroll_sufix") ?? "";

            $max = ModelPayroll::where("nip",$nip)->where("nr","!=","")->orderBy("id","desc")->first()->nr ?? null;
            $max = preg_replace('/^'.preg_quote($prefix, '/').'/', '',$max);
            $max = preg_replace('/'.preg_quote($sufix, '/'). '$/', '',$max);

            if(isset($max)){
                $max = explode($separator, $max);
                settype($max[1], "integer");
            }

            $payroll_number = "";
            $month = Util::invdate(Carbon::now(), 3);
            $year = Util::invdate(Carbon::now(), 4);

            if($payroll_type == "month"){
                if(isset($max[2]) && $max[2] == $month)
                    $number = Util::zero_first(++$max[1]);
                else
                    $number = "01";

                $payroll_number =  $prefix.$types[$type].$separator.$number.$separator.$month.$separator.$year.$sufix;
            }

            if($payroll_type == "year"){
                if(isset($max[2]) && $max[2] == $year)
                    $number = Util::zero_first(++$max[1]);
                else
                    $number = "01";

                $payroll_number =  $prefix.$types[$type].$separator.$number.$separator.$year.$sufix;
            }


            return $payroll_number;

    }

}