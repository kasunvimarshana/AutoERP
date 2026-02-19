<?php

namespace App\Models;

use App\Enums\FileDisk;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MediaFile extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'organization_id', 'category_id', 'user_id',
        'attachable_type', 'attachable_id', 'disk', 'path', 'filename',
        'original_filename', 'mime_type', 'size', 'checksum', 'is_public', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'disk' => FileDisk::class,
            'size' => 'integer',
            'is_public' => 'bool',
            'metadata' => 'array',
        ];
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk->value)->url($this->path);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FileCategory::class, 'category_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
