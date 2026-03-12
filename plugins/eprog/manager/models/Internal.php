<?php namespace Eprog\Manager\Models;

use Model;
use Eprog\Manager\Models\Internal as ModelInternal;
use Eprog\Manager\Models\Internalvalue;
use Eprog\Manager\Models\Internal;
use Eprog\Manager\Models\SettingStatus;
use Eprog\Manager\Classes\Util;
use Eprog\Manager\Controllers\Internal as InternalController;
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
class Internal extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Validation
     */

    public static $rule = [
        'nr'     => 'required',
    	'create_at'   => 'required|datetermin:1',
        'record'     => 'numeric|gt:0',

    ];

    public $rules = [];



    /**
     * @var string The database table used by the model.
     */
    public $table = 'eprog_manager_internal';

    protected $fillable = ["user_id","admin_id","nr","place","created_at","updated_at"];


    public $hasMany = [
            'value' => ['Eprog\Manager\Models\Internalvalue', 'delete' => true]
           
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


    	$length = sizeof(Input::get('Values.edit_product'));
    	if($length < 2) {
        
            throw new ValidationException(['my_field'=>trans("eprog.manager::lang.ksef.valid_product")]);
        }

    	if(Input::segment(5) == "update"){

            self::summaryPrice();
   			self::updateValue();

    	}

		$this->admin_id = BackendAuth::getUser()->id;

        $exists  = ModelInternal::where("nip",Session::get("selected.nip"))->where("nr", Input::get("Internal.nr"))->where("id","!=",$this->id)->count();
        if($exists > 0) throw new ValidationException(['my_field'=>trans("eprog.manager::lang.attribute_exists",["attribute" => trans("eprog.manager::lang.nr")])]);

	
    }

    public function beforeCreate()
    {

      
        self::summaryPrice();

        $this->nip =  Session::get("selected.nip") ?? '';

        $exists  = ModelInternal::where("nip",Session::get("selected.nip"))->where("nr", Input::get("Internal.nr"))->count();
        if($exists > 0) throw new ValidationException(['my_field'=>trans("eprog.manager::lang.attribute_exists",["attribute" => trans("eprog.manager::lang.nr")])]);


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
            		$lista[$user->id] = $user->surname." ".$user->firm_name." (".$user->id.")";
        	}

		if(Input::has("user_id")) {

			if(isset($lista[Input::get("user_id")]))
			return [Input::get("user_id") => $lista[Input::get("user_id")]];
			
		}


		if(Input::has("internal")) {

			$internal = Internal::find(Input::get("internal"));
			return [$internal->user_id => $lista[$internal->user_id]];
			
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
                    $internal = ModelInternal::find(Input::get("correct"));
                    if($internal && strlen($internal->ksefNumber) > 0) $kor = 1;                
                }

                if($kor && isset($internal->type) && ($internal->type == 0 || $internal->type == 4))
                    return [1 => $lista[1]];
                else if($kor && isset($internal->type) && $internal->type == 2)
                    return [5 => $lista[5]];
                else if($kor && isset($internal->type) && $internal->type == 3)
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
        $statues = InternalController::status();
        array_unshift($statues , "-- ".strtolower(trans("eprog.manager::lang.select"))." --");
        return  $statues;

    }

    public function getStatusAttribute()
    {

        return  $this->status_id > 0 && isset(InternalController::status()[$this->status_id])  ? InternalController::status()[$this->status_id] : "";

    }

    public function getModeOptions()
    {
        return [trans("eprog.manager::lang.original"),trans("eprog.manager::lang.copy"),trans("eprog.manager::lang.duplicate"),trans("eprog.manager::lang.none")];
    }


    public function updateValue()
    {

        $length = sizeof(Input::get('Values.edit_product'));
      
    	Internalvalue::where("internal_id","=",$this->id)->delete();
        for($i = 0; $i < $length - 1;$i++){            
            $amount = preg_replace(["/,/i","/\s/i"], [".", ""],  Input::get('Values.edit_amount.'.$i));
            Internalvalue::create(["internal_id" => $this->id, "product" => Input::get('Values.edit_product.'.$i), "quantity" => Input::get('Values.edit_quantity.'.$i), "measure" => Input::get('Values.edit_measure.'.$i),  "amount" => $amount ]);

        }    

    }

    public function onKsef_create(){

        //\Log::info("hh");

    }

    public function summaryPrice()
    {

		$sumamount = 0;

        $length = sizeof(Input::get('Values.edit_product'));
        
        for($i = 0; $i < $length - 1;$i++){
        
            $amount = floatval(preg_replace(["/,/i","/\s/i"], [".", ""], Input::get('Values.edit_amount.'.$i)));
            $quantity = trim(Input::get('Values.edit_quantity.'.$i));
            $amount = round($amount*$quantity,2);
			$sumamount  += $amount;
	

		}

		$this->amount = $sumamount;

    }

    public function scopeFilterByUser($query, $filter)
    {

        return $query->whereHas('user', function($group) use ($filter) {
            $group->whereIn('id', $filter);
        });

    }




}