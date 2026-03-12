<?php namespace Eprog\Manager\Models;

use October\Rain\Exception\ValidationException;
use Eprog\Manager\Classes\Util;
use Model;
use Session;
use Input;

/**
 * Model
 */
class Worker extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Validation
     */
    public $rules = [
        'name'    => 'required',
        'surname'    => 'required',
        'email'    => 'between:6,100|email',
        'name'  =>  'between:2,100',
        'surname'  =>  'between:2,100',
        'birthday'    => 'birthday',
    ];



    /**
     * @var string The database table used by the model.
     */
    public $table = 'eprog_manager_worker';

    public $attachMany = [
        
        'files' => ['System\Models\File', 'public' => false],

    ];

    public function beforeSave()
    {

  
        $this->nip =  Session::get("selected.nip") ?? '';

    }


    public function beforeCreate()
    {
    
    
        $this->nip =  Session::get("selected.nip") ?? '';

    }

    public function getYearOptions()
    {
        $year = date("Y", time());
        $years = [];
        for($i=0;$i<=10;$i++)
        $years[$year-$i] = $year-$i;
        return $years;

    }

    public function getMonthOptions()
    {

        return [1 => trans("eprog.manager::lang.months.1"), 2 => trans("eprog.manager::lang.months.2"), 3 => trans("eprog.manager::lang.months.3"), 4 => trans("eprog.manager::lang.months.4"), 5 => trans("eprog.manager::lang.months.5"), 6 => trans("eprog.manager::lang.months.6"), 7 => trans("eprog.manager::lang.months.7"), 8 => trans("eprog.manager::lang.months.8"), 9 => trans("eprog.manager::lang.months.9"), 10 => trans("eprog.manager::lang.months.10"), 11 => trans("eprog.manager::lang.months.11"), 12 => trans("eprog.manager::lang.months.12")];

    }

    public function getCountryOptions()
    {

      return Util::getCountries()[Session::get("locale")];

    }

    
}