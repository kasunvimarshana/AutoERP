<?php

declare(strict_types=1);

namespace Modules\POS\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\POS\Application\Services\POSService;
use Modules\POS\Domain\Contracts\POSRepositoryContract;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Structural compliance tests for POSService session and transaction read methods.
 *
 * These pure-PHP tests verify method signatures and structural contracts
 * without requiring a full Laravel bootstrap.
 */
class POSServiceCrudTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence
    // -------------------------------------------------------------------------

    public function test_pos_service_has_open_session_method(): void
    {
        $this->assertTrue(
            method_exists(POSService::class, 'openSession'),
            'POSService must expose openSession().'
        );
    }

    public function test_pos_service_has_close_session_method(): void
    {
        $this->assertTrue(
            method_exists(POSService::class, 'closeSession'),
            'POSService must expose closeSession().'
        );
    }

    public function test_pos_service_has_show_transaction_method(): void
    {
        $this->assertTrue(
            method_exists(POSService::class, 'showTransaction'),
            'POSService must expose showTransaction().'
        );
    }

    public function test_pos_service_has_list_sessions_method(): void
    {
        $this->assertTrue(
            method_exists(POSService::class, 'listSessions'),
            'POSService must expose listSessions().'
        );
    }

    // -------------------------------------------------------------------------
    // Visibility — all public
    // -------------------------------------------------------------------------

    public function test_open_session_is_public(): void
    {
        $ref = new ReflectionMethod(POSService::class, 'openSession');
        $this->assertTrue($ref->isPublic());
    }

    public function test_close_session_is_public(): void
    {
        $ref = new ReflectionMethod(POSService::class, 'closeSession');
        $this->assertTrue($ref->isPublic());
    }

    public function test_show_transaction_is_public(): void
    {
        $ref = new ReflectionMethod(POSService::class, 'showTransaction');
        $this->assertTrue($ref->isPublic());
    }

    public function test_list_sessions_is_public(): void
    {
        $ref = new ReflectionMethod(POSService::class, 'listSessions');
        $this->assertTrue($ref->isPublic());
    }

    // -------------------------------------------------------------------------
    // Signatures
    // -------------------------------------------------------------------------

    public function test_open_session_accepts_array_data(): void
    {
        $ref    = new ReflectionMethod(POSService::class, 'openSession');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('data', $params[0]->getName());
        $this->assertSame('array', (string) $params[0]->getType());
    }

    public function test_close_session_accepts_session_id(): void
    {
        $ref    = new ReflectionMethod(POSService::class, 'closeSession');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('sessionId', $params[0]->getName());
    }

    public function test_show_transaction_accepts_id(): void
    {
        $ref    = new ReflectionMethod(POSService::class, 'showTransaction');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    // -------------------------------------------------------------------------
    // Return types
    // -------------------------------------------------------------------------

    public function test_list_sessions_return_type_is_collection(): void
    {
        $ref        = new ReflectionMethod(POSService::class, 'listSessions');
        $returnType = (string) $ref->getReturnType();

        $this->assertSame(\Illuminate\Database\Eloquent\Collection::class, $returnType);
    }

    public function test_show_transaction_return_type_is_model(): void
    {
        $ref        = new ReflectionMethod(POSService::class, 'showTransaction');
        $returnType = (string) $ref->getReturnType();

        $this->assertSame(\Illuminate\Database\Eloquent\Model::class, $returnType);
    }

    // -------------------------------------------------------------------------
    // Service instantiation
    // -------------------------------------------------------------------------

    public function test_pos_service_instantiates_with_repository_contract(): void
    {
        $repo    = $this->createMock(POSRepositoryContract::class);
        $service = new POSService($repo);

        $this->assertInstanceOf(POSService::class, $service);
    }
}
