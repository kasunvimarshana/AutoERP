<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final class PaymentTermsResolver
{
    public function resolve(string $documentDate, ?string $paymentTerms, mixed $explicitDueDate = null): string
    {
        $date = CarbonImmutable::parse($documentDate)->startOfDay();
        $terms = trim((string) $paymentTerms);
        $explicit = trim((string) ($explicitDueDate ?? ''));

        if ($explicit !== '') {
            $due = CarbonImmutable::parse($explicit)->startOfDay();
            if ($due->lt($date)) {
                throw ValidationException::withMessages([
                    'due_date' => ['Due date cannot be earlier than the purchase date.'],
                ]);
            }

            if ($terms !== '' && $terms !== 'explicit') {
                $termDue = $this->dueDateForTerms($date, $terms);
                if (! $due->equalTo($termDue)) {
                    throw ValidationException::withMessages([
                        'due_date' => ['Explicit due date conflicts with the selected payment terms.'],
                    ]);
                }
            }

            return $due->toDateString();
        }

        return $this->dueDateForTerms($date, $terms)->toDateString();
    }

    private function dueDateForTerms(CarbonImmutable $date, string $terms): CarbonImmutable
    {
        return match ($terms) {
            '', 'due_on_receipt', 'cod', 'cash' => $date,
            'net_7' => $date->addDays(7),
            'net_15' => $date->addDays(15),
            'net_30' => $date->addDays(30),
            default => $date,
        };
    }
}
