<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Storage;

class UserProfile extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_profile';
    protected $primaryKey = 'user_id';
    public $incrementing = false;

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
    
    public function getLogoPathAttribute($path){
        return $path ? env('FILE_BASE_PATH').Storage::url('app/'.$path) : env('FILE_BASE_PATH').Storage::url('app/uploads/logo/user-icon.png');
    }

}
