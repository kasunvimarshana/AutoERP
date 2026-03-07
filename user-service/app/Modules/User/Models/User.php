<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'keycloak_id', // Maps to the token 'sub'
        'name',
        'email',
        'department',  // Used for ABAC policies
    ];
}
