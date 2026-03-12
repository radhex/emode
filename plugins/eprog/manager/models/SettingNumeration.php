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
use Eprog\Manager\Classes\Util;


class SettingNumeration extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'numeration_settings';
    public $settingsFields = 'fields.yaml';

    public function initSettingsData()
    {

        $this->invoice_block_number = false;
        $this->invoice_type = 'month'; 
        $this->invoice_separator = '/'; 

        $this->order_block_number = false;
        $this->order_type = 'month'; 
        $this->order_separator = '/'; 

        $this->internal_block_number = false;
        $this->internal_type = 'month'; 
        $this->internal_separator = '/'; 

        $this->payroll_block_number = false;
        $this->payroll_type = 'month'; 
        $this->payroll_separator = '/'; 
    }


    public static function getRegionOptions()    {

	   return  Util::getRegions();

    }

    public $rules = [
        'invoice_separator' => 'required',
        'order_separator' => 'required',
        'internal_separator' => 'required',
        'payroll_separator' => 'required',
    ];




}