<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Accounting\Domain\ValueObjects\AccountType;
use Modules\Accounting\Domain\ValueObjects\JournalEntryStatus;
use Modules\Accounting\Domain\ValueObjects\PaymentStatus;
use Modules\Accounting\Domain\ValueObjects\PaymentMethod;
use Modules\Accounting\Domain\Entities\Account;
use Modules\Accounting\Domain\Entities\JournalEntry;
use Modules\Accounting\Domain\Entities\JournalLine;
use Modules\Accounting\Domain\Entities\Payment;
use Modules\Accounting\Domain\Entities\Refund;

class AccountingModuleTest extends TestCase
{
    // --- AccountType ---

    public function test_account_type_valid_returns_five_types(): void
    {
        $this->assertCount(5, AccountType::valid());
    }

    public function test_account_type_from_asset(): void
    {
        $t = AccountType::from(AccountType::ASSET);
        $this->assertSame(AccountType::ASSET, (string) $t);
    }

    public function test_account_type_from_liability(): void
    {
        $t = AccountType::from(AccountType::LIABILITY);
        $this->assertSame(AccountType::LIABILITY, (string) $t);
    }

    public function test_account_type_from_equity(): void
    {
        $t = AccountType::from(AccountType::EQUITY);
        $this->assertSame(AccountType::EQUITY, (string) $t);
    }

    public function test_account_type_from_revenue(): void
    {
        $t = AccountType::from(AccountType::REVENUE);
        $this->assertSame(AccountType::REVENUE, (string) $t);
    }

    public function test_account_type_from_expense(): void
    {
        $t = AccountType::from(AccountType::EXPENSE);
        $this->assertSame(AccountType::EXPENSE, (string) $t);
    }

