<?php

declare(strict_types=1);

namespace Modules\Accounting\Tests\Unit;

use Illuminate\Http\JsonResponse;
use Modules\Accounting\Application\Services\AccountingService;
use Modules\Accounting\Interfaces\Http\Controllers\AccountingController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Structural tests for AccountingController::getBalanceSheet().
 *
 * Validates method existence, visibility, parameter types, and return type.
 * No HTTP layer / no DB required.
 */
class AccountingControllerBalanceSheetTest extends TestCase
{
    public function test_get_balance_sheet_method_exists_on_controller(): void
    {
        $this->assertTrue(
            method_exists(AccountingController::class, 'getBalanceSheet'),
            'AccountingController must expose a getBalanceSheet() action.'
        );
    }

    public function test_get_balance_sheet_is_public(): void
    {
        $ref = new ReflectionMethod(AccountingController::class, 'getBalanceSheet');
        $this->assertTrue($ref->isPublic());
    }

    public function test_get_balance_sheet_accepts_integer_id(): void
    {
        $ref    = new ReflectionMethod(AccountingController::class, 'getBalanceSheet');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()?->getName());
    }

    public function test_get_balance_sheet_returns_json_response(): void
    {
        $ref        = new ReflectionMethod(AccountingController::class, 'getBalanceSheet');
        $returnType = (string) $ref->getReturnType();

        $this->assertSame(JsonResponse::class, $returnType);
    }

    public function test_accounting_service_has_get_balance_sheet_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'getBalanceSheet'),
            'AccountingService must expose getBalanceSheet() for the controller to delegate to.'
        );
    }
}
