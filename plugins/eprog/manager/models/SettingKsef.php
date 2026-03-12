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


class SettingKsef extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'ksef_settings';
    public $settingsFields = 'fields.yaml';

    public function initSettingsData()
    {

        $this->type = 'cert'; 
        $this->mode = 'test'; 

    }


    public $attachOne = [
    
        'cert_auth_p12' => ['System\Models\File', 'public' => false],
        'cert_link_p12' => ['System\Models\File', 'public' => false],
        'cert_crt' => ['System\Models\File', 'public' => false],
        'cert_prv' => ['System\Models\File', 'public' => false]
   
    ];


    public $rules = [
        'nip'    => 'required:nip',
        //'token' => 'required_if:type,token',
        //'cert_auth_p12' => 'required_if:type,cert',
    ];


    public $customMessages = [

         'token' => 'eprog.manager::lang.ksef.valid_token',
         'cert_auth_p12' => 'eprog.manager::lang.ksef.valid_cert_auth',
         'pass' => 'eprog.manager::lang.ksef.valid_pass'

    ];


    public function beforeSave()
    {

        if(Input::has("test") && Input::get("test"))
        {
       
            Session::forget("ksef");
            $client = Ksef::buildClient();
            if($client)
            Flash::success(e(trans('eprog.manager::lang.ksef.test_success')));

        }

    }



}