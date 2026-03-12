<?php namespace Eprog\Manager\Models;

use Model;
use RainLab\User\Models\User;
use RainLab\User\Models\UserGroup;
use RainLab\User\Models\UserGroups;
use Input;
use ApplicationException;
use Flash;
use Mail as SendMail;
use Carbon\Carbon;
use Redirect;
use Webklex\IMAP\Facades\Client;


/**
 * Model
 */
class Mailing extends Model
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
    

    protected $fillable = ['groups', 'type', 'name', 'desc', 'list', 'send'];

    public $table = 'eprog_manager_mailing';


    public $attachMany = [
        
        'files' => ['System\Models\File', 'public' => true],
   

    ];

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

        $this->type = Input::get("type");

       	if(Input::has("send_clear"))
            $this->send = "";
	
        if(!Input::has("Mail.groups") && Input::get("from_admin"))
            throw new ValidationException(['my_field'=>trans("eprog.manager::lang.no_group")]);
	    
    }

    public static function getGroupsOptions()
    {
  
        $lista = [];
 
    	$groups = UserGroup::all();
    	foreach($groups as $group){
        		$lista[$group->id] = $group->name;
    	}
	
       //if(Input::has("id") && isset($lista[Input::get("id")])) $lista = [Input::get("id") => $lista[Input::get("id")]];
        
        return  $lista;

    }

    public function getAttachmentsAttribute()
    {

    	$return = "";
    	foreach($this->files as $file)
            $return .= "<a href='".PublicFiles::getDownloadUrl($file)."'>".$file->file_name."</a><br>";

    	return $return;

    }
    
    public function scopeFilterByProject($query, $filter)
    {

        return $query->whereHas('project', function($group) use ($filter) {

            $group->whereIn('id', $filter);
            
        });
	
    }


}