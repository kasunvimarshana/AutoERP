<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;

final class FinancePostingProfileRule extends TenantOwnedModel
{
    public const OPENING_EFFECTIVE_DATE = '1900-01-01';

    protected $table = 'finance_posting_profile_rules';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'posting_profile_id' => 'integer',
            'account_role_id' => 'integer',
            'effective_from' => 'date:Y-m-d',
            'effective_to' => 'date:Y-m-d',
            'is_active' => 'boolean',
        ]);
    }

    public function postingProfile(): BelongsTo
    {
        return $this->belongsTo(FinancePostingProfile::class, 'posting_profile_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(FinanceAccountRole::class, 'account_role_id');
    }
}
