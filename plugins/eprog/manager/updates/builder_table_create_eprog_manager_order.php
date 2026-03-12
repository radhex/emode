<?php namespace Eprog\Manager\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateEprogManagerOrder extends Migration
{
    public function up()
    {
        Schema::create('eprog_manager_order', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('id_user');
            $table->string('name');
            $table->text('desc');
            $table->text('info');
            $table->text('staff');
            $table->dateTime('date');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('eprog_manager_order');
    }
}
