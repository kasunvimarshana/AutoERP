<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    use HasUlids;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'idempotency_key',
        'request_method',
        'request_path',
        'response_status',
        'response_body',
        'processed_at',
        'expires_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function isProcessed(): bool
    {
        return $this->processed_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
