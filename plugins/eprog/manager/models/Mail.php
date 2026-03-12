<?php namespace Eprog\Manager\Models;

use Model;
use Eprog\Manager\Models\Project;
use Eprog\Manager\Models\Mail as ModelMail;
use October\Rain\Exception\ValidationException;
use Input;
use Flash;
use Eprog\Manager\Controllers\PublicFiles;
use Eprog\Manager\Classes\Util;
use BackendAuth;
use Auth;
use Carbon\Carbon;
use Redirect;
use Rainlab\User\Models\User;
use Mail as SendMail;
use Eprog\Manager\Models\SettingNotify;


/**
 * Model
 */
class Mail extends Model
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
    
    protected $fillable = ['name','desc','user_id','admin_id','send', 'answer','read'];

    public $attributes = [
    	   'send' => 0,
    	   'read' => 0,
    	   'answer' => 0
    ];


    public $table = 'eprog_manager_mail';
  
    public $belongsTo = [
        
            'user' => ['Rainlab\User\Models\User'],
	    'admin' => ['Backend\Models\User', 'scope' => 'admin']	

    ];
    
    public $attachMany = [
        
        'files' => ['System\Models\File', 'public' => false],
   

    ];

    protected $jsonable = ['staff'];


    public function fileUploadRules()
    {
        return ['files' => 'required|maxFiles:10|max:5000'];
    }
    
    public function afterDelete() {
  
        foreach ($this->files as $file) {
            $file && $file->delete();
        }

    }
    public function beforeSave() {
    
        if(!Input::get("Mail.user_id") > 0 && Input::get("from_admin"))
        throw new ValidationException(['my_field'=>trans("eprog.manager::lang.no_user")]);

        if(Input::segment(1) == "backend" && Input::segment(5) != "preview" && (!Input::filled("Mail.user_id") || !Input::filled("Mail.admin_id")))
        throw new ValidationException(['my_field'=>trans("eprog.manager::lang.no_data")]);
	    
    }

    
    public function beforeCreate()
    {

    	if(BackendAuth::getUser() && Input::get("from_admin")) {
    		$this->send = 1;
    		//$this->admin_id = BackendAuth::getUser()->id;
    	}
    	else 
    		$this->admin_id = Input::get("admin");
        
    	$this->date = Carbon::now();

	
    }
    
    public function afterCreate()
    {

    	if(BackendAuth::getUser() && Input::get("from_admin")) {
    			
			$user = User::find($this->user_id);
			if(!$user) return false;
		        $data = [
    				"username" => $user->name,
    				"email" => $user->email,
    				"system" => config("app.name")
    			];

			    if(SettingNotify::get('mail_notify'))
		            SendMail::send('eprog.manager::mail.message', $data, function($message) use ($data) {
				    $message->to($data['email']);
    			});

    	}
    	$this->date = Carbon::now();

        if(Input::segment(1) == "backend")
    	Flash::success(trans("eprog.manager::lang.mail_out"));	
	
    }
    


    public static function getUserIdOptions()
    {
      
        $lista = [];//["-- ".strtolower(trans("eprog.manager::lang.select"))." --"];

        if(Input::has("reply"))
        $reply = ModelMail::find(Input::get("reply"));

	    $user = User::where('staff','like','%"'.BackendAuth::getUser()->id.'"%')->get();
    	foreach($user as $user){

                if(isset($reply)){
                    if($reply->user_id == $user->id)
                        $lista[$user->id] = $user->surname." ".$user->firm_name." (".$user->id.")";
                }
                else{
                   $lista[$user->id] = $user->surname." ".$user->firm_name." (".$user->id.")";
                }

        	
    	}


        if(Input::has("id") && isset($lista[Input::get("id")])) $lista = [Input::get("id") => $lista[Input::get("id")]];
        
        return  $lista;

    }

    public static function getAdminIdOptions()
    {
  
        $lista = [];

    	$admin =  \Backend\Models\User::where("is_superuser","=","0")->get();

    	foreach($admin as $admin){

                if(BackendAuth::getUser()->id == $admin->id)
        		$lista[$admin->id] = $admin->first_name." ".$admin->last_name;            
    	}
	

        if(!BackendAuth::getUser()->hasAccess('eprog.manager.manage_mail'))
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


    public function getAttachmentsAttribute()
    {

    	$return = "";
    	foreach($this->files as $file)
            $return .= "<i class=\"icon-paperclip\"></i> <a href='".PublicFiles::getDownloadUrl($file)."'>".$file->file_name."</a>&nbsp;&nbsp;&nbsp";

    	return $return;

    }

    public function getAdminattachmentsAttribute()    {

    	$return = "";
    	foreach($this->files as $file)
            $return .= "<i class=\"oc-icon-paperclip\"></i><a href='".PublicFiles::getAdminDownloadUrl($file)."'>".$file->file_name."</a>&nbsp;&nbsp;&nbsp";

    	return $return;

    }

    public function scopeFilterByUser($query, $filter)
    {

        return $query->whereHas('user', function($group) use ($filter) {
            $group->whereIn('id', $filter);
        });
	
    }

    public function scopeFilterByAdmin($query, $filter)
    {

        return $query->whereHas('admin', function($group) use ($filter) {
            $group->whereIn('id', $filter);
        });
	
    }
        
}