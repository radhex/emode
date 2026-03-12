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
class Inboxcreate extends Model
{

    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Validation
     */    
    public $rules = [
        'to'    => 'required',
        'title'    => 'required',
        'body'    => 'required'
    ];

    public $customMessages = [

        'to.required' => 'eprog.manager::lang.valid_to',
        'title.required' => 'eprog.manager::lang.valid_title',
        'body.required' => 'eprog.manager::lang.valid_body'
      
    ];
    
    
    /**
     * @var string The database table used by the model.
     */
    
    public $folder;

    public $perPage;

    public $table = 'eprog_manager_inbox';

    public $attachMany = [
        
        'files' => ['System\Models\File', 'public' => true],
    
    ];

    public function getFolder()
    {
     
        $this->folder = config("imap.folders.inbox.folder");
        return $this->folder;

    }

    public function fileUploadRules()
    {

        return ['files' => 'required|maxFiles:10|max:5000'];

    }



   
}