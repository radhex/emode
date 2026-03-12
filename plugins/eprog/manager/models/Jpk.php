<?php namespace Eprog\Manager\Models;

use Model;
use Input;
use Eprog\Manager\Classes\Util;
use Eprog\Manager\Classes\Jpk as ClassJpk;
use Session;

/**
 * Model
 */
class Jpk extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Validation
     */
    public $rules = [
        'name'    => 'required',
        'version'    => 'required',
        'period'    => 'required',
        'xml'    => 'required',

    ];

    public $customMessages = [
        'name.required' => 'eprog.manager::lang.valid_name'
    ];


    /**
     * @var string The database table used by the model.
     */

    protected $fillable = [

        'nip',
        'name',
        'xml',
        'other',
        'version',
        'period',
        'created_at',
        'updated_at'

    ];


    public $table = 'eprog_manager_jpk';

    public function beforeSave() {

        
        $this->xml =  ClassJpk::verifyJpkXml(Util::escapeXml($this->xml), $this->version);  
        $this->other = json_encode(Input::get("other"));
    
    }

    public function beforeCreate()
    {
    
    
        $this->nip =  Session::get("selected.nip") ?? '';

    }

    
}