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
class Inbox extends ImapModel
{

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