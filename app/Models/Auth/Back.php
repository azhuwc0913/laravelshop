<?php

namespace App\Models\Auth;

use App\Models\Tables\Manager;

class Back extends Manager
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'password', 'last_login_at', 'last_login_ip'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public $timestamps = false;
}
