<?php

declare(strict_types=1);

namespace Archify\DddArchitect\Tests\Unit\Generators;

use Archify\DddArchitect\Generators\EntityGenerator;
use Archify\DddArchitect\Generators\ValueObjectGenerator;
use Archify\DddArchitect\Generators\AggregateRootGenerator;
use Archify\DddArchitect\Generators\DomainEventGenerator;
use Archify\DddArchitect\Generators\DomainServiceGenerator;
use Archify\DddArchitect\Generators\SpecificationGenerator;
use Archify\DddArchitect\Generators\RepositoryInterfaceGenerator;
use Archify\DddArchitect\Generators\EloquentRepositoryGenerator;
use Archify\DddArchitect\Generators\CommandGenerator;
use Archify\DddArchitect\Generators\CommandHandlerGenerator;
use Archify\DddArchitect\Generators\QueryGenerator;
use Archify\DddArchitect\Generators\QueryHandlerGenerator;
use Archify\DddArchitect\Generators\DtoGenerator;
use Archify\DddArchitect\Tests\TestCase;

/**
 * @covers \Archify\DddArchitect\Generators\AbstractGenerator
 * @covers \Archify\DddArchitect\Generators\EntityGenerator
 * @covers \Archify\DddArchitect\Generators\ValueObjectGenerator
 * @covers \Archify\DddArchitect\Generators\AggregateRootGenerator
 * @covers \Archify\DddArchitect\Generators\DomainEventGenerator
 * @covers \Archify\DddArchitect\Generators\DomainServiceGenerator
 * @covers \Archify\DddArchitect\Generators\SpecificationGenerator
 * @covers \Archify\DddArchitect\Generators\RepositoryInterfaceGenerator
 * @covers \Archify\DddArchitect\Generators\EloquentRepositoryGenerator
 * @covers \Archify\DddArchitect\Generators\CommandGenerator
 * @covers \Archify\DddArchitect\Generators\CommandHandlerGenerator
 * @covers \Archify\DddArchitect\Generators\QueryGenerator
 * @covers \Archify\DddArchitect\Generators\QueryHandlerGenerator
 * @covers \Archify\DddArchitect\Generators\DtoGenerator
 */
