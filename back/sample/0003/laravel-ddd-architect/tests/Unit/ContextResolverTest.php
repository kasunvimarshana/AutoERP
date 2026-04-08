<?php

namespace YourVendor\LaravelDDDArchitect\Tests\Unit;

use YourVendor\LaravelDDDArchitect\Tests\TestCase;
use YourVendor\LaravelDDDArchitect\Contracts\ContextRegistrar;
use YourVendor\LaravelDDDArchitect\Generators\ContextGenerator;
use YourVendor\LaravelDDDArchitect\Support\FileGenerator;
use YourVendor\LaravelDDDArchitect\Support\StubRenderer;

class ContextResolverTest extends TestCase
{
    private ContextRegistrar $registrar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registrar = $this->app->make(ContextRegistrar::class);
    }

    /** @test */
    public function it_returns_empty_array_when_no_contexts_exist(): void
    {
        $this->assertSame([], $this->registrar->all());
    }

    /** @test */
    public function it_detects_created_contexts(): void
    {
        $this->scaffoldContext('Order');
        $this->scaffoldContext('Billing');

        $all = $this->registrar->all();

        $this->assertContains('Order',   $all);
        $this->assertContains('Billing', $all);
    }

    /** @test */
    public function it_reports_exists_false_for_missing_context(): void
    {
        $this->assertFalse($this->registrar->exists('NonExistent'));
    }

    /** @test */
    public function it_reports_exists_true_after_scaffold(): void
    {
        $this->scaffoldContext('Order');
        $this->assertTrue($this->registrar->exists('Order'));
    }

    /** @test */
    public function it_returns_correct_absolute_path(): void
    {
        $path = $this->registrar->path('Order');
        $this->assertStringEndsWith('app/Domain/Order', str_replace('\\', '/', $path));
    }

    /** @test */
    public function it_returns_correct_namespace(): void
    {
        $ns = $this->registrar->namespace('Order');
        $this->assertSame('App\\Domain\\Order', $ns);
    }

    /** @test */
    public function it_excludes_shared_from_context_list(): void
    {
        $this->scaffoldContext('Order');
        // Manually create a Shared directory
        mkdir(base_path('app/Domain/Shared'), 0755, true);

        $all = $this->registrar->all();

        $this->assertNotContains('Shared', $all);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function scaffoldContext(string $name): void
    {
        $config         = config('ddd-architect');
        $config['shared_kernel'] = false;

        $generator = new ContextGenerator(
            config: $config,
            renderer: $this->app->make(StubRenderer::class),
            files: new FileGenerator(),
            contextName: $name,
        );

        $generator->generate();
    }
}
