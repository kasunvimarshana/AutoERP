<?php

declare(strict_types=1);

namespace Modules\Purchase\Services\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

trait AssertsPurchaseExpectedVersion
{
    private function assertExpectedVersion(Model $document, ?int $expectedVersion): void
    {
        if ($expectedVersion === null) {
            return;
        }

        if ((int) $document->getAttribute('row_version') === $expectedVersion) {
            return;
        }

        throw ValidationException::withMessages([
            'expected_version' => ['Purchase document was changed by another request. Reload it before continuing.'],
        ]);
    }
}
