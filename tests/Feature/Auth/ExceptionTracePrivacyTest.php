<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Tests\TestCase;

final class ExceptionTracePrivacyTest extends TestCase
{
    public function test_exception_stack_traces_hide_function_arguments(): void
    {
        self::assertSame('1', ini_get('zend.exception_ignore_args'));
    }
}
