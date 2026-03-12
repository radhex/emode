<?php namespace Eprog\Manager\Models;

use Model;

/**
 * Model
 */
class Ordervalue extends Model
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
    public $table = 'eprog_manager_ordervalue';

    protected $fillable = ['order_id','product_id','product','pkwiu','cn','gtu','measure','quantity','netto','vat','brutto','exchange'];

    public function order()
    {
        return $this->belongsTo('Eprog\Manager\Models\Order');
    }

}


