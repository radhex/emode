<?php namespace Eprog\Manager\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateEprogManagerScheduler2 extends Migration
{
    public function up()
    {
        Schema::create('eprog_manager_scheduler', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('category_id')->nullable();
            $table->integer('admin_id')->nullable();
            $table->string('name')->nullable();
            $table->text('desc')->nullable();
            $table->text('time')->nullable();
            $table->dateTime('start')->nullable();
            $table->dateTime('stop')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('eprog_manager_scheduler');
    }
}
