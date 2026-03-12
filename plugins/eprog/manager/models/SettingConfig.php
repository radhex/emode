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
use Session;


class SettingConfig extends Model
{

    use \October\Rain\Database\Traits\Validation;

    public $implement = ['System.Behaviors.SettingsModel'];



    public $rules = [
        'surname'    => 'required',
        'name'    => 'required',
        'nip'    => 'nip',
        'code'    => 'code',
        'birthday'    => 'birthday',
        'email'    => 'required',
        'tax_form' => 'required',
    ];


    public $customMessages = [
        'nip.nip' => 'eprog.manager::lang.valid_nip',
        'code.code' => 'eprog.manager::lang.valid_code'
    ];

    public $settingsCode = 'config_settings';
    public $settingsFields = 'fields.yaml';

    public function initSettingsData()
    {

        $this->name = env('CONFIG_NAME', ''); 
        $this->surname = env('CONFIG_NAME', '');
        $this->birthday = env('CONFIG_BIRTHDAY', '');  
        $this->email = env('CONFIG_EMAIL', ''); 
        $this->phone = env('CONFIG_PHONE', ''); 
        $this->firm = env('CONFIG_FIRM', ''); 
        $this->nip = env('CONFIG_NIP', '');
        $this->region = env('CONFIG_REGION', ''); 
        $this->country = env('CONFIG_COUNTRY', ''); 
        $this->city = env('CONFIG_CITY', '');  
        $this->code = env('CONFIG_CODE', '');  
        $this->street = env('CONFIG_STREET', '');  
        $this->number = env('CONFIG_NUMBER', '');  
        $this->bank = env('CONFIG_BANK', '');  
        $this->currency = env('CONFIG_CURRENCY', '');  

    }


    public function afterSave()
    {

        \Cache::forget("backend::brand.custom_css");
        $theme = \Input::get("SettingConfig.theme") ?? 1;
        $file = "config/css/custom".$theme.".css";
        if(file_exists($file)){
            copy($file, "storage/cms/css/custom.css");
            preg_match('/:root\s*\{([^}]*)\}/', file_get_contents($file), $matches);
            file_put_contents("storage/cms/css/color.css",":root {".($matches[1] ?? '')."} ");
        }

    }

    public function getCountryOptions()
    {

        return Util::getCountries()[Session::get("locale")];

    }

    public static function getRegionOptions()
    {

	   return  Util::getRegions();

    }

    public static function getCurrencyOptions()
    {

       return  Util::getCurrencies();

    }

    public function getTaxTypeOptions()
    {

        return [trans('eprog.manager::lang.natural_person'),trans('eprog.manager::lang.firm')];

    }


    public function getTaxOfficeOptions(){

        return Util::getTaxOffice();

    }

    public function getTaxLumpOptions(){

        return Util::getTaxLump();

    }

    public function getTaxFormOptions(){

        return Util::getTaxForm();

    }

    public function getThemeOptions()
    {

        return ["1" =>"Color 1", "2" =>"Color 2", "3" =>"Blue", "4" =>"Cherry", "5" =>"Green"];
        
    }
}