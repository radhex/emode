<?php namespace RainLab\User\Models;

use Str;
use Auth;
use Mail;
use Event;
use October\Rain\Auth\Models\User as UserBase;
use RainLab\User\Models\Settings as UserSettings;
use Backend\Controllers\Files;
use Eprog\Manager\Models\Project;
use Eprog\Manager\Models\Work;
use Flash;
use Eprog\Manager\Controllers\PublicFiles;
use ApplicationException;
use Input;
use Eprog\Manager\Classes\Util;
use Backend\Models\Preference;
use Session;

class User extends UserBase
{
    use \October\Rain\Database\Traits\SoftDeleting;
    use \Laravel\Sanctum\HasApiTokens;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'users';

    protected $jsonable = ['staff'];

    /**
     * Validation rules
     */
    public $rules = [
     
        'email'    => 'between:6,100|email|unique:users',
        'username' => 'required|between:2,100|unique:users',
        'password' => 'min:12|regex:/[A-Z]/|regex:/[0-9]/|confirmed',
        'password_confirmation' => 'required_with:password|min:12',
        'name'  =>  'between:2,100',
        'surname'  =>  'between:2,100',
        'firm_nip'    => 'nip',
        'birthday'    => 'birthday',

    ];


    public $customMessages = [

        'email.required' => 'eprog.manager::lang.valid_email',
        'password.required' => 'eprog.manager::lang.valid_password',
        'password.between' => 'eprog.manager::lang.valid_password_between',
        'password.confirmed' => 'eprog.manager::lang.valid_password_confirmed',
        'password_confirmation.between' => 'eprog.manager::lang.valid_password_between',
        'password_confirmation.required_with' => 'eprog.manager::lang.valid_password_confirmed',
        'password.regex' => 'eprog.manager::lang.valid_password_user',
        'name.required' => 'eprog.manager::lang.valid_firstname',
        'surname.required' => 'eprog.manager::lang.valid_lastname',
        'firm_nip.nip' => 'eprog.manager::lang.valid_nip',
      
    ];
    
    
    /**
     * @var array Relations
     */
    public $belongsToMany = [
        'groups' => ['RainLab\User\Models\UserGroup', 'table' => 'users_groups']
    ];
    
    public $hasMany = [

            'project' => ['Eprog\Manager\Models\Project'],
            'mail' => ['Eprog\Manager\Models\Mail']
    ];


    public $attachOne = [
        'avatar' => ['System\Models\File']
    ];

    public $attachMany = [
   
        'public_files' => ['System\Models\File', 'public' => false],
   	    'private_files' => ['System\Models\File', 'public' => false]
    ];


    public static $model = ["id" => "int(10)", "name" => "varchar(255)", "email" => "Indeks", "password" => "varchar(255)", "activation_code" => "Indeks", "persist_code" => "varchar(255)", "reset_password_code" => "Indeks", "permissions" => "mediumtext", "is_activated" => "tinyint(1)", "activated_at" => "timestamp", "last_login" => "timestamp", "created_at" => "timestamp", "updated_at" => "timestamp", "username" => "varchar(255)", "surname" => "varchar(255)", "phone" => "varchar(255)", "country" => "varchar(255)", "region" => "varchar(255)", "city" => "varchar(255)", "code" => "varchar(255)", "street" => "varchar(255)", "number" => "varchar(255)", "firm_name" => "varchar(255)", "firm_nip" => "varchar(255)", "firm_country" => "varchar(255)", "firm_region" => "varchar(255)", "firm_city" => "varchar(255)", "firm_code" => "varchar(255)", "firm_street" => "varchar(255)", "firm_number" => "varchar(255)", "info" => "varchar(255)", "deleted_at" => "timestamp", "last_seen" => "timestamp", "staff" => "mediumtext", "timezone" => "varchar(255)", "is_guest" => "tinyint(1)", "is_superuser" => "tinyint(1)", "is_banned" => "tinyint(1)"];

    public function fileUploadRules()
    {
        return ['avatar' => 'image|max:500', 'public_files' => 'required|maxFiles:10|max:5000', 'private_files' => 'required|maxFiles:50|max:5000'];
    }


    public function getBackavatarAttribute()
    {

         return Files::getThumbUrl($this->avatar,200,200, null);
	  
    }

    public static function getRegionOptions()    {

	     return  Util::getRegions();

    }

    public function getFirmRegionOptions()    {

	     return  Util::getRegions();

    }



