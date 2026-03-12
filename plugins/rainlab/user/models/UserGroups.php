<?php namespace RainLab\User\Models;

use October\Rain\Auth\Models\Group as GroupBase;
use ApplicationException;

/**
 * User Group Model
 */
class UserGroups extends GroupBase
{

    /**
     * @var string The database table used by the model.
     */
    protected $table = 'users_groups';


}
