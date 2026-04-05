<?php

declare(strict_types=1);

namespace Modules\Accounting\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class CreateJournalEntryData extends BaseDto
{
    public int $tenant_id;
    public string $reference;
    public ?string $description = null;
    public string $transaction_date;
    public array $lines = [];  // [['account_id'=>int,'debit'=>float,'credit'=>float,'description'=>?string]]
}
