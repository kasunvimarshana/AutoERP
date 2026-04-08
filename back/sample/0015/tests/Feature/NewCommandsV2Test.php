<?php

namespace YourVendor\LaravelDDDArchitect\Tests\Feature;

use YourVendor\LaravelDDDArchitect\Tests\TestCase;

class NewCommandsV2Test extends TestCase
{
    // =========================================================================
    // ddd:make-model
    // =========================================================================

    /** @test */
    public function make_model_creates_eloquent_model_factory_and_seeder(): void
    {
        $this->artisan('ddd:make-context', ['name' => 'TestContext', '--no-shared' => true])
             ->assertSuccessful();

        $this->artisan('ddd:make-model', [
            'context' => 'TestContext',
            'name'    => 'Order',
        ])->assertSuccessful();

        $this->assertFileExists(
            base_path('app/Infrastructure/Persistence/Eloquent/OrderModel.php')
        );
        $this->assertFileExists(
            base_path('app/Infrastructure/Persistence/Factories/OrderFactory.php')
        );
        $this->assertFileExists(
            base_path('app/Infrastructure/Persistence/Seeders/OrderSeeder.php')
        );
    }

    /** @test */
    public function make_model_skips_factory_with_no_factory_flag(): void
    {
        $this->artisan('ddd:make-context', ['name' => 'TestContext', '--no-shared' => true])
             ->assertSuccessful();

        $this->artisan('ddd:make-model', [
            'context'      => 'TestContext',
            'name'         => 'Order',
            '--no-factory' => true,
        ])->assertSuccessful();

        $this->assertFileExists(
            base_path('app/Infrastructure/Persistence/Eloquent/OrderModel.php')
        );
        $this->assertFileDoesNotExist(
            base_path('app/Infrastructure/Persistence/Factories/OrderFactory.php')
        );
    }

    /** @test */
    public function make_model_skips_seeder_with_no_seeder_flag(): void
    {
        $this->artisan('ddd:make-context', ['name' => 'TestContext', '--no-shared' => true])
             ->assertSuccessful();

        $this->artisan('ddd:make-model', [
            'context'     => 'TestContext',
            'name'        => 'Order',
            '--no-seeder' => true,
        ])->assertSuccessful();

        $this->assertFileDoesNotExist(
            base_path('app/Infrastructure/Persistence/Seeders/OrderSeeder.php')
        );
    }

    /** @test */
    public function make_model_file_contains_correct_class_name(): void
    {
        $this->artisan('ddd:make-context', ['name' => 'TestContext', '--no-shared' => true])
             ->assertSuccessful();

        $this->artisan('ddd:make-model', ['context' => 'TestContext', 'name' => 'Order'])
             ->assertSuccessful();

        $content = file_get_contents(
            base_path('app/Infrastructure/Persistence/Eloquent/OrderModel.php')
        );

        $this->assertStringContainsString('class OrderModel', $content);
        $this->assertStringContainsString('namespace App\Infrastructure\Persistence\Eloquent', $content);
    }

    // =========================================================================
    // ddd:make-listener
    // =========================================================================

    /** @test */
    public function make_listener_creates_listener_file(): void
    {
        $this->artisan('ddd:make-context', ['name' => 'TestContext', '--no-shared' => true])
             ->assertSuccessful();

        $this->artisan('ddd:make-listener', [
            'context' => 'TestContext',
            'name'    => 'SendOrderConfirmation',
        ])->assertSuccessful();

        $this->assertFileExists(
            base_path('app/Infrastructure/Events/SendOrderConfirmationListener.php')
        );
    }

    /** @test */
    public function make_listener_file_contains_should_queue_interface(): void
    {
        $this->artisan('ddd:make-context', ['name' => 'TestContext', '--no-shared' => true])
             ->assertSuccessful();

        $this->artisan('ddd:make-listener', [
            'context' => 'TestContext',
            'name'    => 'SendOrderConfirmation',
        ])->assertSuccessful();

        $content = file_get_contents(
            base_path('app/Infrastructure/Events/SendOrderConfirmationListener.php')
        );

        $this->assertStringContainsString('ShouldQueue', $content);
        $this->assertStringContainsString('class SendOrderConfirmationListener', $content);
    }

    /** @test */
    public function make_listener_injects_custom_event_class_from_option(): void
    {
        $this->artisan('ddd:make-context', ['name' => 'TestContext', '--no-shared' => true])
             ->assertSuccessful();

        $this->artisan('ddd:make-listener', [
            'context' => 'TestContext',
            'name'    => 'NotifyAdmin',
            '--event' => 'App\Domain\Order\Events\OrderWasPlaced',
        ])->assertSuccessful();

        $content = file_get_contents(
            base_path('app/Infrastructure/Events/NotifyAdminListener.php')
        );

        $this->assertStringContainsString('App\Domain\Order\Events\OrderWasPlaced', $content);
    }

    // =========================================================================
    // ddd:make-policy
    // =========================================================================

    /** @test */
    public function make_policy_creates_policy_file(): void
    {
        $this->artisan('ddd:make-context', ['name' => 'TestContext', '--no-shared' => true])
             ->assertSuccessful();

        $this->artisan('ddd:make-policy', [
            'context' => 'TestContext',
            'name'    => 'Order',
        ])->assertSuccessful();

        $this->assertFileExists(
            base_path('app/Domain/TestContext/Policies/OrderPolicy.php')
        );
    }

    /** @test */
    public function make_policy_file_contains_correct_namespace_and_class(): void
    {
        $this->artisan('ddd:make-context', ['name' => 'TestContext', '--no-shared' => true])
             ->assertSuccessful();

        $this->artisan('ddd:make-policy', ['context' => 'TestContext', 'name' => 'Order'])
             ->assertSuccessful();

        $content = file_get_contents(
            base_path('app/Domain/TestContext/Policies/OrderPolicy.php')
        );

        $this->assertStringContainsString(
            'namespace App\Domain\TestContext\Policies',
            $content
        );
        $this->assertStringContainsString('class OrderPolicy', $content);
        $this->assertStringContainsString('public function allows(', $content);
    }

    // =========================================================================
    // Structure preset via config
    // =========================================================================

    /** @test */
    public function ddd_modular_preset_uses_src_base_path(): void
    {
        // Override config to use the ddd-modular preset
        config(['ddd-architect.structure' => 'ddd-modular']);

        $this->artisan('ddd:make-context', ['name' => 'TestContext', '--no-shared' => true])
             ->assertSuccessful();

        // ddd-modular uses 'src' as base_path and 'Src' as namespace
        $this->assertDirectoryExists(base_path('src/Domain/TestContext'));
    }
}
