<?php namespace Eprog\Manager\Models;

use Model;
use Eprog\Manager\Models\Order as ModelOrder;
use Eprog\Manager\Models\Ordervalue;
use Eprog\Manager\Models\Order;
use Eprog\Manager\Models\SettingStatus;
use Eprog\Manager\Classes\Util;
use Eprog\Manager\Controllers\Order as OrderController;
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
class Order extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Validation
     */

    public static $rule = [
    	'create_at'    	=> 'required|datetermin:1',
    	'buyer_name'    => 'required',
    	'buyer_nip'     => 'required_unless:buyer_type,3|nip:buyer_type,0|vateu:buyer_type,1|ksef:buyer_type,2',
        'buyer_adres1'    => 'required',
        'buyer_adres2'    => 'required',
    	'buyer_country' => 'required',
    	'buyer_email'   => 'email',
    	'_addbuyer_role_desc'    => 'required_if:_addbuyer_role,10',
    	'_addbuyer_name'    => 'required_unless:_addbuyer,0',
    	'_addbuyer_nip'     => 'required_unless:_addbuyer_type,3|nip:_addbuyer_type,0|vateu:_addbuyer_type,1|ksef:_addbuyer_type,2',
    	'_addbuyer_adres1'  => 'required_unless:_addbuyer,0',
        '_addbuyer_adres2'  => 'required_unless:_addbuyer,0',
    	'_addbuyer_country' => 'required_unless:_addbuyer,0',
    	'_addbuyer_email'   => 'email',
    	//'buyer_phone'   => 'phone',
    	'seller_name'    => 'required',
    	'seller_nip'     => 'required|nip',
    	'seller_adres1'  => 'required',
        'seller_adres2'  => 'required',
    	'seller_country' => 'required',
    	'seller_email'   => 'email',
    	//'seller_phone'   => 'phone',
    	'currency'  => 'required',
        '_pay_part' => 'required_if:_pay_info,1|ksef:_pay_info,1',
        '_pay_date' => 'required_with:_pay_info|datetermin:3',
        '_pay_termin' => 'datetermin:3',
        '_pay_other_desc' => 'required_if:_pay_type,7|max:256',
        '_ku' => 'required_with:_wu|numeric|gt:0',
        '_wu' => 'required_with:_ku',
        '_zw_desc' => 'required_with:_zw',
        '_skonto_cond' => 'required_with:_skonto',
        '_skonto' => 'required_with:_skonto_cond',
        '_umo_date' => 'datetermin:3',
        '_zam_date' => 'datetermin:3',
        '_regon' => 'regon',
        '_krs' => 'krs',
        '_bdo' => 'bdo',
        '_wdt' => 'max:256',
        '_stopka' => 'max:3500',
        '_add_desc' => 'max:256',
        '_bank_nr' => 'required_with:_swift|bank',
        '_swift' => 'swift',

    ];

    public $rules = [];

    public $customMessages = [

        'buyer_phone.phone' => 'eprog.manager::lang.valid_phone',
        '_addbuyer_role_desc.required_if' => 'eprog.manager::lang.ksef.role_desc_valid',
        '_pay_other_desc.required_if' => 'eprog.manager::lang.ksef.pay_other_desc_valid',
        '_pay_part.required_if' => 'eprog.manager::lang.ksef.pay_part_valid',
        '_addbuyer_nip.ksef' => 'eprog.manager::lang.valid_number1',
        'buyer_nip.ksef' => 'eprog.manager::lang.valid_number',

    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'eprog_manager_order';

    protected $fillable = ["user_id","admin_id","nr","type","place","seller_name","seller_nip","seller_country","seller_adres1","seller_adres2","seller_email","seller_phone","buyer_name","buyer_type","buyer_nip","buyer_country","buyer_adres1","buyer_adres2","buyer_email","buyer_phone","brutto","netto","vat","currency","exchange","desc","ord","disp","make_at","create_at","xml","upo","ksefNumber","referenceNumber","orderReferenceNumber","created_at","updated_at"];


    public $hasMany = [
            'value' => ['Eprog\Manager\Models\Ordervalue', 'delete' => true]
           
    ];

    public $belongsTo = [
            'user' => ['RainLab\User\Models\User'],
	    	'admin'=>  ['Backend\Models\User'],
    ];


    public function __construct(array $attributes = []) { 
        parent::__construct();
        $this->rules = self::$rule;
    }

    public function beforeSave() {

        //self::xml();
        //\Log::info($this->xml);
        //throw new ValidationException(['my_field'=>'Blad wysylania faktury']);

    	$length = sizeof(Input::get('Values.edit_product'));
    	if($length < 2) {
        
            throw new ValidationException(['my_field'=>trans("eprog.manager::lang.ksef.valid_product")]);
        }

        if(Input::get("action") == "ksefSend") self::ksefSend();

    	if(Input::segment(5) == "update"){

            self::summaryPrice();
            self::xml();
   			self::updateValue();


    	}


		$this->admin_id = BackendAuth::getUser()->id;
        $this->currency = Input::get("currency");
	
    }

    public function beforeCreate()
    {


        //dd("dd");
        self::summaryPrice();
        self::xml();



    }

    public function afterCreate()
    {

  
  		self::updateValue();


	
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


		if(Input::has("order")) {

			$order = Order::find(Input::get("order"));
			return [$order->user_id => $lista[$order->user_id]];
			
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

    public function getSellerCountryOptions()
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


    public function getTypeOptions()
    {

    

		$lista = self::getTypeOption();

		if(Input::segment(5) == "create") {

                $kor = 0;
                if(Input::has("correct")){
                    $order = ModelOrder::find(Input::get("correct"));
                    if($order && strlen($order->ksefNumber) > 0) $kor = 1;                
                }

                if($kor && isset($order->type) && ($order->type == 0 || $order->type == 4))
                    return [1 => $lista[1]];
                else if($kor && isset($order->type) && $order->type == 2)
                    return [5 => $lista[5]];
                else if($kor && isset($order->type) && $order->type == 3)
                    return [6 => $lista[6]];
                else if(Input::filled("advance"))
                    return [3 => $lista[3]];
                else
                    return [0 => $lista[0], 2 => $lista[2], 3 => $lista[3], 4 => $lista[4]];
       

		}
		else
		return [$this->type => $lista[$this->type]];
	
    }

    public function getStatusIdOptions()
    {
        $statues = OrderController::status();
        array_unshift($statues , "-- ".strtolower(trans("eprog.manager::lang.select"))." --");
        return  $statues;

    }

    public function getStatusAttribute()
    {

        return  $this->status_id > 0 && isset(OrderController::status()[$this->status_id])  ? OrderController::status()[$this->status_id] : "";

    }

    public function getModeOptions()
    {
        return [trans("eprog.manager::lang.original"),trans("eprog.manager::lang.copy"),trans("eprog.manager::lang.duplicate"),trans("eprog.manager::lang.none")];
    }


    public function updateValue()
    {


        $length = sizeof(Input::get('Values.edit_product'));
      
    	Ordervalue::where("order_id","=",$this->id)->delete();
        for($i = 0; $i < $length - 1;$i++){
            $netto = null; $brutto = null; $exchange = "1.0000"; 
            if(Input::get("count") == "netto") $netto = preg_replace(["/,/i","/\s/i"], [".", ""],  Input::get('Values.edit_netto.'.$i));
            if(Input::get("count") == "brutto") $brutto = preg_replace(["/,/i","/\s/i"], [".", ""],  Input::get('Values.edit_brutto.'.$i));
            if(Input::get("currency") != "PLN") $exchange = preg_replace(["/,/i","/\s/i"], [".", ""],  Input::get('Values.edit_exchange.'.$i));
            Ordervalue::create(["order_id" => $this->id, "product" => Input::get('Values.edit_product.'.$i), "pkwiu" => Input::get('Values.edit_pkwiu.'.$i), "cn" => Input::get('Values.edit_cn.'.$i), "gtu" => Input::get('Values.edit_gtu.'.$i), "quantity" => Input::get('Values.edit_quantity.'.$i), "measure" => Input::get('Values.edit_measure.'.$i), "netto" =>  $netto, "vat" => Input::get('Values.edit_vat.'.$i), "brutto" => $brutto, "exchange" => $exchange]);

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


    public function xml()
    {

        $xml = Ksef::xml("Order");
		$this->xml = $xml;

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
        
        $result = Ksef::orderSend($faktura);

        if(isset($result['elementReferenceNumber']))
            die($result['elementReferenceNumber']);             
        else
            throw new ValidationException(['my_field'=>'Blad wysylania faktury']);
*/
    }




}