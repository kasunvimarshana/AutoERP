<?php

declare(strict_types=1);

namespace Modules\User\Tests;

use Modules\User\Database\Seeders\PlatformOperatorSeeder;
use Tests\TestCase;

final class PlatformOperatorSeederConfigurationTest extends TestCase
{
    public function test_platform_operator_seeder_reads_module_configuration(): void
    {
        config()->set('user.seeding.platform_operator.enabled', true);
        config()->set('user.seeding.platform_operator.email', 'platform@example.com');
        config()->set('user.seeding.platform_operator.password', 'correct-horse-battery-staple');

        $seeder = new PlatformOperatorSeeder();

        $enabled = (fn (): bool => $this->enabled())->call($seeder);
        $email = (fn (): string => $this->requiredEmail())->call($seeder);
        $password = (fn (): string => $this->requiredPassword())->call($seeder);

        self::assertTrue($enabled);
        self::assertSame('platform@example.com', $email);
        self::assertSame('correct-horse-battery-staple', $password);
    }
}