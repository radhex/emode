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
use Session;
use Mail;
use Webklex\IMAP\Facades\Client;
use October\Rain\Exception\ValidationException;


/**
 * Model
 */
class Drivecreate extends Model
{

    use \October\Rain\Database\Traits\Validation;
       
    /*
     * Validation
     */    
    public $rules = [
        'files'    => 'required'
    ];

    
    /**
     * @var string The database table used by the model.
     */
    
    public $folder;

    public $perPage;

    public $table = 'eprog_manager_drive';

    public $attachMany = [
        
        'files' => ['System\Models\File', 'public' => true],
    
    ];

    public function fileUploadRules()
    {

        return ['files' => 'required|maxFiles:10|max:5000'];

    }



   
}