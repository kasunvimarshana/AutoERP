<?php

namespace YourVendor\LaravelDDDArchitect\Tests\Feature;

use YourVendor\LaravelDDDArchitect\Tests\TestCase;

class ArtisanCommandsTest extends TestCase
{
    // =========================================================================
    // ddd:make-context
    // =========================================================================

    /** @test */
    public function make_context_creates_full_ddd_structure(): void
    {
        $this->artisan('ddd:make-context', ['name' => 'TestContext'])
             ->assertSuccessful();

        $this->assertDirectoryExists(base_path('app/Domain/TestContext/Entities'));
        $this->assertDirectoryExists(base_path('app/Domain/TestContext/ValueObjects'));
        $this->assertDirectoryExists(base_path('app/Domain/TestContext/Repositories'));
        $this->assertDirectoryExists(base_path('app/Application/TestContext/UseCases'));
        $this->assertDirectoryExists(base_path('app/Application/TestContext/Commands'));
        $this->assertDirectoryExists(base_path('app/Application/TestContext/Handlers'));
        $this->assertDirectoryExists(base_path('app/Infrastructure/Persistence/Eloquent'));
        $this->assertDirectoryExists(base_path('app/Presentation/Http/Controllers/Api'));
    }

    /** @test */
    public function make_context_accepts_mode_option(): void
    {
        $this->artisan('ddd:make-context', [
            'name'   => 'TestContext',
            '--mode' => 'domain',
        ])->assertSuccessful();

        $this->assertDirectoryExists(base_path('app/Domain/TestContext/Entities'));
        $this->assertDirectoryDoesNotExist(base_path('app/Application/TestContext'));
    }

    /** @test */
    public function make_context_studly_cases_the_name(): void
    {
        $this->artisan('ddd:make-context', ['name' => 'order_management'])
             ->assertSuccessful();

        $this->assertDirectoryExists(base_path('app/Domain/OrderManagement'));
    }

    /** @test */
    public function make_context_does_not_overwrite_without_force(): void
    {
        $entityPath = base_path('app/Domain/TestContext/Entities/TestContext.php');

        // First run
        $this->artisan('ddd:make-context', ['name' => 'TestContext'])->assertSuccessful();
        $this->assertFileExists($entityPath);

        // Manually modify the file
        $originalContent = file_get_contents($entityPath);
        file_put_contents($entityPath, '// CUSTOM CONTENT');

        // Second run without --force
        $this->artisan('ddd:make-context', ['name' => 'TestContext'])->assertSuccessful();

        // Custom content should be preserved
        $this->assertSame('// CUSTOM CONTENT', file_get_contents($entityPath));

        // Restore for teardown
        file_put_contents($entityPath, $originalContent);
    }

