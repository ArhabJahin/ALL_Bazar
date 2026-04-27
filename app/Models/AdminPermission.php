<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminPermission extends Model
{
    protected $fillable = ['user_id', 'permission', 'allowed'];

    protected $casts = ['allowed' => 'boolean'];
}
