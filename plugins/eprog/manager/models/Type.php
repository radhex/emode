<?php namespace Eprog\Manager\Models;

use Model;
use Eprog\Manager\Models\Type as AllType;
use Eprog\Manager\Classes\Util;

/**
 * Model
 */
class Type extends Model
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

    /**
     * @var string The database table used by the model.
     */
    public $table = 'eprog_manager_type';


    public function getParentIdOptions()
    {

        $id = $this->id ? $this->id : 0;
        $lista = ["-- ".strtolower(trans("eprog.manager::lang.select"))." --"];

        $type = AllType::where("id","!=", $id)->orderBy("ord")->where("disp","=", 1)->get();

        foreach($type as $type){

           $lista[$type->id] = Util::categoryPath($type->id);
           
        }

        asort($lista);
        return  $lista;

    }

    public function scopeFilterByCategory($query, $filter)
    {
        return $query->whereHas('parent', function($group) use ($filter) {
            $group->whereIn('id', $filter);
        });

    }
    
}