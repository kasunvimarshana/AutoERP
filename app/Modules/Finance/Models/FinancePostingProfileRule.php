<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;

final class FinancePostingProfileRule extends TenantOwnedModel
{
    protected $table = 'finance_posting_profile_rules';

    protected $guarded = ['id', 'tenant_id', 'row_version'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'posting_profile_id' => 'integer',
            'account_role_id' => 'integer',
        ]);
    }

    public function postingProfile(): BelongsTo { return $this->belongsTo(FinancePostingProfile::class, 'posting_profile_id'); }
    public function accountRole(): BelongsTo { return $this->belongsTo(FinanceAccountRole::class, 'account_role_id'); }
}
