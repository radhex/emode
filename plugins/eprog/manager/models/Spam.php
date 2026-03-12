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
class Spam extends ImapModel
{

    public $table = 'eprog_manager_spam';

    public function getFolder()
    {
     
        $this->folder = config("imap.folders.spam.folder");
        return $this->folder;

    }
   
}