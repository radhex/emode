<?php namespace Eprog\Manager\Models;

use Model;
use Eprog\Manager\Models\Project;
use October\Rain\Exception\ValidationException;
use Input;
use Flash;
use Eprog\Manager\Controllers\PublicFiles;
use Eprog\Manager\Classes\Util;
use BackendAuth;
use Carbon\Carbon;
use Eprog\Manager\Controllers\Work as WorkController;

/**
 * Model
 */
class Work extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Validation
     */    
    public $rules = [
        'name'    => 'required'
    ];

    
    public $customMessages = [
        'name.required' => 'eprog.manager::lang.valid_name'
    ];
    
    /**
     * @var string The database table used by the model.
     */
    
    protected $fillable = ['name','project_id','status_id','start','stop'];

    public $table = 'eprog_manager_work';

    public $hasMany = [

            'task' => ['Eprog\Manager\Models\Task', 'delete' => true]
    ];

  
    public $belongsTo = [
        
            'project' => ['Eprog\Manager\Models\Project']
    ];
    
    public $attachMany = [
        
        'public_files' => ['System\Models\File', 'public' => false],
        'private_files' => ['System\Models\File', 'public' => false]

    ];

    protected $jsonable = ['staff'];


    public function fileUploadRules()
    {

        return ['public_files' => 'required|maxFiles:20|max:5000', 'private_files' => 'required|maxFiles:20|max:5000'];

    }

    
    public function afterDelete()
    {
  
        foreach ($this->public_files as $file) {
            $file && $file->delete();
        }

        foreach ($this->private_files as $file) {
            $file && $file->delete();
        }


    }
    
    public function beforeSave()
    {


        if(Input::has("add_task") && Input::get("add_task_flag")) {
                if(Input::get("add_task") == '') return false;
                $data = ["desc" => Input::get("add_task"),"work_id" => $this->id, "user_id"=> BackendAuth::getUser()->id , "start"=> Carbon::now()];
                Task::create($data);
		        Flash::success(trans("eprog.manager::lang.add_task"));
                return false;
        }   

        if(Input::has("del_task_id") && Input::get("del_task_flag")) {

                Task::destroy(Input::get("del_task_id"));
		        Flash::success(trans("eprog.manager::lang.del_task"));
                return false;
        }   

        if(Input::has("edit_task_id") && Input::get("edit_task_flag")) {

		        $task = Task::find(Input::get("edit_task_id")); 
                if($task) $task->update(["desc"=> Input::get("edit_task".Input::get("edit_task_id"))]);
		        Flash::success(trans("eprog.manager::lang.edit_task"));
                return false;
        }   


        if(!Input::has("Work.project_id") && !Input::has("add_work") && !Input::has("delta"))
        throw new ValidationException(['my_field'=>trans("eprog.manager::lang.no_project")]);
	
    }
    
    public function getProjectIdOptions()
    {

        $id = $this->project_id;
        if(Input::has("id")) $id = Input::get("id");
        $lista = [];
        $user = Project::find($id);
        if($user) $lista[$user->id] = $user->name." (".$user->id.")";

     
    	if(BackendAuth::getUser()->hasAccess("eprog.manager.manage_work")){
            	$user = Project::all();
            	foreach($user as $user){
                		$lista[$user->id] = $user->name." (".$user->id.")";
            	}
    	}

        //if(Input::has("id") && isset($lista[Input::get("id")])) $lista =[Input::get("id") => $lista[Input::get("id")]];
        
        return  $lista;

    }


    public function getStaffOptions()
    {

      $list = [];
      $user =  \Backend\Models\User::where("is_superuser","=","0")->get();
    	foreach($user as $user){
    	
    		if(Util::isGroup($user, "worker"))
    		$list[$user->id] = $user->first_name." ".$user->last_name;
    		
    	}

	    return $list;
        
    }


    public function getFileAttribute()
    {


    	$return = "";
    	foreach($this->public_files as $file)
            $return .= "<a href='".PublicFiles::getDownloadUrl($file)."'>".$file->file_name."</a><br>";

    	return $return;

    }

    public function getStatusIdOptions()
    {
        $statues = WorkController::status();
        array_unshift($statues , "-- ".strtolower(trans("eprog.manager::lang.select"))." --");
        return  $statues;

    }

    public function getStatusAttribute()
    {

        return  $this->status_id > 0 && isset(WorkController::status()[$this->status_id])  ? WorkController::status()[$this->status_id] : "";

    }


    
    public function scopeFilterByProject($query, $filter)
    {

        return $query->whereHas('project', function($group) use ($filter) {
            $group->whereIn('id', $filter);
        });
	
    }
        
}