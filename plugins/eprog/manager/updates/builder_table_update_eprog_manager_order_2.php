<?php namespace Eprog\Manager\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateEprogManagerOrder2 extends Migration
{
    public function up()
    {
        Schema::table('eprog_manager_order', function($table)
        {
            $table->string('nr', 255)->nullable();
            $table->decimal('brutto', 10, 2)->nullable();
            $table->decimal('netto', 10, 2)->nullable();
            $table->decimal('vat', 10, 2)->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('eprog_manager_order', function($table)
        {
            $table->dropColumn('nr');
            $table->dropColumn('brutto');
            $table->dropColumn('netto');
            $table->dropColumn('vat');
        });
    }
}
