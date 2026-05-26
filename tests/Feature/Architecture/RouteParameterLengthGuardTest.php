<?php

declare(strict_types=1);

namespace Tests\Feature\Architecture;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class RouteParameterLengthGuardTest extends TestCase
{
    public function testAllRouteParameterNamesAreSymfonyCompatible(): void
    {
        $violations = [];

        foreach (Route::getRoutes() as $route) {
            $parameterNames = $route->parameterNames();
            if ($parameterNames === null) {
                continue;
            }

            foreach ($parameterNames as $parameterName) {
                if (strlen($parameterName) > 32) {
                    $violations[] = sprintf(
                        '%s (%s) has parameter "%s" length %d',
                        $route->uri(),
                        implode('|', $route->methods()),
                        $parameterName,
                        strlen($parameterName),
                    );
                }
            }
        }

        self::assertSame(
            [],
            $violations,
            "Found route parameters longer than 32 characters:\n" . implode("\n", $violations),
        );
    }
}
