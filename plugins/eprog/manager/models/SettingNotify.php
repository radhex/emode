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
use Eprog\Manager\Classes\Util;


class SettingNotify extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'notify_settings';
    public $settingsFields = 'fields.yaml';

    public function initSettingsData()
    {

        $this->mail_notify = true;
        $this->status_notify = true;
        $this->event_notify = true;
        $this->invoice_notify = false;

      
    }

    public function beforeSave()
    {

    }

    public static function getRegionOptions()    {

	   return  Util::getRegions();

    }
}