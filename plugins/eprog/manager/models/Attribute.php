<?php namespace Eprog\Manager\Models;

use Eprog\Manager\Models\Unit;
use Model;
use Input;

/**
 * Model
 */
class Attribute extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Validation
     */
    public $rules = [
        'name'    => 'required',
        'ord'    => 'required|integer|min:0'
    ];

    public $customMessages = [
        'name.required' => 'eprog.manager::lang.valid_name'
    ];

    public $belongsTo = [
            'unit' => ['Eprog\Manager\Models\Unit']

    ];


    /**
     * @var string The database table used by the model.
     */
    public $table = 'eprog_manager_attribute';


    public function getUnitIdOptions()
    {

        $lista = [];
        $lista[0] = "-- ".strtolower(trans("eprog.manager::lang.select"))." --";
        $unit = Unit::orderBy("name")->get();
            foreach($unit as $unit){
                    $lista[$unit->id] = $unit->short ? $unit->name." [".$unit->short."]" : $unit->name;
            }

        if(Input::has("unit_id")) {

            return [Input::get("unit_id") => $lista[Input::get("unit_id")]];
            
        }


        if(Input::has("order")) {

            $order = Order::find(Input::get("order"));
            return [$order->unit_id => $lista[$order->unit_id]];
            
        }

        return  $lista;

    }


}