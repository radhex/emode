<?php namespace Eprog\Manager\Models;

use Model;
use BackendAuth;
use Eprog\Manager\Controllers\PublicFiles;

/**
 * Model
 */
class Scheduler extends Model
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

    protected $fillable = [
        'name',
        'desc',
        'start',
        'stop',
        'user_id',
        'admin_id',
        'type_id'
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'eprog_manager_scheduler';

    public $belongsTo = [

        'type' => ['Eprog\Manager\Models\Type'],
        'admin' => ['Backend\Models\User'],
	    'user' => ['Rainlab\User\Models\User']
    ];

    public $attachMany = [
   
        'public_files' => ['System\Models\File', 'public' => false],
        'private_files' => ['System\Models\File', 'public' => false]

    ];


    public function fileUploadRules()
    {

        return ['public_files' => 'required|maxFiles:20|max:5000', 'private_files' => 'required|maxFiles:20|max:5000'];

    }


    public function afterDelete() {
  
        foreach ($this->public_files as $file) {
            $file && $file->delete();
        }

        foreach ($this->private_files as $file) {
            $file && $file->delete();
        }

    }

    public function getFileAttribute(){


    	$return = "";
    	foreach($this->public_files as $file)
            $return .= "<a href='".PublicFiles::getDownloadUrl($file)."'>".$file->file_name."</a><br>";

    	return $return;

    }

    public static function getUserIdOptions()
    {

        $lista = ['0'=> '-- '.strtolower(trans("eprog.manager::lang.select")).' --'];

		$user = \Rainlab\User\Models\User::orderBy("surname")->get();
        	foreach($user as $user){
            		$lista[$user->id] = $user->surname." ".$user->firm_name." (".$user->firm_nip.")";
        	}

        return  $lista;

    }

    public static function getAdminIdOptions()
    {


        $lista = [];

    	$admin =  \Backend\Models\User::where("is_superuser","=","0")->get();
    	foreach($admin as $admin){

        		$lista[$admin->id] = $admin->first_name." ".$admin->last_name;
    	}

        if(!BackendAuth::getUser()->hasAccess("eprog.manager.manage_scheduler")){

    		$lista = [];	
    		$lista[BackendAuth::getUser()->id] = BackendAuth::getUser()->first_name." ".BackendAuth::getUser()->last_name;

	    }

        return  $lista;

    }

    public static function getTypeIdOptions()
    {

        $lista = ['0'=> '-- '.strtolower(trans("eprog.manager::lang.select")).' --'];

        	$type =  Type::orderBy("name")->get();
        	foreach($type as $type){

            		$lista[$type->id] = $type->name;
        	}
        
        return  $lista;

    }

    public function scopeFilterByUser($query, $filter)
    {

        return $query->whereHas('user', function($group) use ($filter) {
            $group->whereIn('id', $filter);
        });
	
    }

    public function scopeFilterByType($query, $filter)
    {

        return $query->whereHas('type', function($group) use ($filter) {
            $group->whereIn('id', $filter);
        });
	
    }

}