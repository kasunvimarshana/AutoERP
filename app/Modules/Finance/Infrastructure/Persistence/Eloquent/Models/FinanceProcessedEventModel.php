<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

final class FinanceProcessedEventModel extends FinanceModel
{
    protected $table = 'finance_processed_events';

    protected $casts = [
        'metadata' => 'array',
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];
}
