<?php namespace Eprog\Manager\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateEprogManagerPeople extends Migration
{
    public function up()
    {
        Schema::create('eprog_manager_people', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('project_id');
            $table->integer('work_id');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('eprog_manager_people');
    }
}
