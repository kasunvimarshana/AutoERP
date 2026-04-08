<?php

namespace YourVendor\LaravelDDDArchitect\Tests\Unit;

use YourVendor\LaravelDDDArchitect\Tests\TestCase;
use YourVendor\LaravelDDDArchitect\Generators\ContextGenerator;
use YourVendor\LaravelDDDArchitect\Support\FileGenerator;
use YourVendor\LaravelDDDArchitect\Support\StubRenderer;

class ContextGeneratorTest extends TestCase
{
    private array $config;
    private StubRenderer $renderer;
    private FileGenerator $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = config('ddd-architect');
        $this->config['shared_kernel'] = false; // keep tests focused

        $this->renderer = $this->app->make(StubRenderer::class);
        $this->files    = new FileGenerator();
    }

    /** @test */
    public function it_creates_domain_directories(): void
    {
        $generator = new ContextGenerator(
            config: $this->config,
            renderer: $this->renderer,
            files: $this->files,
            contextName: 'TestContext',
        );

        $generator->generate();

        $base = base_path('app/Domain/TestContext');

        foreach ($this->config['domain_structure'] as $dir) {
            $this->assertDirectoryExists("{$base}/{$dir}");
        }
    }

    /** @test */
    public function it_creates_application_directories(): void
    {
        $generator = new ContextGenerator(
            config: $this->config,
            renderer: $this->renderer,
            files: $this->files,
            contextName: 'TestContext',
        );

        $generator->generate();

        $base = base_path('app/Application/TestContext');

        foreach ($this->config['application_structure'] as $dir) {
            $this->assertDirectoryExists("{$base}/{$dir}");
        }
    }

    /** @test */
    public function it_generates_the_domain_entity_file(): void
    {
        $generator = new ContextGenerator(
            config: $this->config,
            renderer: $this->renderer,
            files: $this->files,
            contextName: 'TestContext',
        );

        $generator->generate();

        $this->assertFileExists(
            base_path('app/Domain/TestContext/Entities/TestContext.php')
        );
    }

    /** @test */
    public function it_generates_the_infrastructure_service_provider(): void
    {
        $generator = new ContextGenerator(
            config: $this->config,
            renderer: $this->renderer,
            files: $this->files,
            contextName: 'TestContext',
        );

        $generator->generate();

        $this->assertFileExists(
            base_path('app/Infrastructure/Providers/TestContextInfrastructureServiceProvider.php')
        );
    }

    /** @test */
    public function it_generates_the_repository_interface(): void
    {
        $generator = new ContextGenerator(
            config: $this->config,
            renderer: $this->renderer,
            files: $this->files,
            contextName: 'TestContext',
        );

        $generator->generate();

        $this->assertFileExists(
            base_path('app/Domain/TestContext/Repositories/TestContextRepositoryInterface.php')
        );
    }

    /** @test */
    public function it_generates_api_and_web_route_files(): void
    {
        $generator = new ContextGenerator(
            config: $this->config,
            renderer: $this->renderer,
            files: $this->files,
            contextName: 'TestContext',
        );

        $generator->generate();

        $this->assertFileExists(base_path('app/Presentation/Http/Routes/api.php'));
        $this->assertFileExists(base_path('app/Presentation/Http/Routes/web.php'));
    }

    /** @test */
    public function it_generates_unit_and_feature_test_stubs(): void
    {
        $generator = new ContextGenerator(
            config: $this->config,
            renderer: $this->renderer,
            files: $this->files,
            contextName: 'TestContext',
        );

        $generator->generate();

        $this->assertFileExists(base_path('tests/Unit/TestContext/TestContextTest.php'));
        $this->assertFileExists(base_path('tests/Feature/TestContext/TestContextApiTest.php'));
    }

    /** @test */
    public function it_does_not_overwrite_existing_files_without_force(): void
    {
        $generator = new ContextGenerator(
            config: $this->config,
            renderer: $this->renderer,
            files: $this->files,
            contextName: 'TestContext',
        );

        $created1 = $generator->generate();

        // Second run without --force
        $created2 = $generator->generate();

        $this->assertNotEmpty($created1);
        $this->assertEmpty($created2, 'No files should be created on second run without force.');
    }

    /** @test */
    public function it_overwrites_existing_files_with_force(): void
    {
        $generator = new ContextGenerator(
            config: $this->config,
            renderer: $this->renderer,
            files: $this->files,
            contextName: 'TestContext',
        );

        $created1 = $generator->generate();
        $created2 = $generator->force()->generate();

        $this->assertNotEmpty($created1);
        $this->assertNotEmpty($created2, 'Files should be re-created with force.');
    }

    /** @test */
    public function it_studly_cases_the_context_name(): void
    {
        $generator = new ContextGenerator(
            config: $this->config,
            renderer: $this->renderer,
            files: $this->files,
            contextName: 'order_management', // snake_case input
        );

        $generator->generate();

        $this->assertDirectoryExists(base_path('app/Domain/OrderManagement'));
    }

    /** @test */
    public function it_respects_domain_only_mode(): void
    {
        $config         = $this->config;
        $config['mode'] = 'domain';

        $generator = new ContextGenerator(
            config: $config,
            renderer: $this->renderer,
            files: $this->files,
            contextName: 'TestContext',
        );

        $generator->generate();

        // Domain directories should exist
        $this->assertDirectoryExists(base_path('app/Domain/TestContext/Entities'));

        // Application directories should NOT exist
        $this->assertDirectoryDoesNotExist(base_path('app/Application/TestContext'));
    }

    /** @test */
    public function it_respects_minimal_mode(): void
    {
        $config         = $this->config;
        $config['mode'] = 'minimal';

        $generator = new ContextGenerator(
            config: $config,
            renderer: $this->renderer,
            files: $this->files,
            contextName: 'TestContext',
        );

        $generator->generate();

        $this->assertDirectoryExists(base_path('app/Domain/TestContext/Entities'));
        $this->assertDirectoryExists(base_path('app/Application/TestContext/UseCases'));
        $this->assertDirectoryDoesNotExist(base_path('app/Infrastructure'));
    }

    /** @test */
    public function it_adds_gitkeep_to_empty_directories(): void
    {
        $generator = new ContextGenerator(
            config: $this->config,
            renderer: $this->renderer,
            files: $this->files,
            contextName: 'TestContext',
        );

        $generator->generate();

        $this->assertFileExists(
            base_path('app/Domain/TestContext/Aggregates/.gitkeep')
        );
    }

    /** @test */
    public function it_renders_correct_namespace_in_entity_stub(): void
    {
        $generator = new ContextGenerator(
            config: $this->config,
            renderer: $this->renderer,
            files: $this->files,
            contextName: 'TestContext',
        );

        $generator->generate();

        $content = file_get_contents(
            base_path('app/Domain/TestContext/Entities/TestContext.php')
        );

        $this->assertStringContainsString('namespace App\Domain\TestContext\Entities', $content);
        $this->assertStringContainsString('class TestContext', $content);
    }
}
