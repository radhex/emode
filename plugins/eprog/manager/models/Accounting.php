<?php namespace Eprog\Manager\Models;

use Model;
use Eprog\Manager\Models\Accounting as ModelAccounting;
use Eprog\Manager\Models\Accountingvalue;
use Eprog\Manager\Models\Accounting;
use Eprog\Manager\Models\SettingStatus;
use Eprog\Manager\Models\Vat;
use Eprog\Manager\Classes\Util;
use Eprog\Manager\Controllers\Accounting as AccountingController;
use October\Rain\Exception\ValidationException;
use Input;
use Flash;
use Rainlab\User\Models\User;
use BackendAuth;
use Response;
use Session;
use Lang;
use Eprog\Manager\Classes\Ksef;
use DB;

/**
 * Model
 */
class Accounting extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Validation
     */

    public static $rule = [
    	'create_at'    	=> 'required',
        'vat_at'     => 'required',
        'lp'         => 'required',
        'prefix'         => 'required',
        'nr'         => 'required',
    	'client_name'    => 'required',
    	'client_nip'     => 'required|nip',
    	'client_adres1'  => 'required',
    	'client_country' => 'required',
    	'client_email'   => 'email',
    	//'seller_phone'   => 'phone',
    	'currency'  => 'required',
        'exchange' => 'required|numeric|gt:0|max:1000000',
        'tax_form' => 'required',
    ];

    public $rules = [];

    public $customMessages = [



    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'eprog_manager_accounting';

    protected $fillable = [
        'ksef_id',
        'year',
        'month',
        'nip',
        'lp',
        'nr',
        'nr_ksef',
        'prefix',
        'mode',
        'type',
        'create_at',
        'vat_at',
        'lump',
        'currency',
        'exchange',
        'tax_form',
        'client_name',
        'client_nip',
        'client_country',
        'client_adres1',
        'client_adres2',
        'client_email',
        'client_phone',
        'data_document',        
        'xml',
        'approve',
        'paid',
        'created_at',
        'updated_at'
    ];


    public $belongsTo = [
            'user' => ['RainLab\User\Models\User'],
	    	'admin'=>  ['Backend\Models\User'],
    ];


    public function __construct(array $attributes = []) { 
        parent::__construct();
        $this->rules = self::$rule;
    }

    protected static function booted()
    {
        static::deleting(function ($post) {
           DB::statement("update eprog_manager_ksef set accounting = null where accounting = '".$post->id."'");
        });

   
    }


    public function beforeValidate()
    {
        if (($this->selltype ?? null) != '1') {
            $this->rules['client_nip'] = 'required';
            $this->rules['client_adres1'] = 'required';
            $this->rules['client_country'] = 'required';
        } else {
            $this->rules['client_nip'] = '';
            $this->rules['client_adres1'] = '';
            $this->rules['client_country'] = '';
        }
    }


    public function beforeSave() {

		$this->admin_id = BackendAuth::getUser()->id;
        $data_document = [];
        $data_document['describe'] = Input::get("describe");
        $data_document['gtu'] = Input::get("gtu");
        $data_document['proc'] = Input::get("proc");
        $data_document['margin'] = str_replace(" ","",str_replace(",",".",Input::get("margin")));
        $data_document['correct'] = Input::get("correct");

        $data_document['lump'] = Input::get("lump");
        foreach(Input::get("kpir") as $k => $v){
            if($k != "kpir18a" && $k != "kpir19")
                $data_document['kpir'][$k]  = str_replace(" ","",str_replace(",",".",$v));
            else
                $data_document['kpir'][$k]  = $v;
        }
        $data_document['kpir']['ckpir'] = Input::get("ckpir");
        $data_document['vat_summary'] = [];
        foreach(Input::get("vat_summary") as $k => $v)
        $data_document['vat_summary'][$k]  = str_replace(" ","",str_replace(",",".",$v));
            
	    $this->data_document = json_encode($data_document);
        $this->netto = $data_document['vat_summary']["net_sum"];
        $this->vat = $data_document['vat_summary']["vat_sum"];
        $this->brutto = $data_document['vat_summary']["gross_sum"];
        $this->vat_register = Input::get("vat_register");
   
        if(Input::get("approve_document") == "on")
            $this->approve = 1;
        else
            $this->approve = null;


        $exists  = ModelAccounting::where("year",Input::get("Accounting.year"))->where("month",Input::get("Accounting.month"))->where("nip",Session::get("selected.nip"))->where("lp", Input::get("Accounting.lp"))->where("id","!=",$this->id)->count();
        if($exists > 0) throw new ValidationException(['my_field'=>trans("eprog.manager::lang.attribute_exists",["attribute" => "LP"])]);


    }

    public function beforeCreate()
    {
   
 
        $exists  = ModelAccounting::where("year",Input::get("Accounting.year"))->where("month",Input::get("Accounting.month"))->where("nip",Session::get("selected.nip"))->where("lp", Input::get("Accounting.lp"))->count();
        if($exists > 0) throw new ValidationException(['my_field'=>trans("eprog.manager::lang.attribute_exists",["attribute" => "LP"])]);

        $this->nip =  Session::get("selected.nip") ?? '';

    }

    public function afterCreate()
    {



	
    }

    
    public function getUserIdOptions()
    {

		$lista = [];
		$lista[0] = "-- ".strtolower(trans("eprog.manager::lang.select"))." --";
		$user = User::orderBy("surname")->get();
        	foreach($user as $user){
            		$lista[$user->id] = $user->surname." ".$user->firm_name." (".$user->firm_nip.")";
        	}

		if(Input::has("user_id")) {

			if(isset($lista[Input::get("user_id")]))
			return [Input::get("user_id") => $lista[Input::get("user_id")]];
			
		}


		if(Input::has("accounting")) {

			$accounting = Accounting::find(Input::get("accounting"));
			return [$accounting->user_id => $lista[$accounting->user_id]];
			
		}

        return  $lista;

    }


    public function  getAddBuyerRoleOptions()
    {


    	return Util::getRole();


    } 


    public function getBuyerTypeOptions()
    {

    	return Util::getNip();

    }

    public function getAddBuyerTypeOptions()
    {

    	return Util::getNip();

    }

    public function getYearOptions()
    {
        $year = date("Y", time());
        $years = [];
        for($i=0;$i<=10;$i++){
            if($year-$i < 2022 && !in_array($_SERVER["HTTP_HOST"],["erp.emode.pl","crm.emode.pl","demo.emode.pl","prod.emode.pl"])) continue;
            $years[$year-$i] = $year-$i;
        }
        return $years;

    }

    public function getMonthOptions()
    {

        return [1 => trans("eprog.manager::lang.months.1"), 2 => trans("eprog.manager::lang.months.2"), 3 => trans("eprog.manager::lang.months.3"), 4 => trans("eprog.manager::lang.months.4"), 5 => trans("eprog.manager::lang.months.5"), 6 => trans("eprog.manager::lang.months.6"), 7 => trans("eprog.manager::lang.months.7"), 8 => trans("eprog.manager::lang.months.8"), 9 => trans("eprog.manager::lang.months.9"), 10 => trans("eprog.manager::lang.months.10"), 11 => trans("eprog.manager::lang.months.11"), 12 => trans("eprog.manager::lang.months.12")];

    }

    public function getSelltypeOptions()
    {
        
        return ["-- ".strtolower(trans("eprog.manager::lang.select"))." --","RO","WEW","FP"];

    }

    public function getTypeOption()
    {
        
		return [0 => trans("eprog.manager::lang.ksef.base"), 1 => trans("eprog.manager::lang.ksef.correct"), 2 => trans("eprog.manager::lang.ksef.advance"), 3 => trans("eprog.manager::lang.ksef.settlement"), 4 => trans("eprog.manager::lang.ksef.simple"), 5 => trans("eprog.manager::lang.ksef.correct_advance"), 6 => trans("eprog.manager::lang.ksef.correct_settlement")];

    }

    public function getKorTypeOptions()
    {
        
        return [1 => trans("eprog.manager::lang.ksef.kor_type_1"), 2 => trans("eprog.manager::lang.ksef.kor_type_2"), 3 => trans("eprog.manager::lang.ksef.kor_type_3")];

    }


    public function getCurrencyOptions()
    {

    	return Util::getCurrencies();

    }

    public function getWuOptions()
    {

    	return Util::getCurrencies();

    }

    public function getBuyerCountryOptions()
    {

    	return Util::getCountries()[Session::get("locale")];

    }

    public function getAddBuyerCountryOptions()
    {

    	return Util::getCountries()[Session::get("locale")];

    }

    public function getClientCountryOptions()
    {

    	return Util::getCountries()[Session::get("locale")];

    }


    public function getPayInfoOptions()
    {

    	return [trans("eprog.manager::lang.ksef.paid_done"),trans("eprog.manager::lang.ksef.paid_part")];	

    }

    public function getPayTypeOptions()
    {

    	return [trans("eprog.manager::lang.ksef.pay_cache"),trans("eprog.manager::lang.ksef.pay_card"),trans("eprog.manager::lang.ksef.pay_bon"),trans("eprog.manager::lang.ksef.pay_check"),trans("eprog.manager::lang.ksef.pay_credit"),trans("eprog.manager::lang.ksef.pay_transfer"),trans("eprog.manager::lang.ksef.pay_mobile"),trans("eprog.manager::lang.ksef.pay_other")];

    }

	public function getMarzaOptions()
	{

		return  [trans("eprog.manager::lang.ksef.towary_uzywane"), trans("eprog.manager::lang.ksef.dziela_sztuki"), trans("eprog.manager::lang.ksef.biura_podrozy"), trans("eprog.manager::lang.ksef.kolekcjonerskie")];
	
	}

	public function getZwOptions()
	{

		return [trans("eprog.manager::lang.ksef.zw1"), trans("eprog.manager::lang.ksef.zw2"), trans("eprog.manager::lang.ksef.zw3")];

	}


    public function getModeOptions()
    {

        return [trans("eprog.manager::lang.sale"), trans("eprog.manager::lang.purchase")];    
	
    }

    public function getRegisterOptions()
    {

        return Vat::where("disp",1)->get()->pluck("name","id")->toArray();   
    
    }


    public function getTypeOptions()
    {

        return   self::getTypeOption();

    }

    public function getTaxFormOptions(){

        return Util::getTaxForm();

    }

    public function getStatusIdOptions()
    {
        $statues = AccountingController::status();
        array_unshift($statues , "-- ".strtolower(trans("eprog.manager::lang.select"))." --");
        return  $statues;

    }

    public function getStatusAttribute()
    {

        return  $this->status_id > 0 && isset(AccountingController::status()[$this->status_id])  ? AccountingController::status()[$this->status_id] : "";

    }



    public function updateValue()
    {

    
        $length = sizeof(Input::get('Values.edit_product'));
      
    	Accountingvalue::where("accounting_id","=",$this->id)->delete();
        for($i = 0; $i < $length - 1;$i++){
            $netto = null; $brutto = null; 
            if(Input::get("count") == "netto") $netto = preg_replace(["/,/i","/\s/i"], [".", ""],  Input::get('Values.edit_netto.'.$i));
            if(Input::get("count") == "brutto") $brutto = preg_replace(["/,/i","/\s/i"], [".", ""],  Input::get('Values.edit_brutto.'.$i));
            Accountingvalue::create(["accounting_id" => $this->id, "product" => Input::get('Values.edit_product.'.$i), "pkwiu" => Input::get('Values.edit_pkwiu.'.$i), "cn" => Input::get('Values.edit_cn.'.$i), "gtu" => Input::get('Values.edit_gtu.'.$i), "quantity" => Input::get('Values.edit_quantity.'.$i), "measure" => Input::get('Values.edit_measure.'.$i), "netto" => $netto, "vat" => Input::get('Values.edit_vat.'.$i), "brutto" => $brutto]);

        }    

    }

    public function onKsef_create(){

        //\Log::info("hh");

    }

    public function summaryPrice()
    {

        $sumnetto = 0;
        $sumbrutto = 0;
        $sumvat = 0;

        $length = sizeof(Input::get('Values.edit_product'));
        
        for($i = 0; $i < $length - 1;$i++){

            if(Input::get('count') == "netto")
            $netto = floatval(preg_replace(["/,/i","/\s/i"], [".", ""], Input::get('Values.edit_netto.'.$i)));
            else
            $brutto = floatval(preg_replace(["/,/i","/\s/i"], [".", ""], Input::get('Values.edit_brutto.'.$i)));    

            $vat_base = trim(Input::get('Values.edit_vat.'.$i));
            $quantity = trim(Input::get('Values.edit_quantity.'.$i));
            $extra = floatval(preg_replace(["/,/i","/\s/i"], [".", ""], Input::get('Values.edit_extra.'.$i)));

            $vat_stawka = $vat_base;$vat_stawka = is_numeric($vat_stawka) ? $vat_stawka : 0;

            if(Input::get('count') == "netto"){
                 $netto = round($netto*$quantity,2);
                 $brutto = round($netto*((100+$vat_stawka)/100),2);
            }
            else{
                 $brutto = round($brutto*$quantity,2);
                 $netto = round($brutto/((100+$vat_stawka)/100),2);
            }

    
            if($this->type == 2 || $this->type == 3 || $this->type == 5 || $this->type == 6){
                $brutto = round($extra,2);
                $netto = round($brutto/((100+$vat_stawka)/100),2);
 
            }

            $vat = round($brutto - $netto,2);

            if(Input::get('Values.edit_kor.'.$i) == "before"){
                $netto = -$netto;
                $vat = -$vat;
                $brutto = -$brutto;
            }

            $sumnetto += $netto;
            $sumbrutto += $brutto;
            $sumvat += $vat;

        }

        $this->brutto = $sumbrutto;
        $this->netto = $sumnetto;
        $this->vat = $sumvat;


    }

    public function scopeFilterByUser($query, $filter)
    {

        return $query->whereHas('user', function($group) use ($filter) {
            $group->whereIn('id', $filter);
        });

    }

    public function  ksefSend()
    {
/*
    
        $faktura = Ksef::xml(); 
        
        $result = Ksef::accountingSend($faktura);

        if(isset($result['elementReferenceNumber']))
            die($result['elementReferenceNumber']);             
        else
            throw new ValidationException(['my_field'=>'Blad wysylania faktury']);
*/
    }




}