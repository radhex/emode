<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Input;
use Redirect;
use BackendAuth;
use Flash;
use October\Rain\Exception\ValidationException;
use Eprog\Manager\Models\Mailing as ModelMailing;
use Eprog\Manager\Models\Order as ModelOrder;
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


class Orderpay extends Controller
{
    public $implement = ['Extends\Backend\Behaviors\ListController'];
    
    public $listConfig = 'config_list.yaml';
    public $folder;

    public $requiredPermissions = ['eprog.manager.access_order'];

    public function __construct()
    {

        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'invoicepay',  'orderpay');
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
     
        if(!BackendAuth::getUser()->hasAccess('eprog.manager.access_order')) return;


        $order = new ModelOrder();
        $order->nr = post('nr') ?? "";
        $order->xml =  urldecode(post('xml')) ?? ""; 
        $order->seller_name = post('seller_name') ?? "";

        if($order && $order->nr){   

            $file = storage_path('temp/public/'.str_replace("/","_",$order->nr).'_'.str_replace(".","",$order->seller_name).'.pdf');
            $html = Ksef::orderHtml($order->xml,$order->nr);
            $pdf = SnappyPdf::loadHTML($html)->output(); 
            file_put_contents($file, $pdf); 
            if(file_exists($file)) return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getfile?file='.urlencode($file));       
        }
        else
            Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));
       
    
    }


}