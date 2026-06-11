<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Finance\DTOs\AccountBalanceResult;

final class AccountBalanceReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var AccountBalanceResult $balance */
        $balance = $this->resource;

        return [
            'account_id' => $balance->accountId,
            'account_code' => $balance->accountCode,
            'account_name' => $balance->accountName,
            'normal_balance' => $balance->normalBalance->value,
            'opening_debit' => $balance->openingDebit,
            'opening_credit' => $balance->openingCredit,
            'period_debit' => $balance->periodDebit,
            'period_credit' => $balance->periodCredit,
            'closing_debit' => $balance->closingDebit,
            'closing_credit' => $balance->closingCredit,
            'balance' => $balance->balance,
        ];
    }
}
