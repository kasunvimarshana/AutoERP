<?php

declare(strict_types=1);

namespace Modules\Accounting\Tests\Unit;

use Modules\Accounting\Application\Services\AccountingService;
use Modules\Accounting\Interfaces\Http\Controllers\AccountingController;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AccountingController chart-of-accounts endpoints.
 *
 * Verifies that listAccounts() and createAccount() controller methods exist
 * and are publicly accessible. No database or Laravel bootstrap required.
 */
class AccountingControllerAccountTest extends TestCase
{
    public function test_accounting_controller_has_list_accounts_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingController::class, 'listAccounts'),
            'AccountingController must expose a public listAccounts() method.'
        );
    }

    public function test_accounting_controller_has_create_account_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingController::class, 'createAccount'),
            'AccountingController must expose a public createAccount() method.'
        );
    }

    public function test_accounting_service_has_list_accounts_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'listAccounts'),
            'AccountingService must expose a public listAccounts() method.'
        );
    }

    public function test_accounting_service_has_create_account_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'createAccount'),
            'AccountingService must expose a public createAccount() method.'
        );
    }

    public function test_list_accounts_is_public(): void
    {
        $reflection = new \ReflectionMethod(AccountingController::class, 'listAccounts');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_create_account_is_public(): void
    {
        $reflection = new \ReflectionMethod(AccountingController::class, 'createAccount');

        $this->assertTrue($reflection->isPublic());
    }
}