final class GeneratorsTest extends TestCase
{
    private string $srcBase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->srcBase = $this->tempDir . '/src';
        config(['ddd-architect.paths.layered' => $this->srcBase]);
    }

    // ── EntityGenerator ───────────────────────────────────────────────────────

    /** @test */
    public function entity_generator_creates_file_with_correct_namespace(): void
    {
        $gen  = $this->app->make(EntityGenerator::class);
        $ok   = $gen->generate('Catalog', 'Product');

        $this->assertTrue($ok);

        $path = $this->srcBase . '/Catalog/Domain/Entities/Product.php';
        $this->assertFileWasCreated($path);
        $this->assertFileContains($path, 'namespace App\\Catalog\\Domain\\Entities');
        $this->assertFileContains($path, 'class Product');
    }

    /** @test */
    public function entity_generator_studly_cases_class_name(): void
    {
        $gen = $this->app->make(EntityGenerator::class);
        $gen->generate('Catalog', 'product_variant');

        $path = $this->srcBase . '/Catalog/Domain/Entities/ProductVariant.php';
        $this->assertFileWasCreated($path);
        $this->assertFileContains($path, 'class ProductVariant');
    }

    /** @test */
    public function entity_generator_skips_existing_file_without_force(): void
    {
        $gen  = $this->app->make(EntityGenerator::class);
        $path = $this->srcBase . '/Catalog/Domain/Entities/Product.php';

        $gen->generate('Catalog', 'Product');
        file_put_contents($path, '<?php // sentinel');

        $result = $gen->generate('Catalog', 'Product', ['force' => false]);

        $this->assertFalse($result);
        $this->assertStringContainsString('sentinel', file_get_contents($path));
    }

    /** @test */
    public function entity_generator_overwrites_with_force(): void
    {
        $gen  = $this->app->make(EntityGenerator::class);
        $path = $this->srcBase . '/Catalog/Domain/Entities/Product.php';

        $gen->generate('Catalog', 'Product');
        file_put_contents($path, '<?php // sentinel');

        $gen->generate('Catalog', 'Product', ['force' => true]);

        $this->assertStringNotContainsString('sentinel', file_get_contents($path));
    }

    // ── ValueObjectGenerator ─────────────────────────────────────────────────

    /** @test */
    public function value_object_generator_creates_correct_file(): void
    {
        $gen  = $this->app->make(ValueObjectGenerator::class);
        $gen->generate('Catalog', 'Money');

        $path = $this->srcBase . '/Catalog/Domain/ValueObjects/Money.php';
        $this->assertFileWasCreated($path);
        $this->assertFileContains($path, 'class Money');
        $this->assertFileContains($path, 'App\\Catalog\\Domain\\ValueObjects');
    }

    // ── AggregateRootGenerator ───────────────────────────────────────────────

    /** @test */
    public function aggregate_generator_creates_correct_file(): void
    {
        $gen = $this->app->make(AggregateRootGenerator::class);
        $gen->generate('Ordering', 'Order');

        $path = $this->srcBase . '/Ordering/Domain/Aggregates/Order.php';
        $this->assertFileWasCreated($path);
        $this->assertFileContains($path, 'class Order');
        $this->assertFileContains($path, 'AggregateRootContract');
    }

    // ── DomainEventGenerator ─────────────────────────────────────────────────

    /** @test */
    public function event_generator_creates_correct_file(): void
    {
        $gen = $this->app->make(DomainEventGenerator::class);
        $gen->generate('Ordering', 'OrderWasPlaced');

        $path = $this->srcBase . '/Ordering/Domain/Events/OrderWasPlaced.php';
        $this->assertFileWasCreated($path);
        $this->assertFileContains($path, 'class OrderWasPlaced');
    }

    // ── RepositoryInterfaceGenerator + EloquentRepositoryGenerator ───────────

    /** @test */
    public function repository_interface_generator_creates_interface(): void
    {
        $gen = $this->app->make(RepositoryInterfaceGenerator::class);
        $gen->generate('Ordering', 'Order');

        $path = $this->srcBase . '/Ordering/Domain/Repositories/OrderRepositoryInterface.php';
        $this->assertFileWasCreated($path);
        $this->assertFileContains($path, 'interface OrderRepositoryInterface');
    }

    /** @test */
    public function eloquent_repository_generator_creates_implementation(): void
    {
        $gen = $this->app->make(EloquentRepositoryGenerator::class);
        $gen->generate('Ordering', 'Order');

        $path = $this->srcBase . '/Ordering/Infrastructure/Persistence/Repositories/EloquentOrderRepository.php';
        $this->assertFileWasCreated($path);
        $this->assertFileContains($path, 'class EloquentOrderRepository');
        $this->assertFileContains($path, 'OrderRepositoryInterface');
    }

    // ── CQRS generators ──────────────────────────────────────────────────────

    /** @test */
    public function command_generator_creates_command_class(): void
    {
        $gen = $this->app->make(CommandGenerator::class);
        $gen->generate('Ordering', 'CreateOrder');

        $path = $this->srcBase . '/Ordering/Application/Commands/CreateOrderCommand.php';
        $this->assertFileWasCreated($path);
        $this->assertFileContains($path, 'class CreateOrderCommand');
    }

    /** @test */
    public function command_handler_generator_imports_command_namespace(): void
    {
        $gen = $this->app->make(CommandHandlerGenerator::class);
        $gen->generate('Ordering', 'CreateOrder');

        $path = $this->srcBase . '/Ordering/Application/Handlers/CreateOrderHandler.php';
        $this->assertFileWasCreated($path);
        $this->assertFileContains($path, 'CreateOrderCommand');
        $this->assertFileContains($path, 'class CreateOrderHandler');
    }

    /** @test */
    public function query_generator_creates_query_class(): void
    {
        $gen = $this->app->make(QueryGenerator::class);
        $gen->generate('Ordering', 'GetOrder');

        $path = $this->srcBase . '/Ordering/Application/Queries/GetOrderQuery.php';
        $this->assertFileWasCreated($path);
        $this->assertFileContains($path, 'class GetOrderQuery');
    }

    /** @test */
    public function query_handler_generator_creates_handler_class(): void
    {
        $gen = $this->app->make(QueryHandlerGenerator::class);
        $gen->generate('Ordering', 'GetOrder');

        $path = $this->srcBase . '/Ordering/Application/Handlers/GetOrderQueryHandler.php';
        $this->assertFileWasCreated($path);
        $this->assertFileContains($path, 'class GetOrderQueryHandler');
        $this->assertFileContains($path, 'GetOrderQuery');
    }

    /** @test */
    public function dto_generator_creates_dto_class(): void
    {
        $gen = $this->app->make(DtoGenerator::class);
        $gen->generate('Ordering', 'CreateOrder');

        $path = $this->srcBase . '/Ordering/Application/DTOs/CreateOrderDto.php';
        $this->assertFileWasCreated($path);
        $this->assertFileContains($path, 'class CreateOrderDto');
    }

    // ── Namespace correctness across modes ───────────────────────────────────

    /** @test */
    public function entity_generator_uses_modular_namespace_when_mode_is_modular(): void
    {
        config([
            'ddd-architect.mode'               => 'modular',
            'ddd-architect.paths.modular'      => $this->tempDir . '/app/Modules',
            'ddd-architect.namespaces.modular' => 'App\\Modules',
        ]);

        $gen  = $this->app->make(EntityGenerator::class);
        $gen->generate('Catalog', 'Product');

        $path = $this->tempDir . '/app/Modules/Catalog/Domain/Entities/Product.php';
        $this->assertFileWasCreated($path);
        $this->assertFileContains($path, 'namespace App\\Modules\\Catalog\\Domain\\Entities');
    }
}
