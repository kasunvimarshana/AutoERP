<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Accounting\Domain\Entities\Account;
use Modules\Accounting\Domain\Entities\JournalEntry;
use Modules\Accounting\Domain\Entities\JournalEntryLine;
use Modules\Accounting\Domain\Entities\Payment;
use Modules\Accounting\Domain\Entities\Refund;

class AccountingModuleTest extends TestCase
{
    private function makeAccount(array $overrides = []): Account
    {
        return new Account(
            $overrides['id'] ?? 1, $overrides['tenant_id'] ?? 1,
            $overrides['code'] ?? '1000', $overrides['name'] ?? 'Cash',
            $overrides['type'] ?? 'asset', 'current', null,
            $overrides['balance'] ?? 0.0, 'USD', true, null, null, null
        );
    }
    private function makeJournalEntry(string $status = 'draft', float $debit = 100.0, float $credit = 100.0): JournalEntry
    {
        return new JournalEntry(
            1, 1, 'JE-001', $status, 'Test Entry', 'USD',
            $debit, $credit, null, null, [], null, null, null
        );
    }

    public function test_account_creation(): void
    {
        $acc = $this->makeAccount();
        $this->assertEquals('1000', $acc->getCode());
        $this->assertEquals('asset', $acc->getType());
        $this->assertEquals(0.0, $acc->getBalance());
        $this->assertTrue($acc->isActive());
    }

    public function test_account_debit(): void
    {
        $acc = $this->makeAccount(['balance' => 500.0]);
        $acc->debit(200.0);
        $this->assertEquals(700.0, $acc->getBalance());
    }

    public function test_account_credit(): void
    {
        $acc = $this->makeAccount(['balance' => 500.0]);
        $acc->credit(100.0);
        $this->assertEquals(400.0, $acc->getBalance());
    }

    public function test_journal_entry_is_balanced(): void
    {
        $je = $this->makeJournalEntry('draft', 100.0, 100.0);
        $this->assertTrue($je->isBalanced());
    }

    public function test_journal_entry_is_not_balanced(): void
    {
        $je = $this->makeJournalEntry('draft', 100.0, 90.0);
        $this->assertFalse($je->isBalanced());
    }

    public function test_journal_entry_post(): void
    {
        $je = $this->makeJournalEntry('draft', 100.0, 100.0);
        $je->post();
        $this->assertEquals('posted', $je->getStatus());
        $this->assertNotNull($je->getPostedAt());
    }

    public function test_journal_entry_post_fails_if_not_draft(): void
    {
        $je = $this->makeJournalEntry('posted', 100.0, 100.0);
        $this->expectException(\DomainException::class);
        $je->post();
    }

    public function test_journal_entry_post_fails_if_unbalanced(): void
    {
        $je = $this->makeJournalEntry('draft', 100.0, 90.0);
        $this->expectException(\DomainException::class);
        $je->post();
    }

    // ──────────────────────────────────────────────────────────────────────
    // Payment entity tests
    // ──────────────────────────────────────────────────────────────────────

    private function makePayment(string $status = 'pending'): Payment
    {
        return new Payment(
            1, 1, 'purchase_order', 10, 500.0, 'USD',
            'bank_transfer', $status, 'outbound',
            'PAY-REF-001', 'Supplier payment', new \DateTimeImmutable(), null, null, null
        );
    }

    public function test_payment_creation(): void
    {
        $p = $this->makePayment();
        $this->assertEquals(500.0, $p->getAmount());
        $this->assertEquals('USD', $p->getCurrency());
        $this->assertEquals('bank_transfer', $p->getPaymentMethod());
        $this->assertEquals('pending', $p->getStatus());
        $this->assertTrue($p->isPending());
        $this->assertEquals('outbound', $p->getDirection());
    }

    public function test_payment_complete_transitions_status(): void
    {
        $p = $this->makePayment();
        $p->complete();
        $this->assertEquals('completed', $p->getStatus());
        $this->assertFalse($p->isPending());
    }

    public function test_payment_fail_transitions_status(): void
    {
        $p = $this->makePayment();
        $p->fail();
        $this->assertEquals('failed', $p->getStatus());
    }

