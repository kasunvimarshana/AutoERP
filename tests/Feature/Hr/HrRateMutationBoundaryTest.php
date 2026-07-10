<?php

declare(strict_types=1);

namespace Tests\Feature\Hr;

use Modules\Hr\Services\EmployeeRateService;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

final class HrRateMutationBoundaryTest extends TestCase
{
    public function test_employee_rate_service_exposes_create_only_mutation_surface(): void
    {
        $methods = array_map(
            static fn (ReflectionMethod $method): string => $method->getName(),
            (new ReflectionClass(EmployeeRateService::class))->getMethods(ReflectionMethod::IS_PUBLIC),
        );

        self::assertContains('create', $methods);
        self::assertContains('activeRate', $methods);
        self::assertNotContains('update', $methods);
        self::assertNotContains('delete', $methods);
        self::assertNotContains('replace', $methods);
    }
}
