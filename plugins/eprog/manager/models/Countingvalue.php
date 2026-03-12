<?php namespace Eprog\Manager\Models;

use Model;

/**
 * Model
 */
class Countingvalue extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Validation
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'eprog_manager_countingvalue';

    protected $fillable = ['counting_id','product_id','product','pkwiu','cn','gtu','measure','quantity','netto','vat','brutto','extra'];

    public static $model = ["id" => "int(10)", "counting_id" => "int(11)", "product_id" => "int(11)", "product" => "varchar(255)", "pkwiu" => "varchar(255)", "cn" => "varchar(255)", "gtu" => "varchar(255)", "quantity" => "int(11)", "measure" => "varchar(255)", "netto" => "varchar(255)", "brutto" => "varchar(255)", "vat" => "varchar(255)", "desc" => "mediumtext", "ord" => "int(11)", "disp" => "tinyint(1)", "created_at" => "timestamp", "updated_at" => "timestamp"];


    public function counting()
    {
        return $this->belongsTo('Eprog\Manager\Models\Counting');
    }

}


