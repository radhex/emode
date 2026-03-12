<?php namespace Eprog\Manager\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateEprogManagerCategory extends Migration
{
    public function up()
    {
        Schema::create('eprog_manager_category', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('id');
            $table->string('name');
            $table->text('desc');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('eprog_manager_category');
    }
}
