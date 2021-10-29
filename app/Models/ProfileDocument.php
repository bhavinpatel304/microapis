<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Storage;
class ProfileDocument extends Model 
{
    use Authenticatable, Authorizable,SoftDeletes;

    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'profile_document';
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 
        'doc_id',
        'doc_name', 
        'doc_number',
        'doc_path',
        'doc_size',
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
    
    public function documentType()
    {
        return $this->belongsTO('App\Models\DocMaster','doc_id')->orderBy('type','ASC');
    }

    public function getDocPathAttribute($path){
        return $path ? env('FILE_BASE_PATH').Storage::url('app/'.$path) : '';
    }
}
