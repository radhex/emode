<?php namespace Eprog\Manager\Console;

use Storage;
use Illuminate\Console\Command;
use System\Models\File;
use Eprog\Manager\Models\Invoice;
use Eprog\Manager\Classes\Util;
use Rainlab\User\Models\User;
use Carbon\carbon;
use Illuminate\Support\Facades\Mail;
use System\Models\MailSetting;
use Config;
use BackendAuth;
use Eprog\Manager\Models\SettingNotify;
use Eprog\Manager\Models\SettingConfig;
use Eprog\Manager\Classes\Ksef;

class KsefSynchro extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'ksef:synchro';

    /**
     * @var string The console command description.
     */
    protected $description = 'Ksef synchronization export invoice.';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {

        Ksef::exportInvoices();

    }


    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }

}
