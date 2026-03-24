<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Tenant\Application\UseCases\CreateTenant;
use Modules\Tenant\Application\UseCases\GetTenant;
use Modules\Tenant\Application\UseCases\ListTenants;
use Modules\Tenant\Application\UseCases\UpdateTenant;
use Modules\Tenant\Application\UseCases\UpdateTenantConfig;
use Modules\Tenant\Application\UseCases\DeleteTenant;
use Modules\Tenant\Domain\Events\TenantCreated;
use Modules\Tenant\Domain\Events\TenantUpdated;
use Modules\Tenant\Domain\Events\TenantConfigChanged;
use Modules\Tenant\Domain\Events\TenantDeleted;

class TenantUseCasesImportsTest extends TestCase
{
    /**
     * Verify that all use case classes can be loaded without fatal errors.
     * This ensures all `use` statements resolve correctly.
     */
    public function test_all_use_case_classes_exist(): void
    {
        $this->assertTrue(class_exists(CreateTenant::class));
        $this->assertTrue(class_exists(GetTenant::class));
        $this->assertTrue(class_exists(ListTenants::class));
        $this->assertTrue(class_exists(UpdateTenant::class));
        $this->assertTrue(class_exists(UpdateTenantConfig::class));
        $this->assertTrue(class_exists(DeleteTenant::class));
    }

    public function test_all_event_classes_exist(): void
    {
        $this->assertTrue(class_exists(TenantCreated::class));
        $this->assertTrue(class_exists(TenantUpdated::class));
        $this->assertTrue(class_exists(TenantConfigChanged::class));
        $this->assertTrue(class_exists(TenantDeleted::class));
    }
}
