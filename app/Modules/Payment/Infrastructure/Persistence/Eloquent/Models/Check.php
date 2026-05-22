<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Payment\Domain\Enums\CheckStatus;
use Modules\Payment\Domain\Enums\CheckType;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class Check extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'checks';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'type' => CheckType::class,
            'check_date' => 'date',
            'due_date' => 'date',
            'amount' => 'decimal:4',
            'status' => CheckStatus::class,
            'clearance_date' => 'date',
        ];
    }

    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', CheckStatus::Pending->value);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\BankAccount',
            'bank_account_id'
        );
    }
}
