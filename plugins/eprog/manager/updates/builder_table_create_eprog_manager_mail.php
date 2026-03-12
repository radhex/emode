<?php namespace Eprog\Manager\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateEprogManagerMail extends Migration
{
    public function up()
    {
        Schema::create('eprog_manager_mail', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_from')->nullable();
            $table->integer('user_to')->nullable();
            $table->integer('admin_from')->nullable();
            $table->integer('admin_to')->nullable();
            $table->integer('answer')->nullable();
            $table->dateTime('date')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('eprog_manager_mail');
    }
}
