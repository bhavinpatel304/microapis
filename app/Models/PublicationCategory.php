<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\SoftDeletes;

class PublicationCategory extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'publication_category';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'category_id', 
        'publication_id',
        'created_by',
        'created_at',
        'updated_at',
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
