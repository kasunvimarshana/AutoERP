<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Console\Command;
use ReflectionClass;
use Tests\TestCase;

final class ArtisanBootstrapSafetyTest extends TestCase
{
    public function test_module_commands_do_not_resolve_runtime_services_in_constructors(): void
    {
        $commandFiles = glob(app_path('Modules/*/Console/Commands/*.php')) ?: [];

        self::assertNotSame([], $commandFiles, 'No module Artisan commands were discovered.');

        foreach ($commandFiles as $commandFile) {
            $relativePath = substr($commandFile, strlen(app_path()) + 1, -4);
            $commandClass = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

            self::assertTrue(class_exists($commandClass), sprintf('Command class %s could not be loaded.', $commandClass));
            self::assertTrue(
                is_subclass_of($commandClass, Command::class),
                sprintf('%s must extend %s.', $commandClass, Command::class),
            );

            $constructor = (new ReflectionClass($commandClass))->getConstructor();
            if ($constructor === null || $constructor->getDeclaringClass()->getName() === Command::class) {
                continue;
            }

            self::assertSame(
                0,
                $constructor->getNumberOfRequiredParameters(),
                sprintf(
                    '%s must resolve runtime dependencies in handle(); Artisan constructs registered commands during bootstrap.',
                    $commandClass,
                ),
            );
        }
    }
}
