<?php namespace Eprog\Manager\Models;

use Model;
use Eprog\Manager\Models\Project;
use October\Rain\Exception\ValidationException;
use Input;
use Flash;
use Eprog\Manager\Controllers\PublicFiles;
use Eprog\Manager\Classes\Util;
use BackendAuth;

/**
 * Model
 */
class Task extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Validation
     */    
    public $rules = [
      
    ];
    

    /**
     * @var string The database table used by the model.
     */
    
    protected $fillable = ['work_id','user_id', 'name','desc','start'];

    public $table = 'eprog_manager_task';
  
    public $belongsTo = [
        
            'work' => ['Eprog\Manager\Models\Work'],
            'user' => ['\Backend\Models\User']
    ];
            
}