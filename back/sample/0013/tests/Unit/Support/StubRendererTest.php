<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Tests\Unit\Support;

use Archify\DddArchitect\Support\StubRenderer;
use Archify\DddArchitect\Tests\TestCase;

/**
 * @covers \Archify\DddArchitect\Support\StubRenderer
 */
final class StubRendererTest extends TestCase
{
    private StubRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = $this->app->make(StubRenderer::class);
    }

    /** @test */
    public function it_replaces_tokens_with_spaces_inside_braces(): void
    {
        $result = $this->renderer->replaceTokens(
            'Hello {{ className }}!',
            ['className' => 'Product']
        );

        $this->assertSame('Hello Product!', $result);
    }

    /** @test */
    public function it_replaces_tokens_without_spaces_inside_braces(): void
    {
        $result = $this->renderer->replaceTokens(
            'namespace {{namespace}};',
            ['namespace' => 'App\\Domain\\Catalog']
        );

        $this->assertSame('namespace App\\Domain\\Catalog;', $result);
    }

    /** @test */
    public function it_replaces_multiple_tokens_in_one_pass(): void
    {
        $template = 'class {{ className }} in {{ namespace }}';
        $result   = $this->renderer->replaceTokens($template, [
            'className' => 'Product',
            'namespace' => 'App\\Domain',
        ]);

        $this->assertSame('class Product in App\\Domain', $result);
    }

    /** @test */
    public function it_leaves_unknown_tokens_untouched(): void
    {
        $template = '{{ className }} {{ unknown }}';
        $result   = $this->renderer->replaceTokens($template, ['className' => 'Foo']);

        $this->assertStringContainsString('Foo', $result);
        $this->assertStringContainsString('{{ unknown }}', $result);
    }

    /** @test */
    public function build_tokens_returns_all_standard_keys(): void
    {
        $tokens = $this->renderer->buildTokens('Ordering', 'OrderItem', 'App\\Ordering\\Domain\\Entities');

        $this->assertArrayHasKey('className',    $tokens);
        $this->assertArrayHasKey('classSnake',   $tokens);
        $this->assertArrayHasKey('classKebab',   $tokens);
        $this->assertArrayHasKey('namespace',    $tokens);
        $this->assertArrayHasKey('contextName',  $tokens);
        $this->assertArrayHasKey('contextKebab', $tokens);
        $this->assertArrayHasKey('contextSnake', $tokens);
        $this->assertArrayHasKey('date',         $tokens);
    }

    /** @test */
    public function build_tokens_studly_cases_the_class_name(): void
    {
        $tokens = $this->renderer->buildTokens('Ordering', 'order_item', 'App\\Ordering');

        $this->assertSame('OrderItem', $tokens['className']);
    }

    /** @test */
    public function build_tokens_kebab_cases_the_context(): void
    {
        $tokens = $this->renderer->buildTokens('OrderManagement', 'Order', 'App\\OrderManagement');

        $this->assertSame('order-management', $tokens['contextKebab']);
    }

    /** @test */
    public function it_resolves_builtin_entity_stub(): void
    {
        $path = $this->renderer->resolveStubPath('domain/entity');

        $this->assertFileExists($path);
        $this->assertStringEndsWith('.stub', $path);
    }

    /** @test */
    public function it_throws_when_stub_does_not_exist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Stub file not found/');

        $this->renderer->resolveStubPath('nonexistent/stub');
    }

    /** @test */
    public function it_prefers_custom_stub_over_builtin(): void
    {
        // Write a custom stub into the temp dir
        $customDir  = $this->tempDir . '/stubs/ddd/domain';
        $this->files->ensureDirectoryExists($customDir);
        file_put_contents($customDir . '/entity.stub', '<?php // CUSTOM');

        config(['ddd-architect.stub_paths' => [$this->tempDir . '/stubs/ddd']]);

        $path = $this->renderer->resolveStubPath('domain/entity');

        $this->assertStringContainsString('CUSTOM', file_get_contents($path));
    }

    /** @test */
    public function it_renders_entity_stub_with_tokens(): void
    {
        $tokens  = $this->renderer->buildTokens('Catalog', 'Product', 'App\\Catalog\\Domain\\Entities');
        $content = $this->renderer->render('domain/entity', $tokens);

        $this->assertStringContainsString('class Product', $content);
        $this->assertStringContainsString('App\\Catalog\\Domain\\Entities', $content);
        $this->assertStringContainsString('Catalog', $content);
    }
}
