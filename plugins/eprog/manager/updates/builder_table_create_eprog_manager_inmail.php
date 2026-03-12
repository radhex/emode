<?php namespace Eprog\Manager\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateEprogManagerInmail extends Migration
{
    public function up()
    {
        Schema::create('eprog_manager_inmail', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('receive_id');
            $table->integer('send_id');
            $table->string('name');
            $table->text('desc');
            $table->boolean('send');
            $table->boolean('read');
            $table->boolean('answer');
            $table->dateTime('date');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('eprog_manager_inmail');
    }
}
