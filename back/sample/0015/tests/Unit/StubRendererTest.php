<?php

namespace YourVendor\LaravelDDDArchitect\Tests\Unit;

use YourVendor\LaravelDDDArchitect\Tests\TestCase;
use YourVendor\LaravelDDDArchitect\Support\StubRenderer;

class StubRendererTest extends TestCase
{
    private StubRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = $this->app->make(StubRenderer::class);
    }

    /** @test */
    public function it_renders_double_brace_tokens(): void
    {
        $content  = 'Hello {{ name }}, welcome to {{ place }}.';
        $rendered = $this->renderer->replace($content, ['name' => 'World', 'place' => 'DDD']);

        $this->assertSame('Hello World, welcome to DDD.', $rendered);
    }

    /** @test */
    public function it_renders_tight_double_brace_tokens(): void
    {
        $content  = 'namespace {{namespace}};';
        $rendered = $this->renderer->replace($content, ['namespace' => 'App\\Domain\\Order']);

        $this->assertSame('namespace App\\Domain\\Order;', $rendered);
    }

    /** @test */
    public function it_builds_correct_token_map(): void
    {
        $tokens = StubRenderer::buildTokens(
            context: 'Order',
            className: 'Order',
            layer: 'Domain\\Order\\Entities',
            rootNs: 'App',
        );

        $this->assertSame('Order',                    $tokens['contextName']);
        $this->assertSame('order',                    $tokens['contextLower']);
        $this->assertSame('order',                    $tokens['contextSnake']);
        $this->assertSame('order',                    $tokens['contextKebab']);
        $this->assertSame('Order',                    $tokens['className']);
        $this->assertSame('App\\Domain\\Order\\Entities', $tokens['namespace']);
    }

    /** @test */
    public function it_builds_studly_class_name_from_snake_input(): void
    {
        $tokens = StubRenderer::buildTokens(
            context: 'order_management',
            className: 'order_item',
            layer: 'Domain\\OrderManagement',
            rootNs: 'App',
        );

        $this->assertSame('OrderManagement', $tokens['contextName']);
        $this->assertSame('OrderItem',       $tokens['className']);
    }

    /** @test */
    public function it_throws_when_stub_file_does_not_exist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->renderer->render('non/existent/stub.stub', []);
    }

    /** @test */
    public function it_resolves_entity_stub(): void
    {
        // The package's built-in stubs must always resolve
        $path = $this->renderer->resolve('domain/entity.stub');
        $this->assertFileExists($path);
    }

    /** @test */
    public function it_renders_entity_stub_with_tokens(): void
    {
        $tokens = StubRenderer::buildTokens('Order', 'Order', 'Domain\\Order\\Entities');

        $rendered = $this->renderer->render('domain/entity.stub', $tokens);

        $this->assertStringContainsString('namespace App\Domain\Order\Entities', $rendered);
        $this->assertStringContainsString('class Order', $rendered);
    }
}
