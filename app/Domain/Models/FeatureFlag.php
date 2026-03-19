<?php

declare(strict_types=1);

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FeatureFlag extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'feature_flags';

    protected $fillable = [
        'tenant_id',
        'name',
        'is_enabled',
        'rollout_percentage',
        'conditions',
        'description',
    ];

    protected $casts = [
        'is_enabled'         => 'boolean',
        'rollout_percentage' => 'integer',
        'conditions'         => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Determine if this flag is enabled for the given context.
     *
     * @param array<string, mixed> $context
     */
    public function isEnabledForContext(array $context = []): bool
    {
        if (!$this->is_enabled) {
            return false;
        }

        if ($this->rollout_percentage < 100) {
            $seed = crc32(($context['user_id'] ?? '') . $this->name);
            $bucket = abs($seed) % 100;
            if ($bucket >= $this->rollout_percentage) {
                return false;
            }
        }

        return true;
    }
}
