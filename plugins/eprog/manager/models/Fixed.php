<?php namespace Eprog\Manager\Models;

use Model;
use Eprog\Manager\Models\Fixed as ModelFixed;
use October\Rain\Exception\ValidationException;
use Eprog\Manager\Classes\Util;
use Session;
use Input;

/**
 * Model
 */
class Fixed extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Validation
     */
    public $rules = [
        'lp'      => 'required',
        'name'    => 'required',
        'fixed'   => 'required',
        'ord'     => 'required|integer|min:0'
    ];

    public $customMessages = [
        'name.required' => 'eprog.manager::lang.valid_name'
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'eprog_manager_fixed';



    public function beforeSave() {

        $this->initial = (float)str_replace(" ","",str_replace(",",".",Input::get("Fixed.initial")));
        $this->initialupdate = (float)str_replace(" ","",str_replace(",",".",Input::get("Fixed.initialupdate")));


        $exists  = ModelFixed::where("nip",Session::get("selected.nip"))->where("lp", Input::get("Fixed.lp"))->where("id","!=",$this->id)->count();
        if($exists > 0) throw new ValidationException(['my_field'=>trans("eprog.manager::lang.attribute_exists",["attribute" => "LP"])]);

    }

    public function beforeCreate()
    {


        $exists  = ModelFixed::where("nip",Session::get("selected.nip"))->where("lp", Input::get("Fixed.lp"))->count();
        if($exists > 0) throw new ValidationException(['my_field'=>trans("eprog.manager::lang.attribute_exists",["attribute" => "LP"])]);

        $this->nip =  Session::get("selected.nip") ?? '';


    }

    
}