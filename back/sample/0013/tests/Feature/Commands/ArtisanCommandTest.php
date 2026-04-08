<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Tests\Feature\Commands;

use Archify\DddArchitect\Tests\TestCase;

/**
 * Feature tests for all DDD Architect Artisan commands.
 *
 * @covers \Archify\DddArchitect\Commands\MakeContextCommand
 * @covers \Archify\DddArchitect\Commands\MakeEntityCommand
 * @covers \Archify\DddArchitect\Commands\MakeValueObjectCommand
 * @covers \Archify\DddArchitect\Commands\MakeAggregateCommand
 * @covers \Archify\DddArchitect\Commands\MakeEventCommand
 * @covers \Archify\DddArchitect\Commands\MakeServiceCommand
 * @covers \Archify\DddArchitect\Commands\MakeSpecificationCommand
 * @covers \Archify\DddArchitect\Commands\MakeRepositoryCommand
 * @covers \Archify\DddArchitect\Commands\MakeCommandCommand
 * @covers \Archify\DddArchitect\Commands\MakeQueryCommand
 * @covers \Archify\DddArchitect\Commands\MakeDtoCommand
 * @covers \Archify\DddArchitect\Commands\ListContextsCommand
 * @covers \Archify\DddArchitect\Commands\InfoCommand
 */
final class ArtisanCommandTest extends TestCase
{
    private string $src;

    protected function setUp(): void
    {
        parent::setUp();
        $this->src = $this->tempDir . '/src';
        config(['ddd-architect.paths.layered' => $this->src]);
    }

    // ── ddd:make:context ─────────────────────────────────────────────────────

    /** @test */
    public function make_context_scaffolds_directory_tree(): void
    {
        $this->artisan('ddd:make:context', ['context' => 'Ordering'])
            ->assertSuccessful();

        $this->assertDirectoryExists($this->src . '/Ordering/Domain/Entities');
        $this->assertDirectoryExists($this->src . '/Ordering/Domain/Repositories');
        $this->assertDirectoryExists($this->src . '/Ordering/Application/Commands');
        $this->assertDirectoryExists($this->src . '/Ordering/Application/Handlers');
        $this->assertDirectoryExists($this->src . '/Ordering/Infrastructure/Persistence/Repositories');
    }

    /** @test */
    public function make_context_creates_service_provider(): void
    {
        $this->artisan('ddd:make:context', ['context' => 'Billing'])
            ->assertSuccessful();

        $providerPath = $this->src . '/Billing/Infrastructure/Providers/BillingServiceProvider.php';
        $this->assertFileExists($providerPath);
        $this->assertStringContainsString('BillingServiceProvider', file_get_contents($providerPath));
    }

    /** @test */
    public function make_context_scaffolds_shared_kernel(): void
    {
        $sharedPath = $this->tempDir . '/src/Shared';
        config(['ddd-architect.shared_kernel.path' => $sharedPath]);

        $this->artisan('ddd:make:context', ['context' => 'Identity'])
            ->assertSuccessful();

        $this->assertFileExists($sharedPath . '/Domain/Contracts/AggregateRootContract.php');
        $this->assertFileExists($sharedPath . '/Domain/Contracts/EntityContract.php');
        $this->assertFileExists($sharedPath . '/Domain/Contracts/RepositoryContract.php');
        $this->assertFileExists($sharedPath . '/Domain/ValueObjects/Uuid.php');
        $this->assertFileExists($sharedPath . '/Domain/ValueObjects/Email.php');
        $this->assertFileExists($sharedPath . '/Domain/ValueObjects/Money.php');
    }

    // ── ddd:make:entity ──────────────────────────────────────────────────────

    /** @test */
    public function make_entity_creates_entity_file(): void
    {
        $this->artisan('ddd:make:entity', ['context' => 'Catalog', 'name' => 'Product'])
            ->assertSuccessful();

        $path = $this->src . '/Catalog/Domain/Entities/Product.php';
        $this->assertFileExists($path);
        $this->assertStringContainsString('class Product', file_get_contents($path));
        $this->assertStringContainsString('App\\Catalog\\Domain\\Entities', file_get_contents($path));
    }

    /** @test */
    public function make_entity_skips_without_force(): void
    {
        $path = $this->src . '/Catalog/Domain/Entities/Product.php';
        $this->files->ensureDirectoryExists(dirname($path));
        file_put_contents($path, '<?php // sentinel');

        $this->artisan('ddd:make:entity', ['context' => 'Catalog', 'name' => 'Product'])
            ->assertSuccessful();

        $this->assertStringContainsString('sentinel', file_get_contents($path));
    }

