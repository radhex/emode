<?php namespace Eprog\Manager\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateEprogManagerMailing extends Migration
{
    public function up()
    {
        Schema::create('eprog_manager_mailing', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->text('groups');
            $table->string('name');
            $table->text('desc');
            $table->dateTime('date');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('eprog_manager_mailing');
    }
}
