<?php namespace Eprog\Manager\Models;

use Lang;
use Model;
use System\Models\MailTemplate;
use RainLab\User\Models\User as UserModel;
use Flash;
use Input;
use Illuminate\Console\Command;
use Genius\StorageClear\Console\StorageDump;
use October\Rain\Exception\ValidationException;
use Artisan;
use Session;
use Eprog\Manager\Classes\Util;
use Eprog\Manager\Classes\Ksef;


class SettingJpk extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'ksef_settings';
    public $settingsFields = 'fields.yaml';

    public function initSettingsData()
    {


        $this->mode = 'test'; 

    }


    public $rules = [
            
    ];


    public $customMessages = [


    ];


    public function beforeSave()
    {



    }


}