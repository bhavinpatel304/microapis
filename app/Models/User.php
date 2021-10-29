<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use App\Traits\MustVerifyEmail;
class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable,SoftDeletes,Notifiable,MustVerifyEmail;
    //const CREATED_AT = 'created_date';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'user_details';
    protected $guarded = ['id'];
/*    protected $fillable = [
        'name', 'email','contact_no','user_type_id','email_verified_at','status',
    ];
*/

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /*protected static function boot()
    {
        parent::boot();
        static::saved(function ($model) {
            // If user email have changed email verification is required
            
            if( $model->isDirty('email') ) {
                $model->setAttribute('email_verified_at', null);
                $model->sendEmailVerificationNotification();
            }
        });
    }*/

    public function getFullNameAttribute() {
        return $this->fname.' '.$this->lname;
    }
    
    public function type()
    {
        return $this->belongsTo('App\Models\UserType','user_type_id');
    }

    public function profile(){
        return $this->hasOne('App\Models\UserProfile','user_id');
    }


    public function profile_document(){
        return $this->hasMany('App\Models\ProfileDocument','user_id');
    }

    public function profile_address(){
        return $this->hasMany('App\Models\ProfileAddress','user_id');
    }
    
    public function membership(){
        return $this->hasOne('App\Models\UserMembership');
    }
}
