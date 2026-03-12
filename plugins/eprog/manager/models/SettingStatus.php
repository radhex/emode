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


class SettingStatus extends Model
{

    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'status_settings';
    public $settingsFields = 'fields.yaml';

    public function initSettingsData()
    {

        $this->invoice_pl = '1;W przygotowaniu'.PHP_EOL.'2;Wysłana'.PHP_EOL.'3;Anulowana';
        $this->invoice_en = '1;In preparation'.PHP_EOL.'2;Sent'.PHP_EOL.'3;Canceled';
        $this->order_pl = '1;Oczekuje'.PHP_EOL.'2;W trakcie realizacji'.PHP_EOL.'3;Zrealizowane'.PHP_EOL.'4;Niezrealizowane';
        $this->order_en = '1;Awaits'.PHP_EOL.'2;In progress'.PHP_EOL.'3;Completed'.PHP_EOL.'4;Unrealized';
        $this->project_pl = '1;Oczekuje'.PHP_EOL.'2;W trakcie realizacji'.PHP_EOL.'3;Zrealizowany'.PHP_EOL.'4;Niezrealizowany';
        $this->project_en = '1;Awaits'.PHP_EOL.'2;In progress'.PHP_EOL.'3;Completed'.PHP_EOL.'4;Unrealized';
        $this->work_pl = '1;Oczekuje'.PHP_EOL.'2;W trakcie realizacji'.PHP_EOL.'3;Zrealizowana'.PHP_EOL.'4;Niezrealizowana';
        $this->work_en = '1;Awaits'.PHP_EOL.'2;In progress'.PHP_EOL.'3;Completed'.PHP_EOL.'4;Unrealized';
      
    }

    public function beforeSave()
    {


    }

    public static function getRegionOptions()    {

	   return  Util::getRegions();

    }
}