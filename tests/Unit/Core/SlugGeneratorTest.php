<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Modules\Core\Infrastructure\Services\SlugGenerator;
use Tests\TestCase;

class SlugGeneratorTest extends TestCase
{
    public function test_it_prefers_explicit_slug_value(): void
    {
        $generator = new SlugGenerator();

        $result = $generator->generate('  My Custom Slug  ', 'Ignored Name');

        $this->assertSame('my-custom-slug', $result);
    }

    public function test_it_generates_from_source_when_slug_is_missing(): void
    {
        $generator = new SlugGenerator();

        $result = $generator->generate(null, 'Premium Plan 2026');

        $this->assertSame('premium-plan-2026', $result);
    }

    public function test_it_uses_fallback_when_slug_and_source_are_empty(): void
    {
        $generator = new SlugGenerator();

        $result = $generator->generate('   ', '   ', 'tenant');

        $this->assertSame('tenant', $result);
    }
}