    /**
     * @var array The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'surname',
        'login',
        'username',
        'email',
        'password',
        'password_confirmation',
      	'phone',
        'country',
      	'region',
      	'timezone',
      	'city',
      	'code',
      	'street',
      	'number',
        'lump',
      	'firm_name',
      	'firm_nip',
        'firm_country',
      	'firm_region',
      	'firm_city',
      	'firm_code',
      	'firm_street',
      	'firm_number'
	
    ];

    /**
     * Purge attributes from data set.
     */
    protected $purgeable = ['password_confirmation', 'send_invite'];

    protected $dates = [
        'last_seen',
        'deleted_at',
        'created_at',
        'updated_at',
        'activated_at',
        'last_login'
    ];

    public static $loginAttribute = null;

    /**
     * Sends the confirmation email to a user, after activating.
     * @param  string $code
     * @return void
     */
    public function attemptActivation($code)
    {
        $result = parent::attemptActivation($code);
        if ($result === false) {
            return false;
        }

        if ($mailTemplate = UserSettings::get('welcome_template')) {
            Mail::sendTo($this, $mailTemplate, $this->getNotificationVars());
        }

        Event::fire('rainlab.user.activate', [$this]);

        return true;
    }

    /**
     * Converts a guest user to a registered one and sends an invitation notification.
     * @return void
     */
    public function convertToRegistered($sendNotification = true)
    {
        // Already a registered user
        if (!$this->is_guest) {
            return;
        }

        if ($sendNotification) {
            $this->generatePassword();
        }

        $this->is_guest = false;
        $this->save();

        if ($sendNotification) {
            $this->sendInvitation();
        }
    }

    //
    // Constructors
    //

    /**
     * Looks up a user by their email address.
     * @return self
     */
    public static function findByEmail($email)
    {
        if (!$email) {
            return;
        }

        return self::where('email', $email)->first();
    }

    //
    // Getters
    //

    /**
     * Gets a code for when the user is persisted to a cookie or session which identifies the user.
     * @return string
     */
    public function getPersistCode()
    {
        $block = UserSettings::get('block_persistence', false);

        if ($block || !$this->persist_code) {
            return parent::getPersistCode();
        }

        return $this->persist_code;
    }

    /**
     * Returns the public image file path to this user's avatar.
     */
    public function getAvatarThumb($size = 25, $options = null)
    {
        if (is_string($options)) {
            $options = ['default' => $options];
        }
        elseif (!is_array($options)) {
            $options = [];
        }

        // Default is "mm" (Mystery man)
        $default = array_get($options, 'default', 'mm');

        if ($this->avatar) {
            return $this->avatar->getThumb($size, $size, $options);
        }
        else {
            return '//www.gravatar.com/avatar/'.
            md5(strtolower(trim($this->email))).
            '?s='.$size.
            '&d='.urlencode($default);
        }
    }

    /**
     * Returns the name for the user's login.
     * @return string
     */
    public function getLoginName()
    {
        if (static::$loginAttribute !== null) {
            return static::$loginAttribute;
        }

        return static::$loginAttribute = UserSettings::get('login_attribute', UserSettings::LOGIN_EMAIL);
    }

    //
    // Scopes
    //

    public function scopeIsActivated($query)
    {
        return $query->where('is_activated', 1);
    }

    public function scopeFilterByGroup($query, $filter)
    {
        return $query->whereHas('groups', function($group) use ($filter) {
            $group->whereIn('id', $filter);
        });
    }

    //
    // Events
    //

    /**
     * Before validation event
     * @return void
     */
    public function beforeValidate()
    {
        /*
         * Guests are special
         */
        if ($this->is_guest && !$this->password) {
            $this->generatePassword();
        }

        /*
         * When the username is not used, the email is substituted.
         */
/*
        if (
            (!$this->username) ||
            ($this->isDirty('email') && $this->getOriginal('email') == $this->username)
        ) {
            $this->username = $this->email;
        }
*/
    }



    /**
     * After create event
     * @return void
     */
    public function afterCreate()
    {
        $this->restorePurgedValues();
        //$this->attemptActivation($this->activation_code);

        if ($this->send_invite) {
            $this->sendInvitation();
        }
    }

    /**
     * After login event
     * @return void
     */
    public function afterLogin()
    {
        $this->last_login = $this->last_seen = $this->freshTimestamp();

/*
        if ($this->trashed()) {
            $this->restore();

            Mail::sendTo($this, 'rainlab.user::mail.reactivate', [
                'name' => $this->name
            ]);

            Event::fire('rainlab.user.reactivate', [$this]);
        }
        else {
            parent::afterLogin();
        }
*/

	if($this->trashed())
	      Flash::error(trans('rainlab.user::lang.auth.notuser'));

        parent::afterLogin();
        Event::fire('rainlab.user.login', [$this]);
    }

