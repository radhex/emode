<?php namespace Eprog\Manager\Models;

use Rainlab\User\Models\User;
use Model;
use DateTimeZone;
use Input;

/**
 * Model
 */
class Admin extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Validation
     */
    public $rules = [
        'server'    => 'required',
        'mode'    => 'required',
        'remote'    => 'required',

 
    ];

    public $customMessages = [
        'name.required' => 'eprog.manager::lang.valid_name'
    ];

    /**
     * @var string The database table used by the model.
     */

    public $belongsTo = [
            'user' => ['RainLab\User\Models\User']
    ];

    protected $jsonable = ['mode'];
    
    public $table = 'eprog_manager_admin';


    public function afterSave()
    {

        $hosts = $this->where("disp",1)->get();

        $data = [];

        $l = 0;
        foreach($hosts as $host){
            $data[$l]["user_id"] = $host->user_id ?? "";  
            $data[$l]["subscription"] = $host->subscription; 
            $data[$l]["capacity"] = $host->capacity;   
            $data[$l]["server_name"] = $host->server;  
            $data[$l]["remote_addr"] = $host->remote;    
            $data[$l]["mode"] = $host->mode;    
            $data[$l]["remote_key"] = $host->key;    
            $data[$l]["expires_at"] = (new \DateTimeImmutable($host->expires))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
            $data[$l]["rotation_id"] = $host->rotation;    
            $l++;
        }

        $content = "<?php\n \$hosts = " . var_export($data, true) . ";\n";

        file_put_contents("/home/host979012/domains/emode.pl/public_html/service/hosts.php",$content);



    }

    public function scopeFilterByUser($query, $filter)
    {

        return $query->whereHas('user', function($group) use ($filter) {
            $group->whereIn('id', $filter);
        });

    }
    
    public function getUserIdOptions()
    {

        $lista = [];
        $lista[0] = "-- ".strtolower(trans("eprog.manager::lang.select"))." --";
        $user = User::orderBy("surname")->get();
            foreach($user as $user){
                    $lista[$user->id] = $user->surname." ".$user->firm_name." (".$user->firm_nip.")";
            }

        if(Input::has("user_id")) {

            if(isset($lista[Input::get("user_id")]))
            return [Input::get("user_id") => $lista[Input::get("user_id")]];
            
        }


        if(Input::has("accounting")) {

            $accounting = Accounting::find(Input::get("accounting"));
            return [$accounting->user_id => $lista[$accounting->user_id]];
            
        }

        return  $lista;

    }

    public function getSubscriptionOptions()
    {


        return ["1" => "license", "2" => "month", "3" => "year"];

    }

    public function getModeOptions()
    {


        return ["1" => "Em1","2" => "Em2", "2a" => "Em2a", "3" => "Em3", "4" => "Em4", "5" => "Em5"];

    }


    
}