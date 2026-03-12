<?php namespace Eprog\Manager\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateEprogManagerProduct3 extends Migration
{
    public function up()
    {
        Schema::table('eprog_manager_product', function($table)
        {
            $table->integer('vat_procent');
            $table->integer('quantity')->nullable(false)->change();
            $table->integer('ord')->nullable(false)->change();
            $table->boolean('disp')->nullable(false)->change();
        });
    }
    
    public function down()
    {
        Schema::table('eprog_manager_product', function($table)
        {
            $table->dropColumn('vat_procent');
            $table->integer('quantity')->nullable()->change();
            $table->integer('ord')->nullable()->change();
            $table->boolean('disp')->nullable()->change();
        });
    }
}
