<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class FinancePostingProfile extends TenantOwnedModel
{
    protected $table = 'finance_posting_profiles';

    protected $guarded = ['id', 'tenant_id', 'row_version', 'scope_key'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'is_active' => 'boolean',
        ]);
    }

    protected static function booted(): void
    {
        static::deleting(static function (): never {
            throw new LogicException('Finance posting profiles cannot be deleted. Deactivate the profile instead.');
        });
    }

    public function tenant(): BelongsTo { return $this->belongsTo(TenantModel::class, 'tenant_id'); }
    public function organizationUnit(): BelongsTo { return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id'); }
    public function rules(): HasMany { return $this->hasMany(FinancePostingProfileRule::class, 'posting_profile_id'); }
    public function lines(): HasMany { return $this->rules(); }
    public function journalEntries(): HasMany { return $this->hasMany(FinanceJournalEntry::class, 'posting_profile_id'); }
}
