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
use Redirect;
use File;
use Eprog\Manager\Classes\Util;
use ValidationException;


class SettingSerwer extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'serwer_settings';
    public $settingsFields = 'fields.yaml';


    public function initSettingsData()
    {

        $this->backup_automat = true;
      
    }


    public function beforeSave()
    {

    
		if(Input::has("backup") && Input::get("backup")){

			Artisan::call('storage:dump');
			Flash::success(e(trans('eprog.manager::lang.backup_done')));

		}

		if(Input::has("clear") && Input::get("clear"))
		{

			Artisan::call('storage:clear'); 
			//Artisan::call('cache:clear');	
			//Artisan::call('optimize');

			system("rm -rf ../logs/*");	
			system("rm -rf storage/logs/*");		
			Flash::success(e(trans('eprog.manager::lang.clear_done')));

		}

		if(Input::has("restore") && Input::get("restore"))
		{
			
			$file = Input::get("file");
			$filepath = config('app.backup')."/".$file;
			$filepath_absolute = substr($filepath, 3);
			$dir = explode("/", getcwd());
			$success = false;
			if(is_array($dir) && sizeof($dir) > 0){
				$dir = $dir[sizeof($dir)-1];
				if(file_exists($filepath)) {

					if (preg_match("/zip/i", $file)){

						if(file_exists("../".$dir)) {
							$database = config('database.connections.' . config('database.default'));
							exec("cd .. && unzip -o ".$filepath_absolute, $output, $returnCode);
							$sql = explode(".zip",$file)[0] ?? '';						
							if($returnCode === 0 && file_exists("../".$sql)){
								exec("mysql -u'".$database['username']."' -p'".$database['password']."' '".$database['database']."' < 'docs/empty.sql'");
								exec("cd .. &&  mysql -u'".$database['username']."' -p'".$database['password']."' '".$database['database']."' < '".$sql."' && rm '".$sql."'");
								Flash::success(e(trans('eprog.manager::lang.backup_database_restore')));
								$success = true;
							}
						
						}

					}

				}
			
			}
			if(!$success) 	throw new ValidationException(["error"=>e(trans('eprog.manager::lang.backup_error'))]);


		}


		if(Input::has("delete") && Input::get("delete"))
		{

			$file = Input::get("file");
			$filepath = config('app.backup')."/".$file;

			if(file_exists($filepath)){

				File::delete($filepath);	
				Flash::success(e(trans('eprog.manager::lang.del_task')));
		
			}
			else
				throw new ValidationException(["error"=>e(trans('eprog.manager::lang.error_task'))]);
		
			
			
		}
	

    }

    public static function getRegionOptions()
    {

		return  Util::getRegions();

    }
}