    /** @test */
    public function make_context_overwrites_with_force(): void
    {
        $entityPath = base_path('app/Domain/TestContext/Entities/TestContext.php');

        $this->artisan('ddd:make-context', ['name' => 'TestContext'])->assertSuccessful();
        file_put_contents($entityPath, '// CUSTOM CONTENT');

        $this->artisan('ddd:make-context', [
            'name'    => 'TestContext',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertStringNotContainsString(
            '// CUSTOM CONTENT',
            file_get_contents($entityPath)
        );
    }

    // =========================================================================
    // ddd:make-entity
    // =========================================================================

    /** @test */
    public function make_entity_creates_entity_file(): void
    {
        // Scaffold the context first (without interaction)
        $this->artisan('ddd:make-context', ['name' => 'TestContext', '--no-shared' => true])
             ->assertSuccessful();

        $this->artisan('ddd:make-entity', [
            'context' => 'TestContext',
            'name'    => 'LineItem',
        ])->assertSuccessful();

        $this->assertFileExists(
            base_path('app/Domain/TestContext/Entities/LineItem.php')
        );
    }

    /** @test */
    public function make_entity_file_contains_correct_namespace(): void
    {
        $this->artisan('ddd:make-context', ['name' => 'TestContext', '--no-shared' => true])
             ->assertSuccessful();

        $this->artisan('ddd:make-entity', ['context' => 'TestContext', 'name' => 'LineItem'])
             ->assertSuccessful();

        $content = file_get_contents(
            base_path('app/Domain/TestContext/Entities/LineItem.php')
        );

        $this->assertStringContainsString('namespace App\Domain\TestContext\Entities', $content);
        $this->assertStringContainsString('class LineItem', $content);
    }

    // =========================================================================
    // ddd:make-value-object
    // =========================================================================

    /** @test */
    public function make_value_object_creates_file_with_correct_namespace(): void
    {
        $this->artisan('ddd:make-context', ['name' => 'TestContext', '--no-shared' => true])
             ->assertSuccessful();

        $this->artisan('ddd:make-value-object', [
            'context' => 'TestContext',
            'name'    => 'OrderStatus',
        ])->assertSuccessful();

        $path    = base_path('app/Domain/TestContext/ValueObjects/OrderStatus.php');
        $content = file_get_contents($path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('namespace App\Domain\TestContext\ValueObjects', $content);
        $this->assertStringContainsString('class OrderStatus', $content);
    }

    // =========================================================================
    // ddd:make-use-case
    // =========================================================================

    /** @test */
    public function make_use_case_creates_file(): void
    {
        $this->artisan('ddd:make-context', ['name' => 'TestContext', '--no-shared' => true])
             ->assertSuccessful();

        $this->artisan('ddd:make-use-case', [
            'context' => 'TestContext',
            'name'    => 'CreateOrder',
        ])->assertSuccessful();

        $path = base_path('app/Application/TestContext/UseCases/CreateOrderUseCase.php');
        $this->assertFileExists($path);
        $this->assertStringContainsString(
            'namespace App\Application\TestContext\UseCases',
            file_get_contents($path)
        );
    }

    // =========================================================================
    // ddd:make-repository
    // =========================================================================

    /** @test */
    public function make_repository_creates_interface_and_implementation(): void
    {
        $this->artisan('ddd:make-context', ['name' => 'TestContext', '--no-shared' => true])
             ->assertSuccessful();

        $this->artisan('ddd:make-repository', [
            'context' => 'TestContext',
            'name'    => 'Order',
        ])->assertSuccessful();

        $this->assertFileExists(
            base_path('app/Domain/TestContext/Repositories/OrderRepositoryInterface.php')
        );
        $this->assertFileExists(
            base_path('app/Infrastructure/Persistence/Repositories/EloquentOrderRepository.php')
        );
    }

    // =========================================================================
    // ddd:make-command-handler
    // =========================================================================

    /** @test */
    public function make_command_handler_creates_command_and_handler(): void
    {
        $this->artisan('ddd:make-context', ['name' => 'TestContext', '--no-shared' => true])
             ->assertSuccessful();

        $this->artisan('ddd:make-command-handler', [
            'context' => 'TestContext',
            'name'    => 'CreateOrder',
        ])->assertSuccessful();

        $this->assertFileExists(
            base_path('app/Application/TestContext/Commands/CreateOrderCommand.php')
        );
        $this->assertFileExists(
            base_path('app/Application/TestContext/Handlers/CreateOrderHandler.php')
        );
    }

    // =========================================================================
    // ddd:make-query-handler
    // =========================================================================

    /** @test */
    public function make_query_handler_creates_query_and_handler(): void
    {
        $this->artisan('ddd:make-context', ['name' => 'TestContext', '--no-shared' => true])
             ->assertSuccessful();

        $this->artisan('ddd:make-query-handler', [
            'context' => 'TestContext',
            'name'    => 'GetOrder',
        ])->assertSuccessful();

        $this->assertFileExists(
            base_path('app/Application/TestContext/Queries/GetOrderQuery.php')
        );
        $this->assertFileExists(
            base_path('app/Application/TestContext/Handlers/GetOrderQueryHandler.php')
        );
    }

    // =========================================================================
    // ddd:make-aggregate
    // =========================================================================

    /** @test */
    public function make_aggregate_creates_aggregate_root_file(): void
    {
        $this->artisan('ddd:make-context', ['name' => 'TestContext', '--no-shared' => true])
             ->assertSuccessful();

        $this->artisan('ddd:make-aggregate', [
            'context' => 'TestContext',
            'name'    => 'OrderAggregate',
        ])->assertSuccessful();

        $path = base_path('app/Domain/TestContext/Aggregates/OrderAggregate.php');
        $this->assertFileExists($path);
        $this->assertStringContainsString(
            'namespace App\Domain\TestContext\Aggregates',
            file_get_contents($path)
        );
    }

    // =========================================================================
    // ddd:make-specification
    // =========================================================================

    /** @test */
    public function make_specification_creates_specification_file(): void
    {
        $this->artisan('ddd:make-context', ['name' => 'TestContext', '--no-shared' => true])
             ->assertSuccessful();

        $this->artisan('ddd:make-specification', [
            'context' => 'TestContext',
            'name'    => 'OrderIsActive',
        ])->assertSuccessful();

        $path = base_path('app/Domain/TestContext/Specifications/OrderIsActive.php');
        $this->assertFileExists($path);
    }

    // =========================================================================
    // ddd:list
    // =========================================================================

    /** @test */
    public function ddd_list_shows_empty_message_when_no_contexts(): void
    {
        $this->artisan('ddd:list')
             ->expectsOutputToContain('No bounded contexts found')
             ->assertSuccessful();
    }

    /** @test */
    public function ddd_list_shows_discovered_contexts(): void
    {
        $this->artisan('ddd:make-context', ['name' => 'TestContext', '--no-shared' => true])
             ->assertSuccessful();

        $this->artisan('ddd:list')
             ->expectsOutputToContain('TestContext')
             ->assertSuccessful();
    }

    // =========================================================================
    // ddd:info
    // =========================================================================

    /** @test */
    public function ddd_info_displays_configuration(): void
    {
        $this->artisan('ddd:info')
             ->expectsOutputToContain('Laravel DDD Architect')
             ->expectsOutputToContain('ddd:make-context')
             ->assertSuccessful();
    }
}
