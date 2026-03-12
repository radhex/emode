<?php namespace Eprog\Manager\Models;

use October\Rain\Exception\ValidationException;
use Eprog\Manager\Models\Zus as ModelZus ;
use Eprog\Manager\Classes\Util;

use Session;
use Input;
use Model;

/**
 * Model
 */
class Zus extends Model
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
    public $table = 'eprog_manager_zus';
/*
    protected $fillable = [

        'year',
        'month',
        'nip',
        'social',
        'health',
        'created_at',
        'updated_at'
    ];
*/

    
    public function beforeSave() {

        //throw new ValidationException(['my_field'=> json_encode(Input::all())]);

  
        $this->social = (float)str_replace(" ","",str_replace(",",".",Input::get("Zus.social")));
        $this->health = (float)str_replace(" ","",str_replace(",",".",Input::get("Zus.health")));


        $exists  = ModelZus::where("year",Input::get("Zus.year"))->where("month",Input::get("Zus.month"))->where("nip",Session::get("selected.nip"))->where("id","!=",$this->id)->count();
        if($exists > 0) throw new ValidationException(['my_field'=>trans("eprog.manager::lang.year_month_exists")]);


    }

    public function beforeCreate()
    {


        $exists  = ModelZus::where("year",Input::get("Zus.year"))->where("month",Input::get("Zus.month"))->where("nip",Session::get("selected.nip"))->count();
        if($exists > 0) throw new ValidationException(['my_field'=>trans("eprog.manager::lang.year_month_exists")]);

        $this->nip =  Session::get("selected.nip") ?? '';


    }
    


    public function getYearOptions()
    {
        $year = date("Y", time());
        $years = [];
        for($i=0;$i<=10;$i++){
            if($year-$i < 2022 && !in_array($_SERVER["HTTP_HOST"],["erp.emode.pl","crm.emode.pl","demo.emode.pl","prod.emode.pl"])) continue;
            $years[$year-$i] = $year-$i;
        }
        return $years;

    }

    public function getMonthOptions()
    {

        return [1 => trans("eprog.manager::lang.months.1"), 2 => trans("eprog.manager::lang.months.2"), 3 => trans("eprog.manager::lang.months.3"), 4 => trans("eprog.manager::lang.months.4"), 5 => trans("eprog.manager::lang.months.5"), 6 => trans("eprog.manager::lang.months.6"), 7 => trans("eprog.manager::lang.months.7"), 8 => trans("eprog.manager::lang.months.8"), 9 => trans("eprog.manager::lang.months.9"), 10 => trans("eprog.manager::lang.months.10"), 11 => trans("eprog.manager::lang.months.11"), 12 => trans("eprog.manager::lang.months.12")];

    }

    public function getTaxFormOptions(){

        return Util::getTaxForm();

    }

    public function scopeOrderByHealth($query, $direction = 'asc')
    {
        return $query->orderBy(
            Db::raw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.health'))"),
            $direction
        );
    }
    
}