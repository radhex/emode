<?php namespace Eprog\Manager\Models;

use October\Rain\Exception\ValidationException;
use Eprog\Manager\Models\File as ModelFile ;
use Model;
use Input;

/**
 * Model
 */
class File extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /*
     * Validation
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'eprog_manager_file';


    public $attachMany = [
   
        'document' => ['System\Models\File', 'public' => false],
        'image' => ['System\Models\File', 'public' => false],
        'media' => ['System\Models\File', 'public' => false],

    ];

  
    public function __construct(array $attributes = [])
    {
        
        parent::__construct();
    
    }

    public function beforeSave() {

        //throw new ValidationException(['my_field'=> json_encode(Input::all())]);


        $exists  = ModelFile::where("year",Input::get("Taxfile.year"))->where("month",Input::get("Taxfile.month"))->where("id","!=",$this->id)->count();
        if($exists > 0) throw new ValidationException(['my_field'=>trans("eprog.manager::lang.year_month_exists")]);


    }

    public function beforeCreate()
    {


        $exists  = ModelFile::where("year",Input::get("Taxfile.year"))->where("month",Input::get("Taxfile.month"))->count();
        if($exists > 0) throw new ValidationException(['my_field'=>trans("eprog.manager::lang.year_month_exists")]);


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

    public function fileUploadRules()
    {

        return ['document' => 'required|maxFiles:100|max:5000', 'image' => 'required|maxFiles:5|max:5000', 'media' => 'required|maxFiles:100|max:5000'];

    }


    public function afterDelete()
    {
  
        foreach ($this->document as $file) {
            $file && $file->delete();
        }

        foreach ($this->image as $file) {
            $file && $file->delete();
        }


        foreach ($this->media as $file) {
            $file && $file->delete();
        }

    }
    
}