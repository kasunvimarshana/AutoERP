<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use LogicException;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use PHPUnit\Framework\TestCase;

final class ResultTest extends TestCase
{
    public function test_success_and_failure_values_are_accessed_explicitly(): void
    {
        $success = Result::success('done');
        $failure = Result::failure(new Error('FAILED', 'Operation failed.'));

        self::assertSame('done', $success->valueOrFail());
        self::assertSame('FAILED', $failure->errorOrFail()->code);
    }

    public function test_failed_result_does_not_expose_a_value(): void
    {
        $this->expectException(LogicException::class);
        Result::failure(new Error('FAILED', 'Operation failed.'))->valueOrFail();
    }
}
