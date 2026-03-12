<?php namespace Eprog\Manager\Models;

use Model;
use RainLab\User\Models\User;
use RainLab\User\Models\UserGroup;
use RainLab\User\Models\UserGroups;
use Input;
use ApplicationException;
use Flash;
use Session;
use Mail as SendMail;
use Carbon\Carbon;
use Redirect;
use Webklex\IMAP\Facades\Client;
use Eprog\Manager\Classes\Util;
use October\Rain\Exception\ValidationException;

/**
 * Model
 */
class Invoicepay extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \Sushi\Sushi;

    
    /*
     * Validation
     */    
    public $rules = [
        'title'    => 'required',
        'body'    => 'required'
    ];

    public $customMessages = [

        'title.required' => 'eprog.manager::lang.valid_title',
        'body.required' => 'eprog.manager::lang.valid_body'
      
    ];
    
    
    /**
     * @var string The database table used by the model.
     */
    

    public $table = 'eprog_manager_invoicepay';

    public $folder;

    public $perPage;

    public function setRecordsPerPage($perPage){

        $this->perPage = $perPage;

    }

    public function getFolder()
    {
     
        $this->folder = '';
        return $this->folder;

    }

    public function beforeFetch()
    {
     
        $this->getCurrent();

    }

    public function getPerPage()
    {
     
        $this->perPage = config("imap.perPage.default");
        return $this->perPage;

    }

    public function getPage()
    {

        return  post('page') ? post('page') : 1;

    }

    public function getCurrent()
    {

        if(Input::has("term"))
            $term = Input::get("term");
        else
            $term = post("listToolbarSearch[term]");
     
        $curr = $this->getPage();


        if(Input::segment(7) > 0)
            $curr = Input::segment(7);
        else if(Input::has("page"))
            $curr = Input::get("page");

        return intval($curr);

    }

    public function getPageFrom()
    {

        $page = $this->getCurrent()*$this->getPerPage() - $this->getPerPage() + 1;

        if($this->getTotal() == 0)
            $page = 0;

        return $page;
    }  

    public function getPageTo()
    {

        if($this->getCurrent() == $this->getLast())
            $page = $this->getTotal();
        else
            $page = $this->getCurrent()*$this->getPerPage();

        if($this->getTotal() == 0)
            $page = 0;

        return $page;
    }    
  

    public function getTotal()
    {

        $term = "";
        if(Input::filled("term"))
            $term = trim(Input::get("term"));
        else
            $term = post("listToolbarSearch[term]");

        $invoices = Util::getApi("invoices",$term);

        return sizeof($invoices);

    }

    public function getLast()
    {
     
        return  intval(ceil($this->getTotal()/$this->getPerPage()));

    }

    public function getRows()
    {
    
        $term = "";
        if(Input::filled("term"))
            $term = trim(Input::get("term"));
        else
            $term = post("listToolbarSearch[term]");
    
        $data = [];
        $invoices = Util::getApi("invoices",$term);


        foreach($invoices as $invoice)
        $data[] = ['id' => $invoice['id'], 'type' => $invoice['type'], 'nr' => $invoice['nr'], 'ksefNumber' => $invoice['ksefNumber'], "netto" => $invoice['netto'], "vat" => $invoice['vat'], "brutto" => $invoice['brutto'], "currency" => $invoice['currency'], "seller_name" => $invoice['seller_name'], "make_at" => $invoice['make_at'], "create_at" => $invoice['create_at'], "xml" => $invoice['xml'], "payment" => $invoice['payment']];


        return $data;

    }


}