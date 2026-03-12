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

class InvoiceNotify extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'invoice:notify';

    /**
     * @var string The console command description.
     */
    protected $description = 'Notofy schedule event.';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {

        foreach(Invoice::all() as $invoice){


    		$start = new Carbon($invoice->make_at);
    		$diff = $start->addDays($invoice->paytime);
            echo Config::get('app.url');

    		if($diff < Carbon::now() && $invoice->pay_at == null){
    			
    			if($invoice->user_id && SettingNotify::get("invoice_notify")) self::mailInvoice($invoice);
    		
    		}

		
	   }


    }

    public function mailInvoice($invoice) {

		$user = User::find($invoice->user_id);
     
	
		$data = [
		
			"nr" => $invoice->nr,
			"brutto" => Util::currency($invoice->brutto),
			"currency" => $invoice->currency ? $invoice->currency : SettingConfig::get("currency"), 
			"username" => $user->name				

		];
	
        	Mail::send('eprog.manager::mail.invoicenotify', $data, function($message) use ($user) {

			$message->to($user->email, $user->name);

        	});
	
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
