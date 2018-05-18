<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable, Eloquence, Mappable;

    protected $table = 'lit_ac_user_profile';
    protected $primaryKey = 'u_id';
    protected $maps = [
        'id' => 'u_id',
        'email' => 'up_email',
        'phone' => 'up_phone',
        'password' => 'up_password',
        'last_user_agent' => 'up_lastuseragent',
        'fb_id' => 'up_fb_id',
        'fb_token' => 'up_fb_token',
        'status' => 'up_status'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'up_password',
    ];

    const GROUPID_GUEST = 0;
    const GROUPID_ADMIN = 1;
    const GROUPID_MEMBER = 20;

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
