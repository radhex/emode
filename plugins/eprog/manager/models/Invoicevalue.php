<?php namespace Eprog\Manager\Models;

use Model;

/**
 * Model
 */
class Invoicevalue extends Model
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
    public $table = 'eprog_manager_invoicevalue';

    protected $fillable = ['invoice_id','product_id','product','pkwiu','cn','gtu','measure','quantity','netto','vat','brutto','extra','exchange'];


    public function invoice()
    {
        return $this->belongsTo('Eprog\Manager\Models\Invoice');
    }

}


