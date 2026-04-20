<?php

namespace App\Modules\Core\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model {
    use SoftDeletes;

    protected $fillable = ['name', 'slug', 'domain', 'settings', 'status'];
    protected $casts = ['settings' => 'array'];
}
