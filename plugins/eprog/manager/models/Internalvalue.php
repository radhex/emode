<?php namespace Eprog\Manager\Models;

use Model;

/**
 * Model
 */
class Internalvalue extends Model
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
    public $table = 'eprog_manager_internalvalue';

    protected $fillable = ['internal_id','product_id','product','measure','quantity','amount'];

    public function Internal()
    {
        return $this->belongsTo('Eprog\Manager\Models\Internal');
    }

}


