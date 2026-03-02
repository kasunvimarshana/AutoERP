<?php

declare(strict_types=1);

namespace Modules\Accounting\Tests\Unit;

use Modules\Accounting\Application\Services\AccountingService;
use Modules\Accounting\Interfaces\Http\Controllers\AccountingController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit tests for AccountingController financial statement endpoints.
 *
 * Verifies that getTrialBalance() and getProfitAndLoss() controller + service
 * methods exist, are public, and accept the correct parameter signatures.
 * No database or Laravel bootstrap required.
 */
class AccountingControllerFinancialStatementsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Controller — getTrialBalance
    // -------------------------------------------------------------------------

    public function test_controller_has_get_trial_balance_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingController::class, 'getTrialBalance'),
            'AccountingController must expose a public getTrialBalance() method.'
        );
    }

    public function test_controller_get_trial_balance_is_public(): void
    {
        $ref = new ReflectionMethod(AccountingController::class, 'getTrialBalance');
        $this->assertTrue($ref->isPublic());
    }

    public function test_controller_get_trial_balance_accepts_int_id(): void
    {
        $ref    = new ReflectionMethod(AccountingController::class, 'getTrialBalance');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()?->getName());
    }

    public function test_controller_get_trial_balance_returns_json_response(): void
    {
        $ref = new ReflectionMethod(AccountingController::class, 'getTrialBalance');
        $this->assertSame(
            \Illuminate\Http\JsonResponse::class,
            $ref->getReturnType()?->getName()
        );
    }

    // -------------------------------------------------------------------------
    // Controller — getProfitAndLoss
    // -------------------------------------------------------------------------

    public function test_controller_has_get_profit_and_loss_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingController::class, 'getProfitAndLoss'),
            'AccountingController must expose a public getProfitAndLoss() method.'
        );
    }

    public function test_controller_get_profit_and_loss_is_public(): void
    {
        $ref = new ReflectionMethod(AccountingController::class, 'getProfitAndLoss');
        $this->assertTrue($ref->isPublic());
    }

    public function test_controller_get_profit_and_loss_accepts_int_id(): void
    {
        $ref    = new ReflectionMethod(AccountingController::class, 'getProfitAndLoss');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()?->getName());
    }

    public function test_controller_get_profit_and_loss_returns_json_response(): void
    {
        $ref = new ReflectionMethod(AccountingController::class, 'getProfitAndLoss');
        $this->assertSame(
            \Illuminate\Http\JsonResponse::class,
            $ref->getReturnType()?->getName()
        );
    }

    // -------------------------------------------------------------------------
    // Service — matching methods exist and are accessible
    // -------------------------------------------------------------------------

    public function test_service_has_get_trial_balance_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'getTrialBalance'),
            'AccountingService must expose a public getTrialBalance() method.'
        );
    }

    public function test_service_has_get_profit_and_loss_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'getProfitAndLoss'),
            'AccountingService must expose a public getProfitAndLoss() method.'
        );
    }
}
