<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyType extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'company_type';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */


    /* 'created_date',
    'updated_date',*/


    protected $fillable = [   
        'type', 
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
