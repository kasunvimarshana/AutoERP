<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Webhook extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'url',
        'secret',
        'events',
        'status',
        'description',
        'last_response_code',
        'last_triggered_at',
        'failure_count',
        'metadata',
    ];

    protected $casts = [
        'events'            => 'array',
        'metadata'          => 'array',
        'last_triggered_at' => 'datetime',
        'failure_count'     => 'integer',
    ];

    protected $hidden = ['secret'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function subscribesTo(string $event): bool
    {
        return in_array($event, $this->events ?? []) || in_array('*', $this->events ?? []);
    }

    public function recordSuccess(int $statusCode): void
    {
        $this->update([
            'last_response_code' => $statusCode,
            'last_triggered_at'  => now(),
            'failure_count'      => 0,
        ]);
    }

    public function recordFailure(int $statusCode): void
    {
        $this->increment('failure_count');
        $this->update([
            'last_response_code' => $statusCode,
            'last_triggered_at'  => now(),
        ]);

        // Auto-disable after too many failures
        if ($this->failure_count >= config('webhook.max_failures', 10)) {
            $this->update(['status' => 'disabled']);
        }
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForEvent($query, string $event)
    {
        return $query->where(function ($q) use ($event) {
            $q->whereJsonContains('events', $event)
              ->orWhereJsonContains('events', '*');
        });
    }
}
