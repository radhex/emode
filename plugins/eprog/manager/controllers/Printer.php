<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Input;
use BackendAuth;
use Eprog\Manager\Models\Scheduler;
use Eprog\Manager\Models\Invoice;
use Eprog\Manager\Models\Project;
use Eprog\Manager\Models\Order;
use Eprog\Manager\Models\Work;
use Eprog\Manager\Classes\Util;
use Eprog\Manager\Classes\Ksef;
use App;
use Response;
use Redirect;
use Session;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF ;
use File;
use View;
use Auth;
//use PDF;
use Webklex\IMAP\Facades\Client;

class Printer extends Controller
{

    public $project;
    

    public static function getAfterFilters() {return [];}
    public static function getBeforeFilters() {return [];}
    public function getMiddleware() {return [];}
    public function callAction($method, $parameters=false) {
        return call_user_func_array(array($this, $method), $parameters);
    }


    public function __construct()
    {
        
    	View::addLocation('plugins/eprog/manager/controllers/printer');
     
    }

    public function projectprint($id)
    {

        $pdf = PDF::loadView('project', compact('id'));
        return $pdf->stream(lcfirst(e(trans('eprog.manager::lang.project_one'))).'.pdf');

    }

    public function projectpdf($id)
    {

        $pdf = PDF::loadView('project', compact('id'));
        return $pdf->download(lcfirst(e(trans('eprog.manager::lang.project_one'))).'.pdf');

    }

    public function schedulerprint($id)
    {

        $pdf = PDF::loadView('scheduler', compact('id'));
        return $pdf->stream(lcfirst(e(trans('eprog.manager::lang.scheduler'))).'.pdf');

    }

    public function schedulerpdf($id)
    {

        $pdf = PDF::loadView('scheduler', compact('id'));
        return $pdf->download(lcfirst(e(trans('eprog.manager::lang.scheduler'))).'.pdf');

    }

    public function invoiceprint($id)
    {



    }

    public function invoicepdf($id)
    {
        $invoice = Invoice::find($id);  
        if($invoice->user_id == Auth::getUser()->id){
            if(!$invoice->ksefNumber) $invoice->ksefNumber = "Offline";
            if($invoice && $invoice->ksefNumber){   
                $file = storage_path('temp/public/pdf_'.$invoice->ksefNumber);
                $pdf = KSef::generateInvoicePdf($invoice); 
                file_put_contents($file, $pdf);
                if(file_exists($file))
                return  response()->download($file,$invoice->ksefNumber.".pdf")->deleteFileAfterSend(true);    
            }
            else
                Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));
        }

    }

    public function orderprint($id)
    {

        //return view('order', compact('id'));


        $pdf = PDF::loadView('order', compact('id'));
        $file = str_replace("&oacute;","o",lcfirst(e(trans('eprog.manager::lang.order_one'))));
        return $pdf->stream($file.'.pdf');


    }

    public function orderpdf($id)
    {

        $order = Order::find($id);  
        if($order->user_id == Auth::getUser()->id){
            if(!$order->ksefNumber) $order->ksefNumber = "Offline";
            if($order && $order->nr){   
                $order->nr = str_replace("/","_",$order->nr);
                $xml = $order->xml;      
                $html = Ksef::orderHtml($xml,$order->nr);
                $file = storage_path('temp/public/'.$order->nr);
                $pdf = PDF::loadHTML($html)->output(); 
                file_put_contents($file, $pdf);
                if(file_exists($file))
                return  response()->download($file,$order->nr.".pdf")->deleteFileAfterSend(true);    
            }
            else
                Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));
        }

    }

    public function proformapdf($id)
    {

        $order = Order::find($id);  
        if($order->user_id == Auth::getUser()->id){
            if(!$order->ksefNumber) $order->ksefNumber = "Offline";
            if($order && $order->nr){   
                $order->nr = str_replace("/","_",$order->nr);
                $xml = $order->xml;      
                $html = Ksef::orderHtmlPro($xml,$order->nr);
                $file = storage_path('temp/public/'.$order->nr);
                $pdf = PDF::loadHTML($html)->output(); 
                file_put_contents($file, $pdf);
                if(file_exists($file))
                return  response()->download($file,$order->nr.".pdf")->deleteFileAfterSend(true);    
            }
            else
                Flash::error(e(trans('eprog.manager::lang.ksef.download_error')));
        }

    }

    public function inboxprint($id, $folder)
    {

        $client = Client::account('default');
        $client->connect();

        $folder = $client->getFolder($folder);

        $message = $folder->query()->getMessage($id);
        $date = Util::dateFormat($message->getDate());
        $title =  iconv('utf-8', 'utf-8//IGNORE', $message->getSubject()->toArray()[0]);
        $body =  iconv('utf-8', 'utf-8//IGNORE', strlen($message->getHtmlBody()) > 0 ? $message->getHtmlBody() : $message->getTextBody());
        $from =  iconv('utf-8', 'utf-8//IGNORE', $message->getFrom());
        $to =  iconv('utf-8', 'utf-8//IGNORE', $message->getTo());
        $cc =  iconv('utf-8', 'utf-8//IGNORE', $message->getCc());



        return view('inbox', compact('date','title','from', 'to', 'cc', 'body'));


   

    }


}