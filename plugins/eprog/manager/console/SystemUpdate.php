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
use October\Rain\Exception\ValidationException;

class SystemUpdate  extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'system:update';

    /**
     * @var string The console command description.
     */
    protected $description = 'Emode system update.';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    { 

       $host = explode("//",env("APP_URL"))[1];
       $ref = file_get_contents('http://emode.pl/service/api/update.php?host='.$host.'&php='.substr(env('PHP_VERSION'),0,3));
       $ref = explode(" ",$ref); 
   
       if(sizeof($ref) == 3 && !preg_match("/demo.emode.pl/",env("APP_URL")) && !preg_match("/crm.emode.pl/",env("APP_URL")) && !preg_match("/prod.emode.pl/",env("APP_URL"))){
            $actual = file_get_contents("vendor/version.php");
            if($ref[0] != $actual){
                $update = "http://emode.pl/service/update/em/".$ref[0]."/".$ref[1].".zip";
                if(substr(str_replace("v","",$ref[0]),0,3) == substr(env('PHP_VERSION'),0,3)){
                    $file = $ref[1].".zip";
                    if(file_exists($file)) system("rm ".$file);
                    $personalfile = md5($host).".zip";
                    if(file_exists($personalfile)) system("rm ".$personalfile);
                    $personal = "http://emode.pl/service/update/em/".$ref[0]."/".$personalfile;
                    system("wget ".$update); 
                    system("wget ".$personal); 
                    if(file_exists($file) && file_exists($personalfile)){
                        if(self::verifyZipContents($file)){
                            self::clear();
                            exec("unzip -o ".$file, $output, $returnCode);
                            exec("unzip -o ".$personalfile, $output, $returnCode1);
                            if ($returnCode === 0 && $returnCode1 === 0) {
                                system("rm ".$file);
                                system("rm ".$personalfile);
                                if(file_exists("update.sql")){
                                        system("mysql ".env('DB_DATABASE')." -u ".env('DB_USERNAME')." -p".env('DB_PASSWORD')." < update.sql");   
                                        system("rm update.sql");
                                }
                                file_put_contents("vendor/version.php",$ref[0]);
                                return "1";
                            }
                            else{
                                system("rm ".$file);
                                system("rm ".$personalfile);
                                return "4";
                            }
                        }
                        else{
                            return "5";
                        }
                    }
                    else 
                        return "4";                  
                }
                else
                    return "3";
            }
            else
                return "2";

       }
       else
           return "4"; 

    }

    public function clear()
    {

        system("rm -r  .vscode");
        system("rm -r  bootstrap");
        system("rm -r  config");
        system("rm -f  docs/*");
        system("rm -f  docs/ksef-pdf-generator/*");
        system("rm -r  docs/ksef-pdf-generator/assets");
        system("rm -r  docs/ksef-pdf-generator/dist");
        system("rm -r  docs/ksef-pdf-generator/src");
        system("rm -r  modules");
        system("rm -r  plugins/eprog");
        system("rm -r  plugins/extends");
        system("rm -r  plugins/genius");
        system("rm -r  plugins/luketowers");
        system("rm -r  plugins/october");
        system("rm -r  plugins/offline");
        system("rm -r  plugins/rainlab");
        system("rm -r  plugins/responsiv");
        system("rm -r  plugins/winter");
        system("rm -r  themes");
        system("rm -r  vendor");
        system("rm  .editorconfig");
        system("rm  .gitignore");
        system("rm  .htaccess");
        system("rm  artisan");
        system("rm  composer.json");
        system("rm  error.php");
        system("rm  index.php");
        system("rm  LICENSE");
        system("rm  phpcs.xml");
        system("rm  phpunit.xml");
        system("rm  README.md");

    }

    private function verifyZipContents($zipFile)
    {

        $requiredPaths = [
            "bootstrap",
            "config",
            "docs",
            "docs/ksef-pdf-generator/assets",
            "docs/ksef-pdf-generator/dist",
            "docs/ksef-pdf-generator/src",
            "modules",
            "plugins",
            "plugins/eprog",
            "plugins/rainlab",
            "themes",
            "vendor",
            "artisan",
            "composer.json",
            "error.php",
            "index.php",
            "LICENSE",
            "phpcs.xml",
            "phpunit.xml",
            "README.md",
        ];

        $zip = new \ZipArchive;
        if ($zip->open($zipFile) !== true) {
            return false; // nie udało się otworzyć ZIP
        }


        foreach ($requiredPaths as $path) {
            $found = false;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (strpos($stat['name'], rtrim($path, '/')) === 0) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $zip->close();
                return false;
            }
        }

        $zip->close();
        return true; 
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
