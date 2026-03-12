<?php namespace Genius\StorageClear\Console;

use Storage;
use Illuminate\Console\Command;
use System\Models\File;
use Carbon\Carbon;

class StorageDumpDatabase extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'storage:dump-database';

    /**
     * @var string The console command description.
     */
    protected $description = 'Generate a dump file of your database only.';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {

        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);
        $path = "../";//env('DOMAINS_PATH')."/".env('PAGE_PATH');
        $backup = "/backup/";
        $carbon = Carbon::now();
        $carbon->setTimezone("Europe/Warsaw");
        $schema = "herlin";
        $db = env('DB_DATABASE');
        $user = env('DB_USERNAME');
        $password = env('DB_PASSWORD');

        $file = $carbon->format("Y_m_d_H_i") . '_db.sql';
        $command = sprintf("mysqldump ".$db." -u ".$user." -p".$password." > ".$path.$backup.$file);

        if (!is_dir($path.$backup)) {
            mkdir($path.$backup, 0755, true);
        }

        exec($command);

        $command = sprintf("zip -j %s ".$path.$backup.$file, $path.$backup.$file.".zip");
        exec($command);

        $command = sprintf("rm ".$path.$backup.$file);
        exec($command);

        self::clearDirectory($path.$backup);
    }

    private function clearDirectory($folderName)
    {

        $expire = strtotime('-2 MONTH');

        if (file_exists($folderName)) {
            foreach (new \DirectoryIterator($folderName) as $fileInfo) {
                if ($fileInfo->isDot()) {
                    continue;
                }

                $time = explode("_",explode("_db",$fileInfo->getFilename())[0]);
                $filetime = strtotime($time[0]."-".$time[1]."-".$time[2]." ".$time[3].":".$time[4]);

                if ($fileInfo->isFile() && $filetime < $expire) {
                    unlink($fileInfo->getRealPath());
                }
            }
        }

    }

    private function zipSql($folderName)
    {

        if (file_exists($folderName)) {
            foreach (new \DirectoryIterator($folderName) as $fileInfo) {
                if ($fileInfo->isDot()) {
                    continue;
                }


                $command = sprintf("zip -j %s ".$fileInfo->getRealPath(), $fileInfo->getRealPath().".zip");
                exec($command);

                unlink($fileInfo->getRealPath());

     
            }
        }

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
