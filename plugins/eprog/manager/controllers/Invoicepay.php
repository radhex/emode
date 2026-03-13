<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Input;
use Redirect;
use BackendAuth;
use Flash;
use October\Rain\Exception\ValidationException;
use Eprog\Manager\Models\Mailing as ModelMailing;
use Eprog\Manager\Models\Invoice as ModelInvoice;
use RainLab\User\Models\User;
use RainLab\User\Models\UserGroup;
use RainLab\User\Models\UserGroups;
use Mail;
use Carbon\carbon;
use Artisan;
use Webklex\IMAP\Facades\Client;
use Eprog\Manager\Models\Inbox as ModelInbox;
use Eprog\Manager\Classes\Ksef;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Eprog\Manager\Classes\Util;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use View;



use Lang;


class Invoicepay extends Controller
{
    public $implement = ['Extends\Backend\Behaviors\ListController'];
    
    public $listConfig = 'config_list.yaml';
    public $folder;

    public $requiredPermissions = ['eprog.manager.access_pay'];

    public function __construct()
    {

        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'invoicepay',  'invoicepay');
        $this->folder = '';
        
    }

    public function index()
    {


        $this->addJs('/plugins/rainlab/user/assets/js/bulk-actions.js');
        $this->asExtension('ListController')->index();

    }

    public function listExtendQuery($query, $definition = null)
    {
      
  		$query->orderBy("id", "desc");	

    }



    public function onPdf()
    {
     
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_invoice')) return;


        $invoice = new ModelInvoice();
        $invoice->nr = post('nr') ?? "";
        $invoice->ksefNumber = post('ksefNumber') ?? ""; 
        $invoice->xml = urldecode(post('xml')) ?? "";  
        $invoice->upo = ""; 
        $invoice->seller_name = post('seller_name') ?? "";

        if($invoice && $invoice->xml){   
            $file = storage_path('temp/public/'.str_replace("/","_",$invoice->nr).'_'.str_replace(".","",$invoice->seller_name).'.pdf');
            if($invoice->ksefNumber){                
                $pdf = KSef::generateInvoicePdf($invoice); 
            }
            else{
                $html = Ksef::invoiceMyHtml($invoice->xml,$invoice->nr);
                $pdf = SnappyPdf::loadHTML($html)->output(); 
            }
            file_put_contents($file, $pdf);  
            if(file_exists($file)) return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getfile?file='.urlencode($file));      
        }
        else
            Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));
       
    
    }


}