    public function test_account_type_from_invalid_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        AccountType::from('invalid_type');
    }

    // --- JournalEntryStatus ---

    public function test_journal_entry_status_draft_constant(): void
    {
        $this->assertSame('draft', JournalEntryStatus::DRAFT);
    }

    public function test_journal_entry_status_posted_constant(): void
    {
        $this->assertSame('posted', JournalEntryStatus::POSTED);
    }

    public function test_journal_entry_status_reversed_constant(): void
    {
        $this->assertSame('reversed', JournalEntryStatus::REVERSED);
    }

    // --- PaymentStatus ---

    public function test_payment_status_constants_exist(): void
    {
        $this->assertSame('pending',   PaymentStatus::PENDING);
        $this->assertSame('completed', PaymentStatus::COMPLETED);
        $this->assertSame('failed',    PaymentStatus::FAILED);
        $this->assertSame('refunded',  PaymentStatus::REFUNDED);
        $this->assertSame('cancelled', PaymentStatus::CANCELLED);
    }

    // --- PaymentMethod ---

    public function test_payment_method_constants_exist(): void
    {
        $this->assertSame('cash',          PaymentMethod::CASH);
        $this->assertSame('bank_transfer', PaymentMethod::BANK_TRANSFER);
        $this->assertSame('card',          PaymentMethod::CARD);
        $this->assertSame('cheque',        PaymentMethod::CHEQUE);
        $this->assertSame('credit',        PaymentMethod::CREDIT);
    }

    // --- Account entity ---

    public function test_account_construction_and_accessors(): void
    {
        $account = new Account(
            id: 1,
            tenantId: 10,
            code: '1000',
            name: 'Cash',
            type: AccountType::ASSET,
        );

        $this->assertSame(1,                $account->id);
        $this->assertSame(10,               $account->tenantId);
        $this->assertSame('1000',           $account->code);
        $this->assertSame('Cash',           $account->name);
        $this->assertSame(AccountType::ASSET, $account->type);
    }

    public function test_account_defaults(): void
    {
        $account = new Account(null, 5, 'EXP-001', 'Office Supplies', AccountType::EXPENSE);

        $this->assertNull($account->id);
        $this->assertNull($account->parentId);
        $this->assertSame('USD', $account->currency);
        $this->assertTrue($account->isActive);
    }

    // --- JournalEntry entity ---

    public function test_journal_entry_construction(): void
    {
        $entry = new JournalEntry(
            id: 1,
            tenantId: 2,
            referenceNumber: 'JE-0001',
            status: JournalEntryStatus::DRAFT,
            entryDate: '2025-01-15',
        );

        $this->assertSame(1,              $entry->id);
        $this->assertSame('JE-0001',      $entry->referenceNumber);
        $this->assertSame('draft',        $entry->status);
        $this->assertSame('2025-01-15',   $entry->entryDate);
    }

    public function test_journal_entry_optional_fields_default_null(): void
    {
        $entry = new JournalEntry(null, 3, 'JE-0002', JournalEntryStatus::POSTED, '2025-02-01');

        $this->assertNull($entry->description);
        $this->assertNull($entry->postedBy);
        $this->assertNull($entry->reversedBy);
    }

    // --- JournalLine entity ---

    public function test_journal_line_debit_entry(): void
    {
        $line = new JournalLine(id: 1, journalEntryId: 10, accountId: 5, debit: 100.0, credit: 0.0);

        $this->assertSame(100.0, $line->debit);
        $this->assertSame(0.0,   $line->credit);
    }

    public function test_journal_line_credit_entry(): void
    {
        $line = new JournalLine(id: 2, journalEntryId: 10, accountId: 6, debit: 0.0, credit: 50.0);

        $this->assertSame(0.0,  $line->debit);
        $this->assertSame(50.0, $line->credit);
    }

    public function test_journal_line_throws_when_both_debit_and_credit_positive(): void
    {
        $this->expectException(\DomainException::class);
        new JournalLine(id: null, journalEntryId: 1, accountId: 1, debit: 100.0, credit: 50.0);
    }

    public function test_journal_line_throws_when_debit_negative(): void
    {
        $this->expectException(\DomainException::class);
        new JournalLine(id: null, journalEntryId: 1, accountId: 1, debit: -10.0, credit: 0.0);
    }

    public function test_journal_line_throws_when_credit_negative(): void
    {
        $this->expectException(\DomainException::class);
        new JournalLine(id: null, journalEntryId: 1, accountId: 1, debit: 0.0, credit: -5.0);
    }

    // --- Payment entity ---

    public function test_payment_construction(): void
    {
        $payment = new Payment(
            id: 1,
            tenantId: 1,
            referenceNumber: 'PAY-001',
            status: PaymentStatus::COMPLETED,
            method: PaymentMethod::CASH,
            amount: 250.00,
        );

        $this->assertSame(1,       $payment->id);
        $this->assertSame(250.00,  $payment->amount);
        $this->assertSame('cash',  $payment->method);
        $this->assertSame('completed', $payment->status);
    }

    public function test_payment_defaults(): void
    {
        $payment = new Payment(null, 2, 'PAY-002', PaymentStatus::PENDING, PaymentMethod::BANK_TRANSFER, 99.99);

        $this->assertNull($payment->id);
        $this->assertSame('USD', $payment->currency);
        $this->assertNull($payment->notes);
        $this->assertNull($payment->journalEntryId);
    }

    // --- Refund entity ---

    public function test_refund_construction(): void
    {
        $refund = new Refund(
            id: 1,
            tenantId: 1,
            paymentId: 42,
            amount: 50.00,
        );

        $this->assertSame(1,     $refund->id);
        $this->assertSame(42,    $refund->paymentId);
        $this->assertSame(50.00, $refund->amount);
    }

    public function test_refund_defaults(): void
    {
        $refund = new Refund(null, 1, 10, 25.00);

        $this->assertNull($refund->id);
        $this->assertSame('USD',     $refund->currency);
        $this->assertSame('pending', $refund->status);
        $this->assertNull($refund->reason);
        $this->assertNull($refund->processedBy);
    }
}
