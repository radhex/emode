<?php namespace Eprog\Manager;

use System\Classes\PluginBase;
use Event;
use Backend;
use System\Classes\SettingsManager;
use BackendAuth;
use Backend\Controllers\Auth;
use Input;
use Session;
use Eprog\Manager\Models\SettingConfig;
use Eprog\Manager\Models\Mail;
use Eprog\Manager\Models\Inmail;
use Eprog\Manager\Classes\Util;
use Eprog\Manager\Console\EventNotify;
use Eprog\Manager\Console\InvoiceNotify;
use Eprog\Manager\Console\KsefSynchro;
use Eprog\Manager\Console\SystemUpdate;
use Eprog\Manager\Models\SettingSerwer;
use Webklex\IMAP\Facades\Client;
use Eprog\Manager\Validate\NipValidate;
use Eprog\Manager\Validate\CodeValidate;
use Eprog\Manager\Validate\PhoneValidate;
use Eprog\Manager\Validate\BirthdayValidate;
use Eprog\Manager\Validate\BankValidate;
use Eprog\Manager\Validate\SwiftValidate;
use Eprog\Manager\Validate\RegonValidate;
use Eprog\Manager\Validate\KrsValidate;
use Eprog\Manager\Validate\BdoValidate;
use Eprog\Manager\Validate\DateterminValidate;
use Eprog\Manager\Validate\VateuValidate;
use Eprog\Manager\Validate\CurrencyValidate;
use Eprog\Manager\Validate\KsefValidate;
use Eprog\Manager\Validate\PaylinkValidate;
use Eprog\Manager\Validate\IpksefValidate;
use October\Rain\Exception\ValidationException;
use Validator;
use Backend\Controllers\UserRoles;
use Backend\Models\UserRole;
use Backend\Models\User;
use Backend\Controllers\Users;
use Backend\Models\User as BackendUser;
use Redirect;
use Eprog\Manager\Classes\Ksef;
use Flash;
use Winter\Storm\Auth\Manager as BaseManager;
use Eprog\Manager\Overrides\Classes\PatchedAuthManager;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Backend\Classes\Controller;
use Eprog\Manager\Classes\BackendPageMiddleware;
use Monolog\Handler\NullHandler;
use Backend\Facades\BackendMenu;
use Illuminate\Support\Facades\Route;
use Log;


class Plugin extends PluginBase
{
    
    public $elevated = true;