    /** @test */
    public function make_entity_overwrites_with_force_flag(): void
    {
        $path = $this->src . '/Catalog/Domain/Entities/Product.php';
        $this->files->ensureDirectoryExists(dirname($path));
        file_put_contents($path, '<?php // sentinel');

        $this->artisan('ddd:make:entity', [
            'context' => 'Catalog',
            'name'    => 'Product',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertStringNotContainsString('sentinel', file_get_contents($path));
    }

    // ── ddd:make:value-object ────────────────────────────────────────────────

    /** @test */
    public function make_value_object_creates_correct_file(): void
    {
        $this->artisan('ddd:make:value-object', ['context' => 'Ordering', 'name' => 'Price'])
            ->assertSuccessful();

        $path = $this->src . '/Ordering/Domain/ValueObjects/Price.php';
        $this->assertFileExists($path);
        $this->assertStringContainsString('class Price', file_get_contents($path));
    }

    // ── ddd:make:aggregate ───────────────────────────────────────────────────

    /** @test */
    public function make_aggregate_creates_correct_file(): void
    {
        $this->artisan('ddd:make:aggregate', ['context' => 'Ordering', 'name' => 'Order'])
            ->assertSuccessful();

        $path = $this->src . '/Ordering/Domain/Aggregates/Order.php';
        $this->assertFileExists($path);
        $this->assertStringContainsString('AggregateRootContract', file_get_contents($path));
    }

    // ── ddd:make:event ───────────────────────────────────────────────────────

    /** @test */
    public function make_event_creates_event_file(): void
    {
        $this->artisan('ddd:make:event', ['context' => 'Ordering', 'name' => 'OrderWasPlaced'])
            ->assertSuccessful();

        $path = $this->src . '/Ordering/Domain/Events/OrderWasPlaced.php';
        $this->assertFileExists($path);
        $this->assertStringContainsString('class OrderWasPlaced', file_get_contents($path));
    }

    // ── ddd:make:repository ──────────────────────────────────────────────────

    /** @test */
    public function make_repository_creates_interface_and_implementation(): void
    {
        $this->artisan('ddd:make:repository', ['context' => 'Ordering', 'name' => 'Order'])
            ->assertSuccessful();

        $interface = $this->src . '/Ordering/Domain/Repositories/OrderRepositoryInterface.php';
        $impl      = $this->src . '/Ordering/Infrastructure/Persistence/Repositories/EloquentOrderRepository.php';

        $this->assertFileExists($interface);
        $this->assertFileExists($impl);
        $this->assertStringContainsString('interface OrderRepositoryInterface', file_get_contents($interface));
        $this->assertStringContainsString('class EloquentOrderRepository',      file_get_contents($impl));
    }

    // ── ddd:make:command ─────────────────────────────────────────────────────

    /** @test */
    public function make_command_creates_command_and_handler_pair(): void
    {
        $this->artisan('ddd:make:command', ['context' => 'Ordering', 'name' => 'CreateOrder'])
            ->assertSuccessful();

        $cmd     = $this->src . '/Ordering/Application/Commands/CreateOrderCommand.php';
        $handler = $this->src . '/Ordering/Application/Handlers/CreateOrderHandler.php';

        $this->assertFileExists($cmd);
        $this->assertFileExists($handler);
        $this->assertStringContainsString('CreateOrderCommand', file_get_contents($handler));
    }

    // ── ddd:make:query ───────────────────────────────────────────────────────

    /** @test */
    public function make_query_creates_query_and_handler_pair(): void
    {
        $this->artisan('ddd:make:query', ['context' => 'Catalog', 'name' => 'GetProduct'])
            ->assertSuccessful();

        $qry     = $this->src . '/Catalog/Application/Queries/GetProductQuery.php';
        $handler = $this->src . '/Catalog/Application/Handlers/GetProductQueryHandler.php';

        $this->assertFileExists($qry);
        $this->assertFileExists($handler);
        $this->assertStringContainsString('GetProductQuery', file_get_contents($handler));
    }

    // ── ddd:make:dto ─────────────────────────────────────────────────────────

    /** @test */
    public function make_dto_creates_dto_file(): void
    {
        $this->artisan('ddd:make:dto', ['context' => 'Catalog', 'name' => 'CreateProduct'])
            ->assertSuccessful();

        $path = $this->src . '/Catalog/Application/DTOs/CreateProductDto.php';
        $this->assertFileExists($path);
        $this->assertStringContainsString('class CreateProductDto', file_get_contents($path));
    }

    // ── ddd:list ─────────────────────────────────────────────────────────────

    /** @test */
    public function list_command_exits_successfully(): void
    {
        $this->artisan('ddd:list')->assertSuccessful();
    }

    /** @test */
    public function list_command_shows_discovered_contexts(): void
    {
        $this->files->ensureDirectoryExists($this->src . '/Ordering');
        $this->files->ensureDirectoryExists($this->src . '/Billing');

        $this->artisan('ddd:list')
            ->assertSuccessful()
            ->expectsOutputToContain('Ordering')
            ->expectsOutputToContain('Billing');
    }

    // ── ddd:info ─────────────────────────────────────────────────────────────

    /** @test */
    public function info_command_displays_configuration(): void
    {
        $this->artisan('ddd:info')
            ->assertSuccessful()
            ->expectsOutputToContain('DDD Architect')
            ->expectsOutputToContain('layered');
    }

    // ── ddd:stubs:publish ────────────────────────────────────────────────────

    /** @test */
    public function publish_stubs_command_copies_stubs_to_resource_path(): void
    {
        $this->artisan('ddd:stubs:publish')->assertSuccessful();

        $this->assertDirectoryExists(resource_path('stubs/ddd'));
        $this->assertFileExists(resource_path('stubs/ddd/domain/entity.stub'));
    }

    /** @test */
    public function publish_stubs_skips_existing_without_force(): void
    {
        $target = resource_path('stubs/ddd/domain/entity.stub');
        $this->files->ensureDirectoryExists(dirname($target));
        file_put_contents($target, '<?php // sentinel');

        $this->artisan('ddd:stubs:publish')->assertSuccessful();

        $this->assertStringContainsString('sentinel', file_get_contents($target));
    }

    /** @test */
    public function publish_stubs_overwrites_with_force(): void
    {
        $target = resource_path('stubs/ddd/domain/entity.stub');
        $this->files->ensureDirectoryExists(dirname($target));
        file_put_contents($target, '<?php // sentinel');

        $this->artisan('ddd:stubs:publish', ['--force' => true])->assertSuccessful();

        $this->assertStringNotContainsString('sentinel', file_get_contents($target));
    }
}
