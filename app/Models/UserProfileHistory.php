<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserProfileHistory extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable,SoftDeletes;

    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_profile_history';
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 
        'gen_user_id',
        'organization_name', 
        'description',
        'logo_path',
        'website',
        'is_govt',
        'contact_persone_name',
        'contact_email', 
        'contact_mobile_no',
        'crud_type',
        'is_mobile_verified',
        'mobile_otp',
        'status',
        'created_by',
        'updated_by'
    ];

    /* 'created_date',
    'updated_date',*/

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [

    ];
    

}
