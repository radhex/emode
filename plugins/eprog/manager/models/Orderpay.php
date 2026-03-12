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

/**
 * Model
 */
class Orderpay extends Model
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
    

    public $table = 'eprog_manager_orderpay';

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

        $orders = Util::getApi("orders",$term);

        return sizeof($orders);

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
        $orders = Util::getApi("orders",$term);


        foreach($orders as $order)
        $data[] = ['id' => $order['id'], 'nr' => $order['nr'],  "netto" => $order['netto'], "vat" => $order['vat'], "brutto" => $order['brutto'], "currency" => $order['currency'], "seller_name" => $order['seller_name'],  "create_at" => $order['create_at'], "xml" => $order['xml'], "payment" => $order['payment']];


        return $data;

    }



}