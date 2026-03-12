<?php namespace Eprog\Manager\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Input;
use Redirect;
use Flash;
use October\Rain\Exception\ValidationException;
use Eprog\Manager\Models\Mailing as ModelMailing;
use RainLab\User\Models\User;
use RainLab\User\Models\UserGroup;
use RainLab\User\Models\UserGroups;
use Mail;
use Carbon\carbon;
use Artisan;
use Webklex\IMAP\Facades\Client;
use Lang;

class Draft extends ImapController
{

    public function __construct()
    {

        parent::__construct();
        BackendMenu::setContext('Eprog.Manager', 'inbox',  'draft');
        $this->folder = config("imap.folders.draft.folder");

    }

}