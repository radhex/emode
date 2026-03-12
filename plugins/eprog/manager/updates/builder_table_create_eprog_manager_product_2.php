<?php namespace Eprog\Manager\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateEprogManagerProduct2 extends Migration
{
    public function up()
    {
        Schema::create('eprog_manager_product', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('category_id')->nullable();
            $table->string('name')->nullable();
            $table->text('desc')->nullable();
            $table->text('info')->nullable();
            $table->decimal('brutto', 10, 0)->nullable();
            $table->decimal('netto', 10, 0)->nullable();
            $table->decimal('vat', 10, 0)->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('ord')->nullable();
            $table->boolean('disp')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('eprog_manager_product');
    }
}
