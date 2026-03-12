<?php namespace Eprog\Manager\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateEprogManagerStatus extends Migration
{
    public function up()
    {
        Schema::create('eprog_manager_status', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->text('desc');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('eprog_manager_status');
    }
}
