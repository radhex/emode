<?php namespace Eprog\Manager\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateEprogManagerMail2 extends Migration
{
    public function up()
    {
        Schema::table('eprog_manager_mail', function($table)
        {
            $table->dateTime('date')->nullable()->change();
        });
    }
    
    public function down()
    {
        Schema::table('eprog_manager_mail', function($table)
        {
            $table->dateTime('date')->nullable(false)->change();
        });
    }
}
