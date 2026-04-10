<?php

namespace App\Modules\Settings\Models;

use App\Modules\Common\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use UuidTrait;

    protected $fillable = ['key', 'value', 'group', 'is_public'];

    protected $casts = [
        'value' => 'array',
        'is_public' => 'boolean',
    ];
}