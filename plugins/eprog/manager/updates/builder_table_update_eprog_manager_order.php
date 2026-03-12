<?php namespace Eprog\Manager\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateEprogManagerOrder extends Migration
{
    public function up()
    {
        Schema::table('eprog_manager_order', function($table)
        {
            $table->increments('id')->unsigned(false)->change();
            $table->integer('id_user')->nullable()->change();
            $table->string('name')->nullable()->change();
            $table->text('desc')->nullable()->change();
            $table->text('info')->nullable()->change();
            $table->text('staff')->nullable()->change();
            $table->dateTime('date')->nullable()->change();
        });
    }
    
    public function down()
    {
        Schema::table('eprog_manager_order', function($table)
        {
            $table->increments('id')->unsigned()->change();
            $table->integer('id_user')->nullable(false)->change();
            $table->string('name', 255)->nullable(false)->change();
            $table->text('desc')->nullable(false)->change();
            $table->text('info')->nullable(false)->change();
            $table->text('staff')->nullable(false)->change();
            $table->dateTime('date')->nullable(false)->change();
        });
    }
}
