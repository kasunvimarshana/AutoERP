<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Modules\Core\DTOs\DataRecord;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

final class DataRecordTest extends TestCase
{
    public function test_get_distinguishes_an_explicit_null_from_a_missing_key(): void
    {
        $record = new DataRecord(['id' => 1, 'nullable_value' => null]);

        self::assertTrue($record->has('nullable_value'));
        self::assertNull($record->get('nullable_value', 'fallback'));
        self::assertSame('fallback', $record->get('missing', 'fallback'));
    }

    public function test_require_fails_for_a_missing_field(): void
    {
        $this->expectException(OutOfBoundsException::class);

        (new DataRecord(['id' => 1]))->require('missing');
    }
}