    public function boot()
    {      

        Event::listen('backend.menu.extendItems', function ($manager) {


            $user = BackendAuth::getUser();

            $blocked = [
                'project'   => \Eprog\Manager\Controllers\Project::class,
                'work'      => \Eprog\Manager\Controllers\Work::class,                
                'product'   => \Eprog\Manager\Controllers\Product::class,
                'category'  => \Eprog\Manager\Controllers\Category::class,
                'attribute' => \Eprog\Manager\Controllers\Attribute::class,
                'unit'      => \Eprog\Manager\Controllers\Unit::class,
                'producent' => \Eprog\Manager\Controllers\Producent::class,                                
                'order'     => \Eprog\Manager\Controllers\Order::class,
                'invoice'   => \Eprog\Manager\Controllers\Invoice::class,
                'ksef'   => \Eprog\Manager\Controllers\Invoice::class,
                'accounting'   => \Eprog\Manager\Controllers\Accounting::class,
                'jpk'   => \Eprog\Manager\Controllers\Jpk::class,
                'internal'   => \Eprog\Manager\Controllers\Internal::class,
                'fixed'   => \Eprog\Manager\Controllers\Fixed::class,
                'worker'   => \Eprog\Manager\Controllers\Worker::class,
                'payroll'   => \Eprog\Manager\Controllers\Payroll::class,
                'zus'   => \Eprog\Manager\Controllers\Zus::class,
                'advance'   => \Eprog\Manager\Controllers\Advance::class,
                'taxfile'   => \Eprog\Manager\Controllers\Taxfile::class,
            ];

            if($_SERVER['SERVER_NAME'] != "crm.emode.pl") $blocked['admin'] = \Eprog\Manager\Controllers\Admin::class;
            if(in_array($_SERVER['SERVER_NAME'],["crm.emode.pl","demo.emode.pl","prod.emode.pl"])){
                //$blocked['invoicepay'] = \Eprog\Manager\Controllers\Invoicepay::class;
                //$blocked['orderpay'] = \Eprog\Manager\Controllers\Orderpay::class;
            }

            //if (in_array("", [$m1, $m2, $m3, $m4, $m5])) $blocked = [];
            if(Util::mode("2")) unset($blocked['ksef'], $blocked['invoice']);
            if(Util::mode("3")) unset($blocked['accounting'], $blocked['jpk'], $blocked['internal'], $blocked['fixed'], $blocked['jpk'], $blocked['worker'], $blocked['payroll'], $blocked['zus'], $blocked['advance'], $blocked['taxfile']);
            if(Util::mode("4")) unset($blocked['order'], $blocked['product'], $blocked['category'], $blocked['attribute'], $blocked['unit'], $blocked['producent']);
            if(Util::mode("5")) unset($blocked['project'], $blocked['work']);

            foreach ($blocked as $code => $controllerClass) {
                BackendMenu::removeMainMenuItem('Eprog.Manager', $code);
            }

            $blockedPaths = [];
            foreach ($blocked as $code => $controllerClass) {
                $blockedPaths[] = 'backend/eprog/manager/' . strtolower($code);
            }

            $currentPath = trim(request()->path(), '/');

            foreach ($blockedPaths as $path) {
                if ($currentPath == $path) {
                    abort(403, e(trans('update_system_access')));
                }
            }

        });

       // $currentUrl = trim(request()->path(), '/'); // np. backend/eprog/manager/calendar

        if(!Session::has("selected.nip"))
        Session::put("selected.nip", SettingConfig::get("nip"));    


        app('router')->pushMiddlewareToGroup(
            'web',
            BackendPageMiddleware::class
        );

        \Event::listen('backend.user.login', function ($user) {

            system("rm -rf storage/temp/public/*");
            Util::checkBearer();

        });

        
        /*
        \App::error(function(\Exception $exception) {
           // throw new ValidationException(['my_field'=>$exception->getMessage()]);
            return \Response::json([
                'X_OCTOBER_ERROR' => true,
                'X_WINTER_ERROR'  => true,
                'error'           => $exception->getMessage(),
            ], 200);

        });


        \App::error(function (\Throwable $ex) {


            return \Response::json([
                'X_OCTOBER_ERROR' => true,
                'X_WINTER_ERROR'  => true,
                'error'           => $ex->getMessage(),
            ], 200);


        });

        */
        
/*
        \Event::listen('backend.menu.extendItems', function ($manager) {
                    if(!isset($_SESSION))
                    session_start();
                    $item = $manager->getMainMenuItem("Eprog.Manager","Drive");
                    if(!isset($_SESSION['upload_token']) && isset($_SESSION['authUrl']))
                    $item->url = $_SESSION['authUrl'];
          });
*/     

          \System\Controllers\Settings::extend(function($controller) {
              $controller->addDynamicMethod('onValidationException', function() {
                  return 'handler called!';
              });
          });


        Auth::extend(function ($controller) {
            $controller->bindEvent('page.beforeDisplay', function ($action, $params) use ($controller) {
              if ($action === 'signout' || $action === 'signin') { 
                  session_start();
                  session_destroy();
              }
            });
        });    

        \Eprog\Manager\Controllers\Drive::extend(function (\Eprog\Manager\Controllers\Drive $drive) {
            $drive->listConfig = $drive->mergeConfig($drive->listConfig, [
                'defaultSort' => [
                    'column' => 'id',
                    'direction' => 'asc',
                ],
            ]);
        });

        \Eprog\Manager\Controllers\DriveTrash::extend(function (\Eprog\Manager\Controllers\DriveTrash $drive) {
            $drive->listConfig = $drive->mergeConfig($drive->listConfig, [
                'defaultSort' => [
                    'column' => 'id',
                    'direction' => 'asc',
                ],
            ]);
        });

        \Eprog\Manager\Controllers\DriveShared::extend(function (\Eprog\Manager\Controllers\DriveShared $drive) {
            $drive->listConfig = $drive->mergeConfig($drive->listConfig, [
                'defaultSort' => [
                    'column' => 'id',
                    'direction' => 'asc',
                ],
            ]);
        });



        \Event::listen('backend.list.extendQuery', function ($listWidget, $query) {


            //if(get_class($listWidget->getController()) == 'System\Controllers\MailTemplates')
            //$query->where('id','>', 2);
          
        });


        Event::listen('backend.page.beforeDisplay', function($controller, $action, $params) {

            if(BackendAuth::getUser()){
         
                $user = Mail::where("send","=",0)->where("read","=",0);
                if(!BackendAuth::getUser()->hasAccess("eprog.manager.manage_mail")) 
                $user = $user->where("admin_id","=",BackendAuth::getUser()->id);
                $user = $user->count();
                $admin = Inmail::where("receiver_id","=",BackendAuth::getUser()->id)->where("read","=",0)->count();
                $all = $user + $admin;  
                
                $sent = 0;$draft = 0;$archive = 0;$spam = 0;$trash = 0;$envelope = 0;$inbox = 0;
                if(config('imap.enable')) {
                    
                    $client = Client::account('default');
                    $client->connect();
                    $inbox = $client->getFolder(config("imap.folders.inbox.folder"))->query()->unseen()->get()->count() ?? 0;   
         
    /*
                    if(in_array(Input::segment(4),["inbox","sent","draft","archive","spam","trash"])) {

                        $sent = $client->getFolder(config("imap.folders.sent.folder"))->query()->unseen()->get()->count() ?? 0;
                        $draft = $client->getFolder(config("imap.folders.draft.folder"))->query()->unseen()->get()->count() ?? 0;
                        $archive = $client->getFolder(config("imap.folders.archive.folder"))->query()->unseen()->get()->count() ?? 0;
                        $spam = $client->getFolder(config("imap.folders.spam.folder"))->query()->unseen()->get()->count() ?? 0;
                        $trash = $client->getFolder(config("imap.folders.trash.folder"))->query()->unseen()->get()->count() ?? 0;

                    }
    */
                    $envelope = $inbox;// + $sent + $draft + $archive + $spam + $trash;

        
                }
    

                $controller->addJs("/plugins/eprog/manager/assets/js/load.js", ['id' => 'eprog-load' ,'all'=>$all, 'user'=>$user, 'admin' => $admin, 'envelope' => $envelope, 'inbox' => $inbox, 'sent' => $sent, 'draft' => $draft, 'archive' => $archive, 'spam' => $spam, 'trash' => $trash, 'backend' => config('cms.backendUri')]);

                if(!BackendAuth::getUser()->is_superuser && (Input::segment(3) == "maillayouts" || Input::segment(3) == "mailpartials" || (Input::segment(3) == "mailtemplates" && Input::segment(4) == "create")))
                return Backend::redirect('system/mailtemplates');

                if (BackendAuth::getUser()->login === 'ksef' || BackendAuth::getUser()->login === 'demo') {
                    if (\Request::is(config('cms.backendUri').'/backend/users/myaccount')) {
                        return Redirect::to(config('cms.backendUri'));
                    }
                }

                if(BackendAuth::getUser()->login == "ksef" && $controller instanceof \Backend\Controllers\Index)
                    return Backend::redirect('eprog/manager/free/create');

            }



            if ($action == 'index' && $controller instanceof \Backend\Controllers\Index)
            return Backend::redirect('eprog/manager/calendar');

            //$controller->addJs("/plugins/eprog/manager/assets/js/jquery-ui.js"); //przesuniete do backend layout _head
            $controller->addCss("/plugins/eprog/manager/assets/css/jquery-ui.css");



        });


        \Backend\Models\User::extend(function($model) 
        {
            $model->hasMany['inmail'] = ['Eprog\Manager\Models\Inmail', 'delete' => 'true'];

            $model->addDynamicMethod('scopeAdmin', function($query){

                return $query->where('is_superuser','=', 0);
            });



        });

        \Backend\Widgets\Lists::extend(function ($widget) {
                $widget->addViewPath(plugins_path().'/eprog/manager/views/widgets/lists/');
            
        });

        Validator::extend('nip', NipValidate::class);
        Validator::extend('vateu', VateuValidate::class);
        Validator::extend('code', CodeValidate::class);
        Validator::extend('phone', PhoneValidate::class);
        Validator::extend('bank', BankValidate::class);
        Validator::extend('birthday', BirthdayValidate::class);
        Validator::extend('swift', SwiftValidate::class);
        Validator::extend('regon', RegonValidate::class);
        Validator::extend('krs', KrsValidate::class);
        Validator::extend('bdo', BdoValidate::class);
        Validator::extend('datetermin', DateterminValidate::class);
        Validator::extend('currency', CurrencyValidate::class);
        Validator::extend('ksef', KsefValidate::class);
        Validator::extend('paylink', PaylinkValidate::class);
        Validator::extend('ipksef', IpksefValidate::class);

        \System\Controllers\Settings::extend(function($controller) {
              $controller->addDynamicMethod('onGenerate', function() {
                  Ksef::convertp12();
                  return Redirect::to('/'.config('cms.backendUri').'/eprog/manager/feed/getCertyficate');
              });
        });

        \Backend\Controllers\Users::extend(function ($controller) {
            $controller->addDynamicMethod('formExtendModel', function ($model) {
                $model->rules['password'] = 'required|min:12|regex:/[A-Z]/|regex:/[0-9]/|confirmed';
                $model->rules['password_confirmation'] = 'required_with:password|min:12';
                $model->customMessages['password.regex'] = e(trans('eprog.manager::lang.valid_password_user'));
                  
            });
        });

        \Event::listen('backend.page.beforeDisplay', function ($controller, $action, $params) {
            if (isset(BackendAuth::getUser()->role->code) && BackendAuth::getUser()->role->code == "ksefree" && \Request::path() === 'dashboard/backend/users/myaccount') {
                return redirect('/dashboard/eprog/manager/free/create'); // przekierowanie zamiast dostępu
            }
        });

        \Backend\Classes\Controller::extend(function ($controller) {
            if($controller->name == "free")
            $controller->addCss("/plugins/eprog/manager/assets/css/custom.css");
        });


        Route::aliasMiddleware('auth:sanctum', \Laravel\Sanctum\Http\Middleware\Authenticate::class);
        $this->registerRoutes();
    }


