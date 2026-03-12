<?php namespace Eprog\Manager\Models;

use October\Rain\Exception\ValidationException;
use Eprog\Manager\Models\Taxfile as ModelTaxfile ;
use Eprog\Manager\Classes\Util;

use Session;
use Input;
use Model;

/**
 * Model
 */
class Taxfile extends Model
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
    public $table = 'eprog_manager_taxfile';

    public $attachMany = [
    
        'record' => ['System\Models\File', 'public' => false],
        'document' => ['System\Models\File', 'public' => false],
        'other' => ['System\Models\File', 'public' => false],

    ];

    
    public function beforeSave() {

        //throw new ValidationException(['my_field'=> json_encode(Input::all())]);


        $exists  = ModelTaxfile::where("year",Input::get("Taxfile.year"))->where("month",Input::get("Taxfile.month"))->where("nip",Session::get("selected.nip"))->where("id","!=",$this->id)->count();
        if($exists > 0) throw new ValidationException(['my_field'=>trans("eprog.manager::lang.year_month_exists")]);


    }

    public function beforeCreate()
    {


        $exists  = ModelTaxfile::where("year",Input::get("Taxfile.year"))->where("month",Input::get("Taxfile.month"))->where("nip",Session::get("selected.nip"))->count();
        if($exists > 0) throw new ValidationException(['my_field'=>trans("eprog.manager::lang.year_month_exists")]);

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

    public function fileUploadRules()
    {

        return ['record' => 'required|maxFiles:100|max:5000', 'document' => 'required|maxFiles:5|max:5000', 'other' => 'required|maxFiles:5|max:5000'];

    }


    public function afterDelete()
    {
    
        foreach ($this->record as $file) {
            $file && $file->delete();
        }

        foreach ($this->document as $file) {
            $file && $file->delete();
        }

        foreach ($this->other as $file) {
            $file && $file->delete();
        }

    }
    
}