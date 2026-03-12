<?php namespace Eprog\Manager\Models;

use Model;
use Eprog\Manager\Models\Project;
use Eprog\Manager\Models\Inmail;
use October\Rain\Exception\ValidationException;
use Input;
use Flash;
use Eprog\Manager\Controllers\PublicFiles;
use Eprog\Manager\Classes\Util;
use BackendAuth;
use Carbon\Carbon;
use Redirect;
use Rainlab\User\Models\User;
use Mail as SendMail;

/**
 * Model
 */
class Inmail extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Validation
     */    
    public $rules = [

        'name'    => 'required',
        'desc'    => 'required'
    ];

    public $customMessages = [

        'name.required' => 'eprog.manager::lang.valid_title',
        'desc.required' => 'eprog.manager::lang.valid_body'
      
    ];
    

    /**
     * @var string The database table used by the model.
     */
    
    protected $fillable = ['name','desc','sender_id','rceiver_id','send', 'answer','read'];

    public $attributes = [
    	   'send' => 0,
    	   'read' => 0,
    	   'answer' => 0
    ];

    public $table = 'eprog_manager_inmail';

    public $belongsTo = [
        
            'receiver' => ['Backend\Models\User', 'id' => 'receiver_id', 'scope'=> 'admin'],
	    'sender' => ['Backend\Models\User', 'id' => 'sender_id' , 'scope'=> 'admin']	

    ];
    
    public $attachMany = [
        
        'files' => ['System\Models\File', 'public' => false],
   

    ];

    protected $jsonable = ['staff'];


    public function fileUploadRules()
    {

        return ['files' => 'required|maxFiles:10|max:5000'];
        
    }

    
    public function afterDelete()
    {
  
        foreach ($this->files as $file) {

            $file && $file->delete();

        }

    }
    public function beforeSave()
    {
    
        if(!Input::has("Inmail.receiver_id") && Input::get("from_admin"))
            throw new ValidationException(['my_field'=>trans("eprog.manager::lang.no_data")]);


        if(Input::has("Inmail.receiver_id") && Input::get("Inmail.sender_id")  && Input::get("Inmail.receiver_id") == Input::get("Inmail.sender_id"))
            throw new ValidationException(['my_field'=>trans("eprog.manager::lang.no_self")]);

        if(Input::segment(1) == "backend" && Input::segment(5) != "preview" && (!Input::filled("Inmail.receiver_id") || !Input::filled("Inmail.sender_id")))
            throw new ValidationException(['my_field'=>trans("eprog.manager::lang.no_data")]);

	    
    }

    
    public function beforeCreate()
    {

        if(BackendAuth::getUser() && Input::get("from_admin")){
    		//$this->send = 1;
    	}
    	
    	$this->date = Carbon::now();
	
    }
    

    public function afterCreate()
    {

    	$this->date = Carbon::now();
    	Flash::success(trans("eprog.manager::lang.mail_out"));	
	
    }
    

    public static function getReceiverIdOptions()
    {

        $is = Util::isGroup(BackendAuth::getUser(),"worker");
        $lista = [];


        if(Input::has("reply"))
        $reply = Inmail::find(Input::get("reply"));  
    	$admin =  \Backend\Models\User::where("is_superuser","=","0")->get();

    	foreach($admin as $admin){
                
                if(isset($reply)){
                    if($reply->sender_id == $admin->id)
        		        $lista[$admin->id] = $admin->first_name." ".$admin->last_name;
                }
                else{
                    if(BackendAuth::getUser()->id != $admin->id)
                        $lista[$admin->id] = $admin->first_name." ".$admin->last_name;
                }
    	}

        
        return  $lista;

    }

    public static function getSenderIdOptions()
    {
   
        $lista = [];
  
    	$admin =  \Backend\Models\User::where("is_superuser","=","0")->get();

    	foreach($admin as $admin){

                if(BackendAuth::getUser()->id == $admin->id)
        		$lista[$admin->id] = $admin->first_name." ".$admin->last_name;
    	}
	

  	    if(Input::segment(5) != "preview" && !BackendAuth::getUser()->hasAccess('eprog.manager.manage_mail'))
        $lista = [BackendAuth::getUser()->id => BackendAuth::getUser()->first_name." ".BackendAuth::getUser()->last_name];
        
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


    public function getAttachmentsAttribute(){

    	$return = "";
    	foreach($this->files as $file)
            $return .= "<a href='".PublicFiles::getDownloadUrl($file)."'>".$file->file_name."</a><br>";

    	return $return;

    }

    public function getAdminattachmentsAttribute(){

    	$return = "";
    	foreach($this->files as $file)
            $return .= "<i class=\"oc-icon-paperclip\"></i><a href='".PublicFiles::getAdminDownloadUrl($file)."'>".$file->file_name."</a>&nbsp;&nbsp;&nbsp";

    	return $return;

    }

    public function scopeFilterByProject($query, $filter)
    {

        return $query->whereHas('project', function($group) use ($filter) {
            $group->whereIn('id', $filter);
        });
	
    }
        
}