    protected function filterRolesField1($form)
    {
        $user = BackendAuth::getUser();

        if (!$user->is_superuser) {
            if (isset($form->fields['roles'])) {
                $allowedRoles = UserRole::where('code', '!=', 'ksefree')
                                        ->pluck('name', 'id')
                                        ->toArray();
                $form->fields['roles']['options'] = $allowedRoles;
            }
        }
    }

    public function registerComponents()
    {
        return [
            'Eprog\Manager\Components\Project'  => 'project',
            'Eprog\Manager\Components\UserMail'  => 'usermail',
            'Eprog\Manager\Components\Scheduler'  => 'scheduler',
            'Eprog\Manager\Components\Invoice'  => 'invoice',
            'Eprog\Manager\Components\Order'  => 'order'
        ];
    }
    public function registerSettings()
    {

        return [
            'config' => [
                'label'       => 'eprog.manager::lang.setting_config',
                'description' => 'eprog.manager::lang.setting_config_desc',
                'category'    => SettingsManager::CATEGORY_SYSTEM,
                'icon'        => 'icon-cog',
                'class'       => 'Eprog\Manager\Models\SettingConfig',
                'order'       => 500,
                'permissions' => ['eprog.manager.access_settings'],
                'keywords'    => 'config system'
            ],
            'status' => [
                'label'       => 'eprog.manager::lang.setting_status',
                'description' => 'eprog.manager::lang.setting_status_desc',
                'category'    => SettingsManager::CATEGORY_SYSTEM,
                'icon'        => 'icon-asterisk',
                'class'       => 'Eprog\Manager\Models\SettingStatus',
                'order'       => 502,
                'permissions' => ['eprog.manager.access_settings'],
                'keywords'    => 'config system'
            ],
            'ksef' => [
                'label'       => 'eprog.manager::lang.setting_ksef',
                'description' => 'eprog.manager::lang.setting_ksef_desc',
                'category'    => SettingsManager::CATEGORY_SYSTEM,
                'icon'        => 'icon-cloud-download',
                'class'       => 'Eprog\Manager\Models\SettingKsef',
                'order'       => 503,
                'permissions' => ['eprog.manager.access_settings'],
                'keywords'    => 'config ksef'
            ],
            'jpk' => [
                'label'       => 'eprog.manager::lang.setting_jpk',
                'description' => 'eprog.manager::lang.setting_jpk_desc',
                'category'    => SettingsManager::CATEGORY_SYSTEM,
                'icon'        => 'icon-file-code-o',
                'class'       => 'Eprog\Manager\Models\SettingJpk',
                'order'       => 503,
                'permissions' => ['eprog.manager.access_settings'],
                'keywords'    => 'config jpk'
            ],
            'numeration' => [
                'label'       => 'eprog.manager::lang.setting_numeration',
                'description' => 'eprog.manager::lang.setting_numeration_desc',
                'category'    => SettingsManager::CATEGORY_SYSTEM,
                'icon'        => 'icon-sort-numeric-asc',
                'class'       => 'Eprog\Manager\Models\SettingNumeration',
                'order'       => 505,
                'permissions' => ['eprog.manager.access_settings'],
                'keywords'    => 'config numeration'
            ],
            'notify' => [
                'label'       => 'eprog.manager::lang.setting_notify',
                'description' => 'eprog.manager::lang.setting_notify_desc',
                'category'    => SettingsManager::CATEGORY_SYSTEM,
                'icon'        => 'icon-flag',
                'class'       => 'Eprog\Manager\Models\SettingNotify',
                'order'       => 508,
                'permissions' => ['eprog.manager.access_settings'],
                'keywords'    => 'notify mail'
            ],
            'server' => [
                'label'       => 'eprog.manager::lang.setting_serwer',
                'description' => 'eprog.manager::lang.setting_serwer_desc',
                'category'    => SettingsManager::CATEGORY_SYSTEM,
                'icon'        => 'icon-server',
                'class'       => 'Eprog\Manager\Models\SettingSerwer',
                'order'       => 509,
                'permissions' => ['eprog.manager.access_backup'],
                'keywords'    => 'config backup clear system'
            ],
            'info' => [
                'label'       => 'eprog.manager::lang.setting_info',
                'description' => 'eprog.manager::lang.setting_info_desc',
                'category'    => SettingsManager::CATEGORY_SYSTEM,
                'icon'        => 'icon-info-circle',
                'class'       => 'Eprog\Manager\Models\SettingInfo',
                'order'       => 510,
                'permissions' => ['eprog.manager.access_info'],
                'keywords'    => 'config update'
            ]
        ];

    }

