<?php namespace Eprog\Manager\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateEprogManagerStaff extends Migration
{
    public function up()
    {
        Schema::rename('eprog_manager_people', 'eprog_manager_staff');
        Schema::table('eprog_manager_staff', function($table)
        {
            $table->increments('id')->unsigned(false)->change();
        });
    }
    
    public function down()
    {
        Schema::rename('eprog_manager_staff', 'eprog_manager_people');
        Schema::table('eprog_manager_people', function($table)
        {
            $table->increments('id')->unsigned()->change();
        });
    }
}
