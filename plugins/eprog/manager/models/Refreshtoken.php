<?php namespace Eprog\Manager\Models;

use Model;
use RainLab\User\Models\User;
use RainLab\User\Models\UserGroup;
use RainLab\User\Models\UserGroups;
use Input;
use ApplicationException;
use Flash;
use Mail as SendMail;
use Carbon\Carbon;
use Redirect;
use Webklex\IMAP\Facades\Client;


/**
 * Model
 */
class Refreshtoken extends Model
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
    

    protected $fillable = ['user_id', 'token'];

    public $table = 'eprog_manager_refreshtoken';

 

}