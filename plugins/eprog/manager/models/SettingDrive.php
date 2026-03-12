<?php namespace Eprog\Manager\Models;

use Lang;
use Model;
use System\Models\MailTemplate;
use RainLab\User\Models\User as UserModel;
use Flash;
use Input;
use Illuminate\Console\Command;
use Genius\StorageClear\Console\StorageDump;
use Artisan;
use Redirect;
use File;
use Eprog\Manager\Classes\Util;
use ValidationException;


class SettingDrive extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'drive_settings';
    public $settingsFields = 'fields.yaml';



    
}