    /**
     * After delete event
     * @return void
     */
    public function afterDelete()
    {
        if ($this->isSoftDelete()) {
            Event::fire('rainlab.user.deactivate', [$this]);
            return;
        }

        $this->avatar && $this->avatar->delete();

        foreach ($this->public_files as $file) {
            $file && $file->delete();
        }

        foreach ($this->private_files as $file) {
            $file && $file->delete();
        }



        parent::afterDelete();
    }

    //
    // Banning
    //

    /**
     * Ban this user, preventing them from signing in.
     * @return void
     */
    public function ban()
    {
        Auth::findThrottleByUserId($this->id)->ban();
    }

    /**
     * Remove the ban on this user.
     * @return void
     */
    public function unban()
    {
        Auth::findThrottleByUserId($this->id)->unban();
    }

    /**
     * Check if the user is banned.
     * @return bool
     */
    public function isBanned()
    {
        $throttle = Auth::createThrottleModel()->where('user_id', $this->id)->first();
        return $throttle ? $throttle->is_banned : false;
    }

    //
    // Last Seen
    //

    /**
     * Checks if the user has been seen in the last 5 minutes, and if not,
     * updates the last_seen timestamp to reflect their online status.
     * @return void
     */
    public function touchLastSeen()
    {
        if ($this->isOnline()) {
            return;
        }

        $oldTimestamps = $this->timestamps;
        $this->timestamps = false;

        $this
            ->newQuery()
            ->where('id', $this->id)
            ->update(['last_seen' => $this->freshTimestamp()])
        ;

        $this->last_seen = $this->freshTimestamp();
        $this->timestamps = $oldTimestamps;
    }

    /**
     * Returns true if the user has been active within the last 5 minutes.
     * @return bool
     */
    public function isOnline()
    {
        return $this->getLastSeen() > $this->freshTimestamp()->subMinutes(5);
    }

    /**
     * Returns the date this user was last seen.
     * @return Carbon\Carbon
     */
    public function getLastSeen()
    {
        return $this->last_seen ?: $this->created_at;
    }

    //
    // Utils
    //

    /**
     * Returns the variables available when sending a user notification.
     * @return array
     */
    protected function getNotificationVars()
    {
        $vars = [
            'name'  => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'login' => $this->getLogin(),
            'password' => $this->getOriginalHashValue('password'),
        ];

        /*
         * Extensibility
         */
        $result = Event::fire('rainlab.user.getNotificationVars', [$this]);
        if ($result && is_array($result)) {
            $vars = call_user_func_array('array_merge', $result) + $vars;
        }

        return $vars;
    }

    /**
     * Sends an invitation to the user using template "rainlab.user::mail.invite".
     * @return void
     */
    protected function sendInvitation()
    {
        Mail::sendTo($this, 'rainlab.user::mail.invite', $this->getNotificationVars());
    }

    /**
     * Assigns this user with a random password.
     * @return void
     */
    protected function generatePassword()
    {
        $this->password = $this->password_confirmation = Str::random(6);
    }

    public function getFileAttribute()
    {


      	$return = "";
      	foreach($this->public_files as $file)
              $return .= "<a href='".PublicFiles::getDownloadUrl($file)."'>".$file->file_name."</a><br>";

      	return $return;

    }

    public function getStaffOptions()    {

        $list = [];
        $user =  \Backend\Models\User::where("is_superuser","=","0")->get();

        foreach($user as $user){

        	$list[$user->id] = $user->first_name." ".$user->last_name;
        	
        }

        return $list;
    }

    public function getTimezoneOptions()    {

        $pre = new Preference();
        return $pre->getTimezoneOptions();
    }

    public function getCountryOptions()
    {

      return Util::getCountries()[Session::get("locale")];

    }

    public function getFirmCountryOptions()
    {

      return Util::getCountries()[Session::get("locale")];

    }

    public function getTaxTypeOptions()
    {

        return [trans('eprog.manager::lang.natural_person'),trans('eprog.manager::lang.firm')];

    }

    public function getTaxOfficeOptions(){

        return Util::getTaxOffice();

    }

    public function getTaxLumpOptions(){

        return Util::getTaxLump();

    }

    public function getTaxFormOptions(){

        return Util::getTaxForm();

    }

}