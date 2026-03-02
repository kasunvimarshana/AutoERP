<?php

declare(strict_types=1);

namespace Modules\Accounting\Tests\Unit;

use Modules\Accounting\Application\Services\AccountingService;
use Modules\Accounting\Domain\Entities\AutoPostingRule;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit tests for AccountingService auto-posting rule methods.
 *
 * Verifies method existence, visibility, parameter signatures, and return
 * types for listAutoPostingRules, createAutoPostingRule, updateAutoPostingRule,
 * and deleteAutoPostingRule. Pure-PHP — no database or Laravel bootstrap required.
 */
class AccountingServiceAutoPostingRuleTest extends TestCase
{
    // -------------------------------------------------------------------------
    // listAutoPostingRules — method existence and signature
    // -------------------------------------------------------------------------

    public function test_list_auto_posting_rules_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'listAutoPostingRules'),
            'AccountingService must expose a public listAutoPostingRules() method.'
        );
    }

    public function test_list_auto_posting_rules_is_public(): void
    {
        $reflection = new ReflectionMethod(AccountingService::class, 'listAutoPostingRules');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_list_auto_posting_rules_accepts_no_parameters(): void
    {
        $reflection = new ReflectionMethod(AccountingService::class, 'listAutoPostingRules');

        $this->assertCount(0, $reflection->getParameters());
    }

    public function test_list_auto_posting_rules_is_not_static(): void
    {
        $reflection = new ReflectionMethod(AccountingService::class, 'listAutoPostingRules');

        $this->assertFalse($reflection->isStatic());
    }

    // -------------------------------------------------------------------------
    // createAutoPostingRule — method existence and signature
    // -------------------------------------------------------------------------

    public function test_create_auto_posting_rule_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'createAutoPostingRule'),
            'AccountingService must expose a public createAutoPostingRule() method.'
        );
    }

    public function test_create_auto_posting_rule_is_public(): void
    {
        $reflection = new ReflectionMethod(AccountingService::class, 'createAutoPostingRule');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_create_auto_posting_rule_accepts_array_data_param(): void
    {
        $reflection = new ReflectionMethod(AccountingService::class, 'createAutoPostingRule');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('data', $params[0]->getName());
        $this->assertSame('array', (string) $params[0]->getType());
    }

    public function test_create_auto_posting_rule_return_type_is_auto_posting_rule(): void
    {
        $reflection = new ReflectionMethod(AccountingService::class, 'createAutoPostingRule');

        $this->assertStringContainsString('AutoPostingRule', (string) $reflection->getReturnType());
    }

    // -------------------------------------------------------------------------
    // updateAutoPostingRule — method existence and signature
    // -------------------------------------------------------------------------

    public function test_update_auto_posting_rule_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'updateAutoPostingRule'),
            'AccountingService must expose a public updateAutoPostingRule() method.'
        );
    }

    public function test_update_auto_posting_rule_is_public(): void
    {
        $reflection = new ReflectionMethod(AccountingService::class, 'updateAutoPostingRule');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_update_auto_posting_rule_accepts_id_and_data(): void
    {
        $reflection = new ReflectionMethod(AccountingService::class, 'updateAutoPostingRule');
        $params     = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
        $this->assertSame('data', $params[1]->getName());
        $this->assertSame('array', (string) $params[1]->getType());
    }

    public function test_update_auto_posting_rule_return_type_is_auto_posting_rule(): void
    {
        $reflection = new ReflectionMethod(AccountingService::class, 'updateAutoPostingRule');

        $this->assertStringContainsString('AutoPostingRule', (string) $reflection->getReturnType());
    }

    // -------------------------------------------------------------------------
    // deleteAutoPostingRule — method existence and signature
    // -------------------------------------------------------------------------

    public function test_delete_auto_posting_rule_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'deleteAutoPostingRule'),
            'AccountingService must expose a public deleteAutoPostingRule() method.'
        );
    }

    public function test_delete_auto_posting_rule_is_public(): void
    {
        $reflection = new ReflectionMethod(AccountingService::class, 'deleteAutoPostingRule');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_delete_auto_posting_rule_accepts_int_id_param(): void
    {
        $reflection = new ReflectionMethod(AccountingService::class, 'deleteAutoPostingRule');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
    }

    public function test_delete_auto_posting_rule_return_type_is_bool(): void
    {
        $reflection = new ReflectionMethod(AccountingService::class, 'deleteAutoPostingRule');

        $this->assertSame('bool', (string) $reflection->getReturnType());
    }

    // -------------------------------------------------------------------------
    // AutoPostingRule entity — structural compliance
    // -------------------------------------------------------------------------

    public function test_auto_posting_rule_entity_class_exists(): void
    {
        $this->assertTrue(
            class_exists(AutoPostingRule::class),
            'AutoPostingRule entity class must exist.'
        );
    }

    public function test_auto_posting_rule_uses_has_tenant_trait(): void
    {
        $this->assertContains(
            \Modules\Core\Domain\Traits\HasTenant::class,
            class_uses_recursive(AutoPostingRule::class)
        );
    }

    public function test_auto_posting_rule_has_expected_fillable_fields(): void
    {
        $rule     = new AutoPostingRule();
        $fillable = $rule->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('event_type', $fillable);
        $this->assertContains('debit_account_id', $fillable);
        $this->assertContains('credit_account_id', $fillable);
        $this->assertContains('is_active', $fillable);
    }
}
