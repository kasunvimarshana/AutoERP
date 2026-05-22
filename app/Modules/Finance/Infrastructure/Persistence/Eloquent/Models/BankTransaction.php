<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganizationScopes;

class BankTransaction extends Model
{
    use HasTenantAndOrganizationScopes;

    protected $table = 'bank_transactions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'transaction_date' => 'date',
            'value_date' => 'date',
            'amount' => 'decimal:4',
            'balance' => 'decimal:4',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo('Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\Tenant', 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo('Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnit', 'organization_unit_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function matchedJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'matched_journal_entry_id');
    }

    public function categoryRule(): BelongsTo
    {
        return $this->belongsTo(BankCategoryRule::class, 'category_rule_id');
    }
}
