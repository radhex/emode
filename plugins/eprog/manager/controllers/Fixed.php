<?php namespace Eprog\Manager\Controllers;

use Eprog\Manager\Classes\Util;
use Backend\Classes\Controller;
use Rainlab\User\Models\User;
use Eprog\Manager\Models\SettingConfig;
use Eprog\Manager\Models\Fixed as ModelFixed;
use Eprog\Manager\Controllers\Taxfile;
use October\Rain\Exception\ValidationException;
use BackendMenu;
use Redirect;
use Session;
use Mpdf\Mpdf;
use Input;
use Flash;
use Lang;

class Fixed extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = ['eprog.manager.manage_accounting'];


    public function __construct()
    {

        parent::__construct();
   	    BackendMenu::setContext('Eprog.Manager', 'accounting', 'fixed');

    }

    public function listExtendQuery($query, $definition = null)
    {
      
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
    
        if(Input::segment(5) == "create") { 
            
            $nip = Session::has("selected.nip") ? Session::get("selected.nip") : SettingConfig::get("nip"); 
            $lp =  ModelFixed::where("nip", $nip)->orderBy("lp","desc")->first()->lp ?? 0;  
            $form->getField('lp')->value = $lp+1;

        }

        if(Input::segment(5) == "update") { 
            

            $form->getField('initial')->value = number_format($form->getField('initial')->value,2,","," ");
            $form->getField('initialupdate')->value = number_format($form->getField('initialupdate')->value,2,","," ");

        }

    }


    public function onExport()
    {

        $url = config('cms.backendUri')."/eprog/manager/export/fixedxml";
        $arg = "";
        if(isset($_POST['checked'])) $arg .= "&checked=".implode(",",$_POST['checked']);
        if(isset($_POST['nip'])) $arg .= "&nip=".$_POST['nip'];
        if(strlen($arg) > 0) $arg[0] = "?";
        return Redirect::to($url.$arg);


    }

    public function onDoc()
    {
        $file = self::generatePdf();
        return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getfile?file='.urlencode($file));
    }


    public static function generatePdf()
    {

    
        $nip = Session::get("selected.nip") ?? '';

        $firm = User::where("firm_nip",$nip)->first()->firm_name ?? '';
        if($nip == SettingConfig::get("nip")) $firm = SettingConfig::get("firm");

        $filename = "Wykaz środków trwałych.pdf";
        $orient = "L";

        $fixed = ModelFixed::where("nip",$nip)->where("disp",1)->orderBy('id','asc')->get();



        $html = "<table class=\"vat\">";
        $html .= "<thead>
        <tr>
            <th colspan=\"10\" style=\"border:0;text-align:left;font-size:16px;font-weight:normal;padding-left:0px;padding-bottom:20px\">
                Wykaz środków trwałych oraz wartości niematerialnych i prawnych - {$firm} NIP: {$nip}
            </th>
        </tr>
        <tr>
            <th>Lp.</th>
            <th>Data nabycia</th>
            <th>Data przyjęcia do używania</th>
            <th>Nazwa i nr dokumentu stwierdzającego nabycie</th>
            <th>Nazwa środka trwałego lub wartości niematerialnej i prawnej</th>
            <th>Symbol Klasyfikacji Środków Trwałych (KŚT)</th>
            <th>Wartość początkowa</th>
            <th>Stawka amortyzacyjna</th>
            <th>Zaktualizowana wartość początkowa</th>
            <th>Przyczyna likwidacji</th>
            <th>Data likwidacji/zbycia</th>
        </tr>

        </thead><tbody>";

        foreach ($fixed as $fix) {
            
            $html .= "<tr>
                <td style=\"text-align:left\">".$fix->lp."</td>
                <td style=\"text-align:left\">".date("Y-m-d",strtotime($fix->create_at))."</td>
                <td style=\"text-align:left\">".date("Y-m-d",strtotime($fix->make_at))."</td>
                <td style=\"text-align:left\">".$fix->name."</td>
                <td style=\"text-align:left\">".$fix->fixed."</td>
                <td style=\"text-align:left\">".$fix->kst."</td>
                <td style=\"text-align:right\">".number_format($fix->initial ?? 0,2,","," ")."</td>
                <td style=\"text-align:left\">".$fix->rate."</td>
                <td style=\"text-align:right\">".number_format($fix->initial ?? 0,2,","," ")."</td>
                <td style=\"text-align:left\">".$fix->liquidation."</td>
                <td style=\"text-align:left\">".date("Y-m-d",strtotime($fix->liquidation_at))."</td>

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


}