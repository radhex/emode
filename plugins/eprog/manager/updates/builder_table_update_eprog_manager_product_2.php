<?php namespace Eprog\Manager\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateEprogManagerProduct2 extends Migration
{
    public function up()
    {
        Schema::table('eprog_manager_product', function($table)
        {
            $table->decimal('brutto', 10, 2)->change();
            $table->decimal('netto', 10, 2)->change();
            $table->decimal('vat', 10, 2)->change();
        });
    }
    
    public function down()
    {
        Schema::table('eprog_manager_product', function($table)
        {
            $table->decimal('brutto', 10, 0)->change();
            $table->decimal('netto', 10, 0)->change();
            $table->decimal('vat', 10, 0)->change();
        });
    }
}