    public function test_payment_cancel_transitions_status(): void
    {
        $p = $this->makePayment();
        $p->cancel();
        $this->assertEquals('cancelled', $p->getStatus());
    }

    public function test_payment_inbound_direction(): void
    {
        $p = new Payment(
            2, 1, 'sales_order', 5, 250.0, 'USD',
            'cash', 'pending', 'inbound',
            null, null, new \DateTimeImmutable(), null, null, null
        );
        $this->assertEquals('inbound', $p->getDirection());
    }

    // ──────────────────────────────────────────────────────────────────────
    // Refund entity tests
    // ──────────────────────────────────────────────────────────────────────

    private function makeRefund(string $status = 'pending'): Refund
    {
        return new Refund(
            1, 1, 100, 200.0, 'USD', $status,
            'Defective goods', 'REF-001',
            new \DateTimeImmutable(), null, null, null
        );
    }

    public function test_refund_creation(): void
    {
        $r = $this->makeRefund();
        $this->assertEquals(200.0, $r->getAmount());
        $this->assertEquals('USD', $r->getCurrency());
        $this->assertEquals(100, $r->getOriginalPaymentId());
        $this->assertEquals('pending', $r->getStatus());
        $this->assertEquals('Defective goods', $r->getReason());
        $this->assertEquals('REF-001', $r->getReference());
    }

    public function test_refund_complete_transitions_status(): void
    {
        $r = $this->makeRefund();
        $r->complete();
        $this->assertEquals('completed', $r->getStatus());
    }

    public function test_refund_fail_transitions_status(): void
    {
        $r = $this->makeRefund();
        $r->fail();
        $this->assertEquals('failed', $r->getStatus());
    }

    // ──────────────────────────────────────────────────────────────────────
    // JournalEntryLine entity tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_journal_entry_line_debit(): void
    {
        $line = new JournalEntryLine(1, 1, 100, 500.0, 0.0, 'Cash payment', null);
        $this->assertEquals(500.0, $line->getDebitAmount());
        $this->assertEquals(0.0, $line->getCreditAmount());
        $this->assertEquals(100, $line->getAccountId());
        $this->assertEquals('Cash payment', $line->getDescription());
    }

    public function test_journal_entry_line_credit(): void
    {
        $line = new JournalEntryLine(2, 1, 200, 0.0, 500.0, 'Accounts payable', 'INV-001');
        $this->assertEquals(0.0, $line->getDebitAmount());
        $this->assertEquals(500.0, $line->getCreditAmount());
        $this->assertEquals('INV-001', $line->getReferenceLine());
    }

    // ──────────────────────────────────────────────────────────────────────
    // Account – additional tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_account_credit_decreases_balance(): void
    {
        // credit() always decreases balance in double-entry (debit = increase, credit = decrease)
        $acc = $this->makeAccount(['type' => 'asset', 'balance' => 1000.0]);
        $acc->credit(200.0);
        $this->assertEquals(800.0, $acc->getBalance());
    }

    public function test_account_zero_balance(): void
    {
        $acc = $this->makeAccount(['balance' => 0.0]);
        $this->assertEquals(0.0, $acc->getBalance());
    }

    public function test_account_inactive(): void
    {
        $acc = new Account(
            2, 1, '2000', 'Accounts Payable', 'liability', 'current', null,
            0.0, 'USD', false, null, null, null
        );
        $this->assertFalse($acc->isActive());
        $this->assertEquals('liability', $acc->getType());
    }

    public function test_journal_entry_getters(): void
    {
        $je = $this->makeJournalEntry();
        $this->assertEquals(1, $je->getId());
        $this->assertEquals(1, $je->getTenantId());
        $this->assertEquals('JE-001', $je->getEntryNumber());
        $this->assertEquals('draft', $je->getStatus());
        $this->assertEquals('Test Entry', $je->getDescription());
        $this->assertEquals('USD', $je->getCurrency());
        $this->assertIsArray($je->getLines());
    }

    public function test_journal_entry_is_balanced_with_float_tolerance(): void
    {
        // Small float differences within tolerance should be considered balanced
        $je = new JournalEntry(
            1, 1, 'JE-002', 'draft', 'Float test', 'USD',
            100.00001, 100.0, null, null, [], null, null, null
        );
        $this->assertTrue($je->isBalanced());
    }
}
