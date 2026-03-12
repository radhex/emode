<?php namespace Eprog\Manager\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateEprogManagerTask extends Migration
{
    public function up()
    {
        Schema::table('eprog_manager_task', function($table)
        {
            $table->dateTime('start')->nullable();
            $table->dateTime('stop')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('eprog_manager_task', function($table)
        {
            $table->dropColumn('start');
            $table->dropColumn('stop');
        });
    }
}
