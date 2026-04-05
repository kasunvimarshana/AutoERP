<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class BankTransactionModel extends BaseModel
{
    protected $table = 'bank_transactions';
    protected $fillable = [
        'tenant_id', 'bank_account_id', 'transaction_date', 'amount',
        'description', 'type', 'status', 'expense_category_id', 'account_id',
        'journal_entry_id', 'reference', 'source', 'metadata',
    ];
    protected $casts = [
        'id'                  => 'int',
        'tenant_id'           => 'int',
        'bank_account_id'     => 'int',
        'amount'              => 'float',
        'expense_category_id' => 'int',
        'account_id'          => 'int',
        'journal_entry_id'    => 'int',
        'transaction_date'    => 'date',
        'metadata'            => 'array',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
        'deleted_at'          => 'datetime',
    ];
}
