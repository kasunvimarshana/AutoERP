<?php

declare(strict_types=1);

namespace Modules\Tenancy\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Modules\Tenancy\Application\Services\TenancyService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Structural delegation tests for TenancyService update/delete paths.
 *
 * Verifies that update() and delete() exist, are public, carry correct
 * signatures, and that all expected service methods are present.
 * Pure-PHP — no Laravel bootstrap required.
 */
class TenancyServiceDelegationTest extends TestCase
{
    // -------------------------------------------------------------------------
    // update — existence and visibility
    // -------------------------------------------------------------------------

    public function test_update_method_exists_and_is_public(): void
    {
        $this->assertTrue(method_exists(TenancyService::class, 'update'));

        $ref = new ReflectionMethod(TenancyService::class, 'update');
        $this->assertTrue($ref->isPublic());
    }

    // -------------------------------------------------------------------------
    // update — signature: (int|string $id, array $data)
    // -------------------------------------------------------------------------

    public function test_update_accepts_id_and_data_array(): void
    {
        $ref    = new ReflectionMethod(TenancyService::class, 'update');
        $params = $ref->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('data', $params[1]->getName());
        $this->assertSame('array', (string) $params[1]->getType());
    }

    // -------------------------------------------------------------------------
    // delete — existence and visibility
    // -------------------------------------------------------------------------

    public function test_delete_method_exists_and_is_public(): void
    {
        $this->assertTrue(method_exists(TenancyService::class, 'delete'));

        $ref = new ReflectionMethod(TenancyService::class, 'delete');
        $this->assertTrue($ref->isPublic());
    }

    // -------------------------------------------------------------------------
    // delete — signature: (int|string $id) returning bool
    // -------------------------------------------------------------------------

    public function test_delete_accepts_single_id_parameter(): void
    {
        $ref    = new ReflectionMethod(TenancyService::class, 'delete');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    public function test_delete_return_type_is_bool(): void
    {
        $ref        = new ReflectionMethod(TenancyService::class, 'delete');
        $returnType = (string) $ref->getReturnType();

        $this->assertSame('bool', $returnType);
    }

    // -------------------------------------------------------------------------
    // create — return type is Model
    // -------------------------------------------------------------------------

    public function test_create_return_type_is_model(): void
    {
        $ref        = new ReflectionMethod(TenancyService::class, 'create');
        $returnType = (string) $ref->getReturnType();

        $this->assertSame(Model::class, $returnType);
    }

    // -------------------------------------------------------------------------
    // update — return type is Model
    // -------------------------------------------------------------------------

    public function test_update_return_type_is_model(): void
    {
        $ref        = new ReflectionMethod(TenancyService::class, 'update');
        $returnType = (string) $ref->getReturnType();

        $this->assertSame(Model::class, $returnType);
    }

    // -------------------------------------------------------------------------
    // All expected service methods exist
    // -------------------------------------------------------------------------

    public function test_service_has_all_expected_methods(): void
    {
        $expected = ['list', 'listActive', 'show', 'findBySlug', 'findByDomain'];

        foreach ($expected as $method) {
            $this->assertTrue(
                method_exists(TenancyService::class, $method),
                "TenancyService must expose {$method}()."
            );
        }
    }
}
