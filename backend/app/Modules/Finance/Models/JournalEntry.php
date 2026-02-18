<?php

namespace App\Modules\Finance\Models;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\Finance\Enums\JournalEntryStatus;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected static function newFactory()
    {
        return \Database\Factories\Finance\JournalEntryFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'entry_number',
        'entry_date',
        'reference_type',
        'reference_id',
        'description',
        'status',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'status' => JournalEntryStatus::class,
        'posted_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function scopeByStatus($query, JournalEntryStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', JournalEntryStatus::DRAFT);
    }

    public function scopePosted($query)
    {
        return $query->where('status', JournalEntryStatus::POSTED);
    }

    public function scopeVoid($query)
    {
        return $query->where('status', JournalEntryStatus::VOID);
    }

    public function getTotalDebitAttribute(): float
    {
        return $this->lines()->sum('debit');
    }

    public function getTotalCreditAttribute(): float
    {
        return $this->lines()->sum('credit');
    }

    public function isBalanced(): bool
    {
        return bccomp($this->total_debit, $this->total_credit, 2) === 0;
    }
}
