<?php namespace Eprog\Manager\Models;

use Model;
use Rainlab\User\Models\User;
use October\Rain\Exception\ValidationException;
use Backend\Controllers\Files;
use Eprog\Manager\Models\Work;
use Input;
use Carbon\Carbon;
use Flash;
use Redirect;
use Eprog\Manager\Classes\Util;
use Mail as SendMail;
use Eprog\Manager\Controllers\PublicFiles;
use BackendAuth;
use Eprog\Manager\Models\SettingNotify;
use Eprog\Manager\Controllers\Project as ProjectController;

/**
 * Model
 */
class Project extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Validation
     */
    public $rules = [
        'name'    => 'required'
    ];

    protected $fillable = ['start','stop'];

    
    public $customMessages = [
        'name.required' => 'eprog.manager::lang.valid_name'
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'eprog_manager_project';

    public $hasMany = [
            'work' => ['Eprog\Manager\Models\Work', 'delete' => true]
    ];
    
    public $belongsTo = [
            'user' => ['RainLab\User\Models\User']
    ];
    
    public $attachMany = [
   
        'public_files' => ['System\Models\File', 'public' => false],
        'private_files' => ['System\Models\File', 'public' => false]

    ];

    protected $jsonable = ['staff'];


    public function __construct(array $attributes = [])
    {
        parent::__construct();
    

    }

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
    
    
    public function addWork()
    {
 
    }            
    public function beforeSave()
    {
   
        //$this->staff = json_encode($this->staff);

        if(Input::has("add_work") && Input::get("add_work_flag")) {
                if(Input::get("add_work") == '') return false;
                $data = ["name" => Input::get("add_work"),"project_id" => $this->id,"status_id" => 1, "start"=> Carbon::now()];
                Work::create($data);
		Flash::success(trans("eprog.manager::lang.add_work"));	
                return false;
        }   

        if(Input::has("print") && Input::get("print")) {
              
		
		return false;
        }   



    	if($this->id){
    		$status_id = Project::find($this->id)->status_id;
    		if(SettingNotify::get('status_notify') && $this->status_id != $status_id) self::changeStatusMail($this->user_id);	
    	}	

        //if(!Input::get("Project.user_id") > 0 && !Input::has("delta"))
        //throw new ValidationException(['my_field'=>trans("eprog.manager::lang.no_user")]);
	    
    }

    public function beforeUpdate()
    {


    }

    public function afterCreate()
    {

       if(SettingNotify::get('status_notify')) self::changeStatusMail(Input::get("id"));

    }
    
    public function getUserIdOptions()
    {

        $id = $this->user_id;
        if(Input::has("id")) $id = Input::get("id");
        $lista = ["-- ".strtolower(trans("eprog.manager::lang.select"))." --"];
        $user = User::find($id);
        if($user) $lista[$user->id] = $user->surname." ".$user->firm_name." (".$user->firm_nip.")";
   
    	if(BackendAuth::getUser()->hasAccess("eprog.manager.manage_project")){
    		$user = User::orderBy("surname")->get();
            	foreach($user as $user){
                		$lista[$user->id] = $user->surname." ".$user->firm_name." (".$user->firm_nip.")";
            	}
    	}

        if(Input::has("user_id") && isset($lista[Input::get("user_id")])) $lista =[Input::get("user_id") => $lista[Input::get("user_id")]];
       
        return  $lista;

    }

    public function getStaffOptions()
    {

        $list = [];
        $user =  \Backend\Models\User::where("is_superuser","=","0")->get();
        foreach($user as $user){

        	if(Util::isGroup($user, "manager"))
        	$list[$user->id] = $user->first_name." ".$user->last_name;
        	
        }

        return $list;
    }

    public function getStatusIdOptions()
    {
        $statues = ProjectController::status();
        array_unshift($statues , "-- ".strtolower(trans("eprog.manager::lang.select"))." --");
        return  $statues;

    }

    public function getStatusAttribute()
    {

        return  $this->status_id > 0 && isset(ProjectController::status()[$this->status_id])  ? ProjectController::status()[$this->status_id] : "";

    }

    public function getFileAttribute()
    {

        $return = "";
        foreach($this->public_files as $file)
            $return .= "<a href='".PublicFiles::getDownloadUrl($file)."'>".$file->file_name."</a><br>";

        return $return;

    }

    public function scopeFilterByUser($query, $filter)
    {

        return $query->whereHas('user', function($group) use ($filter) {
            $group->whereIn('id', $filter);
        });

    }

    public function changeStatusMail($user_id)
    {

    	$user = User::find($user_id);
    	$project = Project::find($this->id);
    	if($user && $project){

            	$data = [

    			"id" => $this->id,
    			"name" => $this->name, 
    			"username" => $user->name, 
                		'status' => self::getStatusAttribute()

            	];
    	
            	SendMail::send('eprog.manager::mail.changestatus', $data, function($message) use ($user) {
                		$message->to($user->email, $user->name);
            	});
    	
    	}
 
    }  
        
}