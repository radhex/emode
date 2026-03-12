<?php namespace Eprog\Manager\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateEprogManagerMail extends Migration
{
    public function up()
    {
        Schema::table('eprog_manager_mail', function($table)
        {
            $table->string('name')->nullable();
            $table->text('desc')->nullable();
            $table->dateTime('date')->nullable(false)->change();
        });
    }
    
    public function down()
    {
        Schema::table('eprog_manager_mail', function($table)
        {
            $table->dropColumn('name');
            $table->dropColumn('desc');
            $table->dateTime('date')->nullable()->change();
        });
    }
}
