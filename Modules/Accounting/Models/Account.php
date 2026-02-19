<?php

declare(strict_types=1);

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Accounting\Enums\AccountStatus;
use Modules\Accounting\Enums\AccountType;
use Modules\Audit\Contracts\Auditable;
use Modules\Audit\Contracts\Auditable as AuditableTrait;
use Modules\Tenant\Contracts\TenantScoped;
use Modules\Tenant\Models\Organization;
use Modules\Tenant\Models\Tenant;

/**
 * Account Model
 *
 * Represents an account in the chart of accounts with hierarchical structure
 */
class Account extends Model
{
    use AuditableTrait, HasFactory, HasUlids, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'parent_id',
        'code',
        'name',
        'description',
        'type',
        'status',
        'normal_balance',
        'is_system',
        'is_bank_account',
        'is_reconcilable',
        'allow_manual_entries',
        'metadata',
    ];

    protected $casts = [
        'type' => AccountType::class,
        'status' => AccountStatus::class,
        'is_system' => 'boolean',
        'is_bank_account' => 'boolean',
        'is_reconcilable' => 'boolean',
        'allow_manual_entries' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Tenant relationship
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Organization relationship
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Parent account relationship
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /**
     * Child accounts relationship
     */
    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    /**
     * Journal lines relationship
     */
    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    /**
     * Check if account is a parent/header account
     */
    public function isParent(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Check if account is a leaf/detail account
     */
    public function isLeaf(): bool
    {
        return ! $this->isParent();
    }

    /**
     * Get all descendants (children, grandchildren, etc.)
     */
    public function descendants(): array
    {
        $descendants = [];

        foreach ($this->children as $child) {
            $descendants[] = $child;
            $descendants = array_merge($descendants, $child->descendants());
        }

        return $descendants;
    }

    /**
     * Get account hierarchy path
     */
    public function hierarchyPath(): string
    {
        if ($this->parent) {
            return $this->parent->hierarchyPath().' > '.$this->name;
        }

        return $this->name;
    }

    /**
     * Get auditable attributes
     */
    public function getAuditableAttributes(): array
    {
        return [
            'code',
            'name',
            'type',
            'status',
            'normal_balance',
        ];
    }
}
