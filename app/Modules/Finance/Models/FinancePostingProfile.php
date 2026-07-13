<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class FinancePostingProfile extends TenantOwnedModel
{
    protected $table = 'finance_posting_profiles';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::updating(static function (FinancePostingProfile $profile): void {
            if (! $profile->isDirty('row_version')) {
                $profile->row_version = ((int) $profile->getOriginal('row_version')) + 1;
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'row_version' => 'integer',
            'is_active' => 'boolean',
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(FinancePostingProfileRule::class, 'posting_profile_id');
    }

    public function lines(): HasMany
    {
        return $this->rules();
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(FinanceJournalEntry::class, 'posting_profile_id');
    }
}
