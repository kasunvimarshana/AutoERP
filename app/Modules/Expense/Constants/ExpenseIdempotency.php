<?php

declare(strict_types=1);

namespace Modules\Expense\Constants;

final class ExpenseIdempotency
{
    public const CREATE_OPERATION = 'expense.create';

    public const REQUEST_HEADER = 'Idempotency-Key';

    public const REQUEST_ATTRIBUTE = 'idempotency_key';

    public const EXPENSE_ID_KEY = 'expense_id';

    public const MAX_KEY_LENGTH = 255;
}
