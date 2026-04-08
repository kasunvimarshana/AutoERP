<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Tests\Unit\Support;

use Archify\DddArchitect\Support\ContextResolver;
use Archify\DddArchitect\Tests\TestCase;

/**
 * @covers \Archify\DddArchitect\Support\ContextResolver
 */
final class ContextResolverTest extends TestCase
{
    private ContextResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = $this->app->make(ContextResolver::class);
    }

    /** @test */
    public function it_registers_and_retrieves_a_context(): void
    {
        $this->resolver->register('Ordering');

        $this->assertTrue($this->resolver->has('Ordering'));
        $this->assertNotNull($this->resolver->get('Ordering'));
    }

    /** @test */
    public function it_studly_cases_context_names_on_register(): void
    {
        $this->resolver->register('order_management');

        $this->assertTrue($this->resolver->has('OrderManagement'));
        $this->assertFalse($this->resolver->has('order_management'));
    }

    /** @test */
    public function it_stores_metadata(): void
    {
        $this->resolver->register('Billing', ['path' => '/some/path']);

        $meta = $this->resolver->get('Billing');

        $this->assertSame('/some/path', $meta['path']);
    }

    /** @test */
    public function it_returns_all_contexts(): void
    {
        $this->resolver->register('Ordering');
        $this->resolver->register('Billing');

        $all = $this->resolver->all();

        $this->assertArrayHasKey('Ordering', $all);
        $this->assertArrayHasKey('Billing', $all);
    }

    /** @test */
    public function it_forgets_a_context(): void
    {
        $this->resolver->register('Temp');
        $this->resolver->forget('Temp');

        $this->assertFalse($this->resolver->has('Temp'));
    }

    /** @test */
    public function it_resolves_layered_path_correctly(): void
    {
        $base = $this->tempDir . '/src';
        config(['ddd-architect.paths.layered' => $base]);

        $path = $this->resolver->resolvePath('Ordering');

        $this->assertSame($base . '/Ordering', $path);
    }

    /** @test */
    public function it_resolves_namespace_for_layered_mode(): void
    {
        config([
            'ddd-architect.mode'               => 'layered',
            'ddd-architect.namespaces.layered' => 'App',
        ]);

        $ns = $this->resolver->resolveNamespace('Ordering');

        $this->assertSame('App\\Ordering', $ns);
    }

    /** @test */
    public function it_resolves_namespace_for_modular_mode(): void
    {
        config([
            'ddd-architect.mode'               => 'modular',
            'ddd-architect.namespaces.modular' => 'App\\Modules',
        ]);

        $ns = $this->resolver->resolveNamespace('Billing');

        $this->assertSame('App\\Modules\\Billing', $ns);
    }

    /** @test */
    public function it_resolves_provider_class_from_pattern(): void
    {
        config([
            'ddd-architect.provider_pattern'   => '{namespace}\\Infrastructure\\Providers\\{context}ServiceProvider',
            'ddd-architect.mode'               => 'layered',
            'ddd-architect.namespaces.layered' => 'App',
        ]);

        $provider = $this->resolver->resolveProvider('Ordering');

        $this->assertSame(
            'App\\Ordering\\Infrastructure\\Providers\\OrderingServiceProvider',
            $provider
        );
    }

    /** @test */
    public function it_returns_null_for_unknown_context(): void
    {
        $this->assertNull($this->resolver->get('NonExistent'));
    }
}
