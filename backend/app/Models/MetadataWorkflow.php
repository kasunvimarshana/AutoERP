<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetadataWorkflow extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'entity_id',
        'name',
        'label',
        'description',
        'is_active',
        'states',
        'transitions',
        'config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'states' => 'array',
        'transitions' => 'array',
        'config' => 'array',
    ];

    /**
     * Get the tenant that owns the workflow.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the entity that owns the workflow.
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(MetadataEntity::class, 'entity_id');
    }

    /**
     * Get available states.
     */
    public function getStates(): array
    {
        return $this->states ?? [];
    }

    /**
     * Get available transitions from a state.
     */
    public function getTransitionsFrom(string $state): array
    {
        return collect($this->transitions ?? [])
            ->filter(fn ($t) => $t['from'] === $state)
            ->values()
            ->toArray();
    }

    /**
     * Check if transition is allowed.
     */
    public function canTransition(string $from, string $to): bool
    {
        return collect($this->transitions ?? [])
            ->contains(fn ($t) => $t['from'] === $from && $t['to'] === $to);
    }

    /**
     * Get initial state.
     */
    public function getInitialState(): ?array
    {
        return collect($this->states ?? [])
            ->firstWhere('type', 'initial');
    }

    /**
     * Scope to active workflows.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
