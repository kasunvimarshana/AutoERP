<?php

declare(strict_types=1);

namespace Modules\Audit\Tests;

use Modules\Audit\Services\AuditQueryFactory;
use Tests\TestCase;

final class AuditQueryFactoryTest extends TestCase
{
    public function test_to_date_is_converted_to_an_exclusive_next_day_boundary(): void
    {
        config()->set('audit.display_timezone', 'Asia/Colombo');

        $query = (new AuditQueryFactory())->fromValidated([
            'from_date' => '2026-06-22',
            'to_date' => '2026-06-22',
            'per_page' => 999,
        ]);

        self::assertSame('2026-06-21 18:30:00', $query->fromUtc?->format('Y-m-d H:i:s'));
        self::assertSame('2026-06-22 18:30:00', $query->toUtcExclusive?->format('Y-m-d H:i:s'));
        self::assertSame(100, $query->perPage);
    }
}