    public function registerPermissions()
    {
        return [
            'eprog.manager.access_project' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 301,
                'label' => 'eprog.manager::lang.access_project'
            ],
            'eprog.manager.access_work' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 302,
                'label' => 'eprog.manager::lang.access_work'
            ],
            'eprog.manager.access_mail' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 303,
                'label' => 'eprog.manager::lang.access_mail'
            ],
            'eprog.manager.access_mailing' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 304,
                'label' => 'eprog.manager::lang.access_mailing'
            ],
            'eprog.manager.access_settings' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 305,
                'label' => 'eprog.manager::lang.access_settings'
            ],
            'eprog.manager.access_scheduler' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 306,
                'label' => 'eprog.manager::lang.access_scheduler'
            ],
            'eprog.manager.access_file' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 307,
                'label' => 'eprog.manager::lang.access_file'
            ],
            'eprog.manager.access_order' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 308,
                'label' => 'eprog.manager::lang.access_order'
            ],
            'eprog.manager.access_invoice' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 309,
                'label' => 'eprog.manager::lang.access_invoice'
            ],
            'eprog.manager.access_ksef' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 310,
                'label' => 'eprog.manager::lang.access_ksef'
            ],
            'eprog.manager.access_accounting' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 310,
                'label' => 'eprog.manager::lang.access_accounting'
            ], 
            'eprog.manager.access_product' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 312,
                'label' => 'eprog.manager::lang.access_product'
            ],
            'eprog.manager.access_xml' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 313,
                'label' => 'eprog.manager::lang.access_xml'
            ],
            'eprog.manager.access_importxml' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 314,
                'label' => 'eprog.manager::lang.access_importxml'
            ],
            'eprog.manager.access_backup' => [
                 'tab'   => 'eprog.manager::lang.access_title',
                 'order' => 315,
                 'label' => 'eprog.manager::lang.access_backup'
             ], 
            'eprog.manager.access_inbox' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 316,
                'label' => 'eprog.manager::lang.access_inbox'
            ],
            'eprog.manager.access_drive' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 317,
                'label' => 'eprog.manager::lang.access_drive'
            ],
            'eprog.manager.access_info' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 318,
                'label' => 'eprog.manager::lang.access_info'
            ],
            'eprog.manager.access_admin' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 319,
                'label' => 'eprog.manager::lang.access_admin'
            ],  
            'eprog.manager.manage_project' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 320,
                'label' => 'eprog.manager::lang.manage_project'
            ], 
            'eprog.manager.manage_work' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 321,
                'label' => 'eprog.manager::lang.manage_work'
            ], 
            'eprog.manager.manage_mail' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 322,
                'label' => 'eprog.manager::lang.manage_mail'
            ],
            'eprog.manager.manage_scheduler' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 323,
                'label' => 'eprog.manager::lang.manage_scheduler'
            ], 
            'eprog.manager.manage_order' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 324,
                'label' => 'eprog.manager::lang.manage_order'
            ], 
            'eprog.manager.manage_invoice' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 325,
                'label' => 'eprog.manager::lang.manage_invoice'
            ], 
            'eprog.manager.manage_ksef' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 326,
                'label' => 'eprog.manager::lang.manage_ksef'
            ], 
            'eprog.manager.manage_accounting' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 327,
                'label' => 'eprog.manager::lang.manage_accounting'
            ], 
            'eprog.manager.manage_product' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 328,
                'label' => 'eprog.manager::lang.manage_product'
            ], 
           'eprog.manager.manage_backup' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 329,
                'label' => 'eprog.manager::lang.manage_backup'
            ], 
            'eprog.manager.manage_inbox' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 330,
                'label' => 'eprog.manager::lang.manage_inbox'
             ], 
            'eprog.manager.manage_drive' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 331,
                'label' => 'eprog.manager::lang.manage_drive'
            ],              
            'eprog.manager.access_free' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 332,
                'label' => 'eprog.manager::lang.access_free'
            ],
            'eprog.manager.manage_info' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 333,
                'label' => 'eprog.manager::lang.manage_info'
            ], 
            'eprog.manager.manage_admin' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 334,
                'label' => 'eprog.manager::lang.manage_admin'
            ], 
            'eprog.manager.edit_scheduler' => [
                'tab'   => 'eprog.manager::lang.access_title',
                'order' => 335,
                'label' => 'eprog.manager::lang.edit_scheduler'
            ],

 
        ];
    }

    public function registerMarkupTags()
    {
        return [
            'filters' => [
         
            ],
            'functions' => [
         
                'clientDate' => function($date) { return Util::dateLocaleClient($date, 'Y-m-d H:i'); },
                'invoiceDate' => function($date) { return Util::dateLocaleClient($date, 'Y-m-d'); },
                'currency' => function($input) { return Util::currency($input); },
                'lcfirst' => function($input) { return lcfirst($input); }
                
            ]
        ];
    }

    public function register()
    {
        $this->registerConsoleCommand('eprog.event:notify', EventNotify::class);
        $this->registerConsoleCommand('eprog.invoice:notify', InvoiceNotify::class);
        $this->registerConsoleCommand('eprog.ksef:synchro', KsefSynchro::class);
        $this->registerConsoleCommand('eprog.system:update', SystemUpdate::class);
      
    }

    public function registerSchedule($schedule)
    {

        $schedule->command('storage:clear')->dailyAt('4:00');//->everyMinute();//->cron("*/1 * * * *");->dailyAt('13:00');

        if(SettingSerwer::get("backup_automat")){
            //$schedule->command('storage:dump-project')->everyMinute();
            $schedule->command('storage:dump-database')->dailyAt('4:00');//->everyMinute();
        }

        $schedule->command('event:notify')->dailyAt('4:00');//->everyMinute();
        $schedule->command('invoice:notify')->dailyAt('4:00');//->everyMinute();
        //$schedule->command('ksef:synchro')->cron("*/10 * * * *");

        $schedule->call(function () {
            \DB::table('system_event_logs')
                ->whereNull('message')
                ->orWhere('message', '')
                ->delete();
        })->everyFiveMinutes();


    }


    public function registerRoutes()
    {
        $router = \App::make('router');


        $router->post('api/backend/login', 'Eprog\Manager\Controllers\Api@login');
        $router->post('api/backend/bearer', 'Eprog\Manager\Controllers\Api@bearer');

        $router->group(['middleware' => \Eprog\Manager\Classes\BackendApiMiddleware::class], function ($router) {

            $router->post('api/backend/invoices', 'Eprog\Manager\Controllers\Api@invoices');
            $router->post('api/backend/orders', 'Eprog\Manager\Controllers\Api@orders');


        });
    }
}
