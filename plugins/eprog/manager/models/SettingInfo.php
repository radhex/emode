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


class SettingInfo extends Model
{


    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'invoice_settings';
    public $settingsFields = 'fields.yaml';


    public function initSettingsData()
    {



    }

    public function beforeSave()
    {

    
        if(Input::has("system") && Input::get("system")){

            $update = Artisan::call('system:update');
  

            if($update == "1")
                Flash::success(e(trans('eprog.manager::lang.update_system_success')));
            if($update == "2")
                throw new ValidationException(['my_field'=>e(trans('eprog.manager::lang.update_system_version'))]);
            if($update == "3")
                throw new ValidationException(['my_field'=>e(trans('eprog.manager::lang.update_system_php'))]);
            if($update == "4")
                throw new ValidationException(['my_field'=>e(trans('eprog.manager::lang.update_system_access'))]);
            if($update == "5")
                throw new ValidationException(['my_field'=>e(trans('eprog.manager::lang.update_system_zip'))]);
         


        }


    }

}