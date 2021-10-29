<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfileAddress extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'profile_address';

    protected $primaryKey = 'id';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */


    /* 'created_date',
    'updated_date',*/


    protected $fillable = [        
        'user_id', 
        'type',
        'address', 
        'pincode', 
        'state_id', 
        'city_id', 
        'latitude', 
        'logitude',
        'created_at',
        'created_by',
        'updated_at', 
        'updated_by'
    ];

    

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
