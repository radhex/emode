<?php namespace Eprog\Manager\Models;

use Model;
use Eprog\Manager\Models\Category as AllCategory;
use Eprog\Manager\Classes\Util;

/**
 * Model
 */
class Category extends Model
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
            'parent' => ['Eprog\Manager\Models\Category']
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'eprog_manager_category';


    public function getParentIdOptions()
    {

        $id = $this->id ? $this->id : 0;
        $lista = ["-- ".strtolower(trans("eprog.manager::lang.select"))." --"];

        $category = AllCategory::where("id","!=", $id)->orderBy("ord")->where("disp","=", 1)->get();

    	foreach($category as $category){

    	   $lista[$category->id] = Util::categoryPath($category->id);
           
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