<?php namespace Eprog\Manager\Models;

use Eprog\Manager\Models\Payroll as ModelPayroll;
use October\Rain\Exception\ValidationException;
use Model;
use Session;
use Input;

/**
 * Model
 */
class Payroll extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Validation
     */
    public $rules = [
        'nr'    => 'required',
        'period'    => 'required'
    ];

    public $customMessages = [
        'name.required' => 'eprog.manager::lang.valid_name'
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'eprog_manager_payroll';

    public function beforeSave()
    {

        self::summaryPrice();

        $exists  = ModelPayroll::where("nip",Session::get("selected.nip"))->where("nr", Input::get("Payroll.nr"))->where("id","!=",$this->id)->count();
        if($exists > 0) throw new ValidationException(['my_field'=>trans("eprog.manager::lang.attribute_exists",["attribute" => trans("eprog.manager::lang.nr")])]);

    }


    public function beforeCreate()
    {
    
        $this->nip =  Session::get("selected.nip") ?? '';

        $exists  = ModelPayroll::where("nip",Session::get("selected.nip"))->where("nr", Input::get("Payroll.nr"))->count();
        if($exists > 0) throw new ValidationException(['my_field'=>trans("eprog.manager::lang.attribute_exists",["attribute" => trans("eprog.manager::lang.nr")])]);

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

    public function summaryPrice()
    {

        $length = sizeof(Input::get('data.p1'));
        //throw new ValidationException(['my_field'=>json_encode(Input::get('data.p1'))]);
        $data = [];
        for($i = 0; $i < $length - 1;$i++){
        
            $data[$i]['p1'] = trim(Input::get('data.p1.'.$i));
            $data[$i]['p2'] = trim(Input::get('data.p2.'.$i));
            for ($p = 3; $p <= 21; $p++) {
                $data[$i]['p'.$p] = floatval(
                    preg_replace(
                        ["/,/i", "/\s/i"],
                        [".", ""],
                        Input::get('data.p'.$p.'.'.$i)
                    )
                );
            }

        }
 
        $this->data = json_encode($data);
        //throw new ValidationException(['my_field'=>$this->data]);

    }
    
}