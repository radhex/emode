<?php namespace Eprog\Manager\Models;

use Model;
use Eprog\Manager\Classes\Util;

/**
 * Model
 */
class Vat extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Validation
     */
    public $rules = [
        'name'    => 'required',
        'type'    => 'required',
        'mode'    => 'required',
    ];

    public $customMessages = [
        'name.required' => 'eprog.manager::lang.valid_name'
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'eprog_manager_vat';


    public function  getTypeOptions()
    {

        return Util::getVatType();
    }

    public function getModeOptions()
    {

        return [trans("eprog.manager::lang.sale"), trans("eprog.manager::lang.purchase")];    
    
    }
    
}