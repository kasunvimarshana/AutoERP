<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FileCategory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id', 'name', 'slug', 'description',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(MediaFile::class, 'category_id');
    }
}
