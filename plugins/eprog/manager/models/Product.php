<?php namespace Eprog\Manager\Models;

use Model;
use Input;
use Flash;
use BackendAuth;
use Eprog\Manager\Classes\Util;
use Eprog\Manager\Models\SettingConfig as Settings;

/**
 * Model
 */
class Product extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Validation
     */
    public $rules = [
        'name'    => 'required',
        'ord'    => 'required|integer|min:0',
        'quantity'    => 'required|integer|min:1',
        'brutto'    => 'required',
        'netto'    => 'required',
        'vat'    => 'required',
        'vat_procent'    => 'required'
    ];

    public $customMessages = [
        'name.required' => 'eprog.manager::lang.valid_name'
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'eprog_manager_product';

    protected $fillable = ['quantity'];

    public $belongsTo = [

            'category' => ['Eprog\Manager\Models\Category'],
            'admin' => ['Backend\Models\User'],
  	    'user' => ['Rainlab\User\Models\User'],
            'producent' => ['Eprog\Manager\Models\Producent'],
       
    ];

    public $attachMany = [
        'image' => ['System\Models\File'],
        'public_files' => ['System\Models\File', 'public' => false],
        'private_files' => ['System\Models\File', 'public' => false]
    ];


    public function fileUploadRules()
    {
        return ['image' => 'image|max:500','public_files' => 'required|maxFiles:20|max:5000', 'private_files' => 'required|maxFiles:20|max:5000'];
    }

    public function getBackimageAttribute()
    {

         return Files::getThumbUrl($this->image,200,200, null);
	  
    }

    public function getImageThumb($size = 25, $options = null)
    {
        
        if (is_string($options)) {
            $options = ['default' => $options];
        }
        elseif (!is_array($options)) {
            $options = [];
        }

        // Default is "mm" (Mystery man)
        $default = array_get($options, 'default', 'mm');


        if(isset($this->image[0])) {
            return $this->image[0]->getThumb($size, $size, $options);
        }
        else {
            return '//www.gravatar.com/avatar/'.
            md5(strtolower(trim($this->email))).
            '?s='.$size.
            '&d='.urlencode($default);
        }
    }

    public function beforeSave() {

        if(Input::get("add_value_flag")) {

            $attr = json_decode($this->attribute, true) ?? [];
            $attr[Input::get("new_attr")] = Input::get("new_value");
            $this->attribute = json_encode($attr);
            
        } 

        if(Input::get("edit_value_flag")) {

            $attr = json_decode($this->attribute, true) ?? [];
            $attr[Input::get("atrr")] = Input::get("value_".Input::get("atrr"));
            $this->attribute = json_encode($attr);    
            
        }

        if(Input::get("delete_value_flag")) {

            $attr = json_decode($this->attribute, true) ?? [];
            unset($attr[Input::get("atrr")]);
            $this->attribute = json_encode($attr);    
            
        }  

        $this->brutto = str_replace(" ","",str_replace(",",".",$this->brutto)); 
        $this->netto = str_replace(" ","",str_replace(",",".",$this->netto));  
        $this->vat = str_replace(" ","",str_replace(",",".",$this->vat));     

    }

    public function afterDelete()
    {   

        foreach ($this->image as $file) {
            $file && $file->delete();
        }

        foreach ($this->public_files as $file) {
            $file && $file->delete();
        }

        foreach ($this->private_files as $file) {
            $file && $file->delete();
        }
      
    }

    public static function getUserIdOptions()
    {

        $lista = ['0'=> '-- '.strtolower(trans("eprog.manager::lang.select")).' --'];

        	$user =  \Rainlab\User\Models\User::all();
        	foreach($user as $user){

            		$lista[$user->id] = $user->surname;
        	}

 

        return  $lista;

    }

    public static function getAdminIdOptions()
    {

        $lista = [];

        $admin =  \Backend\Models\User::where("is_superuser","=","0")->get();
        foreach($admin as $admin){

        $lista[$admin->id] = $admin->login;

        }

        if(!BackendAuth::getUser()->hasAccess("eprog.manager.manage_scheduler")){
        $lista = [];	
        $lista[BackendAuth::getUser()->id] = BackendAuth::getUser()->login;
        }

        return  $lista;

    }

    public static function getCategoryIdOptions()
    {

        $lista = ['0'=> '-- '.strtolower(trans("eprog.manager::lang.select")).' --'];

        	$category =  Category::orderBy("ord")->where("disp","=", 1)->get();
        	foreach($category  as $category ){

            		$lista[$category->id] = Util::categoryPath($category->id);
        	}

        asort($lista);
        return  $lista;

    }

    public static function getProducentIdOptions()
    {

        $lista = ['0'=> '-- '.strtolower(trans("eprog.manager::lang.select")).' --'];

        	$producent =  Producent::orderBy("name")->orderBy("ord")->where("disp","=", 1)->get();
        	foreach($producent  as $producent ){

            		$lista[$producent->id] = $producent->name;
        	}

        
        return  $lista;

    }

    public static function getVatProcentOptions()
    {

        return     Util::getVat();

    }



    public static function getCurrencyOptions()
    {

        return Util::getCurrencies();

    }


    public function scopeFilterByCategory($query, $filter)
    {

        return $query->whereHas('category', function($group) use ($filter) {
            $group->whereIn('id', $filter);
        });
	
    }

    public function scopeFilterByProducent($query, $filter)
    {

        return $query->whereHas('producent', function($group) use ($filter) {
            $group->whereIn('id', $filter);
        });
	
    }

    public function filterFields($field, $context = null)
    {
/*
        $field->brutto->label = trans("eprog.manager::lang.gross_value")." ".Settings::get("currency");
        $field->netto->label = trans("eprog.manager::lang.net_value")." ".Settings::get("currency");
        $field->vat->label = trans("eprog.manager::lang.vat_value")." ".Settings::get("currency");
        $field->special_brutto->label = trans("eprog.manager::lang.gross_special")." ".Settings::get("currency");
        $field->special_netto->label = trans("eprog.manager::lang.net_special")." ".Settings::get("currency");
        $field->special_vat->label = trans("eprog.manager::lang.vat_special")." ".Settings::get("currency");
*/
    }
}