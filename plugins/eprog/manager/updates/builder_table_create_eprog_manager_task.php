<?php namespace Eprog\Manager\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateEprogManagerTask extends Migration
{
    public function up()
    {
        Schema::create('eprog_manager_task', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('work_id');
            $table->integer('user_id')->nullable();
            $table->string('name')->nullable();
            $table->text('desc')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('eprog_manager_task');
    }
}
