<?php namespace Eprog\Manager\Console;

use Storage;
use Illuminate\Console\Command;
use System\Models\File;
use Eprog\Manager\Models\Scheduler;
use Eprog\Manager\Classes\Util;
use Rainlab\User\Models\User;
use Carbon\carbon;
use Illuminate\Support\Facades\Mail;
use System\Models\MailSetting;
use Config;
use BackendAuth;
use Eprog\Manager\Models\SettingNotify;

class EventNotify extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'event:notify';

    /**
     * @var string The console command description.
     */
    protected $description = 'NotIfy schedule event.';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {

        foreach(Scheduler::all() as $schedule){


		$start = new Carbon($schedule->start);
		$diff = $start->diffInDays(Carbon::now());
		
		if($schedule->start > Carbon::now()){
			if($diff == 1 || $diff == 2 || $diff == 3 || $diff == 7){

				if($schedule->user_id && SettingNotify::get("event_notify")) self::mailEvent($schedule->user_id, $schedule->name, $diff);
			}
		}
		
	}

    }

    public function mailEvent($user_id, $name, $day) {

		$user = User::find($user_id);
     
		if($day > 4)  $head = "Pozostało ".$day." dni"; 
		if($day == 3) $head = "Pozostały ".$day." dni"; 
		if($day == 1) $head = "Pozostał  ".$day." dzień"; 


		$data = [
		
			"head" => $head,
			"name" => $name,
			"username" => $user->name			

		];
	
        	Mail::send('eprog.manager::mail.eventnotify', $data, function($message) use ($user) {

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
