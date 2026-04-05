<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Modules\POS\Application\Services\CloseSessionService;
use Modules\POS\Application\Services\OpenSessionService;
use Modules\POS\Application\Services\ProcessPosTransactionService;
use Modules\POS\Application\Services\VoidPosTransactionService;
use Modules\POS\Domain\Entities\PosSession;
use Modules\POS\Domain\Entities\PosTerminal;
use Modules\POS\Domain\Entities\PosTransaction;
use Modules\POS\Domain\Entities\PosTransactionLine;
use Modules\POS\Domain\Exceptions\PosSessionNotFoundException;
use Modules\POS\Domain\Exceptions\PosTerminalNotFoundException;
use Modules\POS\Domain\Exceptions\PosTransactionNotFoundException;
use Modules\POS\Domain\RepositoryInterfaces\PosSessionRepositoryInterface;
use Modules\POS\Domain\RepositoryInterfaces\PosTerminalRepositoryInterface;
use Modules\POS\Domain\RepositoryInterfaces\PosTransactionRepositoryInterface;

class POSModuleTest extends TestCase
{
    // ──────────────────────────────────────────────────────────────────────
    // Factory helpers
    // ──────────────────────────────────────────────────────────────────────

    private function makeTerminal(int $id = 1, bool $active = true): PosTerminal
    {
        return new PosTerminal($id, 1, 1, 'Main Register', 'REG01', null, $active, null, null);
    }

    private function makeSession(int $id = 1, string $status = PosSession::STATUS_OPEN): PosSession
    {
        return new PosSession(
            $id, 1, 1, 5,
            $status, 200.0, null, null,
            new \DateTimeImmutable(), null, null, null,
        );
    }

    private function makeLine(): PosTransactionLine
    {
        return new PosTransactionLine(1, 1, 10, null, 'Widget', 'WDG-001', 2.0, 25.0, 0.0, 5.0, 55.0, null);
    }

    private function makeTransaction(int $id = 1, string $status = PosTransaction::STATUS_COMPLETED): PosTransaction
    {
        return new PosTransaction(
            $id, 1, 1, null,
            PosTransaction::TYPE_SALE, $status, 'USD',
            50.0, 5.0, 0.0, 55.0,
            'cash', 55.0, 0.0, null, null,
            [$this->makeLine()],
            null, null,
        );
    }

    private function mockTerminalRepo(): MockObject&PosTerminalRepositoryInterface
    {
        return $this->createMock(PosTerminalRepositoryInterface::class);
    }

    private function mockSessionRepo(): MockObject&PosSessionRepositoryInterface
    {
        return $this->createMock(PosSessionRepositoryInterface::class);
    }

    private function mockTransactionRepo(): MockObject&PosTransactionRepositoryInterface
    {
        return $this->createMock(PosTransactionRepositoryInterface::class);
    }

    // ──────────────────────────────────────────────────────────────────────
    // PosTerminal entity tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_pos_terminal_creation(): void
    {
        $t = $this->makeTerminal(1);
        $this->assertEquals(1, $t->getId());
        $this->assertEquals('Main Register', $t->getName());
        $this->assertEquals('REG01', $t->getCode());
        $this->assertEquals(1, $t->getWarehouseId());
        $this->assertTrue($t->isActive());
    }

    public function test_pos_terminal_activate_deactivate(): void
    {
        $t = $this->makeTerminal(1, false);
        $this->assertFalse($t->isActive());
        $t->activate();
        $this->assertTrue($t->isActive());
        $t->deactivate();
        $this->assertFalse($t->isActive());
    }

    // ──────────────────────────────────────────────────────────────────────
    // PosSession entity tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_pos_session_creation(): void
    {
        $s = $this->makeSession();
        $this->assertEquals(1, $s->getId());
        $this->assertTrue($s->isOpen());
        $this->assertEquals(200.0, $s->getOpeningBalance());
        $this->assertNull($s->getClosingBalance());
    }

    public function test_pos_session_close(): void
    {
        $s = $this->makeSession(1, PosSession::STATUS_OPEN);
        $s->close(250.0, 'End of day');
        $this->assertEquals(PosSession::STATUS_CLOSED, $s->getStatus());
        $this->assertEquals(250.0, $s->getClosingBalance());
        $this->assertNotNull($s->getClosedAt());
    }

    public function test_pos_session_close_already_closed_throws(): void
    {
        $s = $this->makeSession(1, PosSession::STATUS_CLOSED);
        $this->expectException(\DomainException::class);
        $s->close(100.0, null);
    }

    // ──────────────────────────────────────────────────────────────────────
    // PosTransaction entity tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_pos_transaction_creation(): void
    {
        $tx = $this->makeTransaction();
        $this->assertEquals(PosTransaction::TYPE_SALE, $tx->getType());
        $this->assertEquals(PosTransaction::STATUS_COMPLETED, $tx->getStatus());
        $this->assertEquals('USD', $tx->getCurrency());
        $this->assertEquals(55.0, $tx->getTotal());
        $this->assertCount(1, $tx->getLines());
    }

    public function test_pos_transaction_void(): void
    {
        $tx = $this->makeTransaction(1, PosTransaction::STATUS_COMPLETED);
        $tx->void();
        $this->assertEquals(PosTransaction::STATUS_VOIDED, $tx->getStatus());
    }

    public function test_pos_transaction_void_non_completed_throws(): void
    {
        $tx = $this->makeTransaction(1, PosTransaction::STATUS_PENDING);
        $this->expectException(\DomainException::class);
        $tx->void();
    }

    public function test_pos_transaction_complete(): void
    {
        $tx = $this->makeTransaction(1, PosTransaction::STATUS_PENDING);
        $tx->complete();
        $this->assertEquals(PosTransaction::STATUS_COMPLETED, $tx->getStatus());
    }

    public function test_pos_transaction_complete_already_completed_throws(): void
    {
        $tx = $this->makeTransaction(1, PosTransaction::STATUS_COMPLETED);
        $this->expectException(\DomainException::class);
        $tx->complete();
    }

    // ──────────────────────────────────────────────────────────────────────
    // PosTransactionLine entity tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_pos_transaction_line_creation(): void
    {
        $line = $this->makeLine();
        $this->assertEquals(10, $line->getProductId());
        $this->assertEquals('Widget', $line->getProductName());
        $this->assertEquals(2.0, $line->getQuantity());
        $this->assertEquals(25.0, $line->getUnitPrice());
        $this->assertEquals(55.0, $line->getLineTotal());
    }

    // ──────────────────────────────────────────────────────────────────────
    // OpenSessionService tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_open_session_success(): void
    {
        $terminalRepo = $this->mockTerminalRepo();
        $sessionRepo  = $this->mockSessionRepo();
        $terminal     = $this->makeTerminal(1, true);
        $session      = $this->makeSession(1);

        $terminalRepo->method('findById')->with(1)->willReturn($terminal);
        $sessionRepo->method('findOpenByTerminal')->with(1)->willReturn(null);
        $sessionRepo->expects($this->once())->method('create')->willReturn($session);

        $service = new OpenSessionService($terminalRepo, $sessionRepo);
        $result  = $service->open(1, 5, 200.0);
        $this->assertTrue($result->isOpen());
    }

    public function test_open_session_terminal_not_found_throws(): void
    {
        $terminalRepo = $this->mockTerminalRepo();
        $sessionRepo  = $this->mockSessionRepo();
        $terminalRepo->method('findById')->willReturn(null);

        $this->expectException(PosTerminalNotFoundException::class);
        (new OpenSessionService($terminalRepo, $sessionRepo))->open(99, 5, 0.0);
    }

    public function test_open_session_inactive_terminal_throws(): void
    {
        $terminalRepo = $this->mockTerminalRepo();
        $sessionRepo  = $this->mockSessionRepo();
        $terminalRepo->method('findById')->willReturn($this->makeTerminal(1, false));

        $this->expectException(\DomainException::class);
        (new OpenSessionService($terminalRepo, $sessionRepo))->open(1, 5, 0.0);
    }

    public function test_open_session_already_open_throws(): void
    {
        $terminalRepo = $this->mockTerminalRepo();
        $sessionRepo  = $this->mockSessionRepo();
        $terminalRepo->method('findById')->willReturn($this->makeTerminal(1, true));
        $sessionRepo->method('findOpenByTerminal')->willReturn($this->makeSession(7));

        $this->expectException(\DomainException::class);
        (new OpenSessionService($terminalRepo, $sessionRepo))->open(1, 5, 0.0);
    }

    // ──────────────────────────────────────────────────────────────────────
    // CloseSessionService tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_close_session_success(): void
    {
        $sessionRepo = $this->mockSessionRepo();
        $open    = $this->makeSession(1, PosSession::STATUS_OPEN);
        $closed  = $this->makeSession(1, PosSession::STATUS_CLOSED);
        $sessionRepo->method('findById')->with(1)->willReturn($open);
        $sessionRepo->method('update')->willReturn($closed);

        $result = (new CloseSessionService($sessionRepo))->close(1, 250.0, 'notes');
        $this->assertEquals(PosSession::STATUS_CLOSED, $result->getStatus());
    }

    public function test_close_session_not_found_throws(): void
    {
        $sessionRepo = $this->mockSessionRepo();
        $sessionRepo->method('findById')->willReturn(null);

        $this->expectException(PosSessionNotFoundException::class);
        (new CloseSessionService($sessionRepo))->close(99, 0.0);
    }

    public function test_close_session_already_closed_throws(): void
    {
        $sessionRepo = $this->mockSessionRepo();
        $sessionRepo->method('findById')->willReturn($this->makeSession(1, PosSession::STATUS_CLOSED));

        $this->expectException(\DomainException::class);
        (new CloseSessionService($sessionRepo))->close(1, 0.0);
    }

    // ──────────────────────────────────────────────────────────────────────
    // ProcessPosTransactionService tests
    // ──────────────────────────────────────────────────────────────────────

    private function makeSaleData(): array
    {
        return [
            'session_id'     => 1,
            'customer_id'    => null,
            'type'           => PosTransaction::TYPE_SALE,
            'currency'       => 'USD',
            'payment_method' => 'cash',
            'amount_tendered' => 60.0,
            'lines' => [
                [
                    'product_id'      => 10,
                    'variant_id'      => null,
                    'product_name'    => 'Widget',
                    'sku'             => 'WDG-001',
                    'quantity'        => 2.0,
                    'unit_price'      => 25.0,
                    'discount_amount' => 0.0,
                    'tax_amount'      => 5.0,
                ],
            ],
        ];
    }

    public function test_process_pos_transaction_success(): void
    {
        $sessionRepo     = $this->mockSessionRepo();
        $transactionRepo = $this->mockTransactionRepo();
        $session         = $this->makeSession(1, PosSession::STATUS_OPEN);
        $tx              = $this->makeTransaction(1, PosTransaction::STATUS_COMPLETED);

        $sessionRepo->method('findById')->willReturn($session);
        $transactionRepo->method('create')->willReturn($tx);

        $service = new ProcessPosTransactionService($sessionRepo, $transactionRepo);
        $result  = $service->process(1, $this->makeSaleData());
        $this->assertTrue($result->isCompleted());
    }

    public function test_process_pos_transaction_closed_session_throws(): void
    {
        $sessionRepo     = $this->mockSessionRepo();
        $transactionRepo = $this->mockTransactionRepo();
        $sessionRepo->method('findById')->willReturn($this->makeSession(1, PosSession::STATUS_CLOSED));

        $this->expectException(\DomainException::class);
        (new ProcessPosTransactionService($sessionRepo, $transactionRepo))
            ->process(1, $this->makeSaleData());
    }

    public function test_process_pos_transaction_calculates_totals(): void
    {
        $sessionRepo     = $this->mockSessionRepo();
        $transactionRepo = $this->mockTransactionRepo();
        $sessionRepo->method('findById')->willReturn($this->makeSession());

        $capturedData = null;
        $transactionRepo->method('create')->willReturnCallback(function ($data, $lines) use (&$capturedData) {
            $capturedData = $data;
            return $this->makeTransaction();
        });

        $service = new ProcessPosTransactionService($sessionRepo, $transactionRepo);
        $service->process(1, $this->makeSaleData());

        // subtotal = 2 * 25 = 50, tax = 5, discount = 0, total = 55
        $this->assertEqualsWithDelta(50.0, $capturedData['subtotal'], 0.001);
        $this->assertEqualsWithDelta(5.0, $capturedData['tax_total'], 0.001);
        $this->assertEqualsWithDelta(55.0, $capturedData['total'], 0.001);
        $this->assertEqualsWithDelta(5.0, $capturedData['change_given'], 0.001); // tendered 60 - total 55 = 5
    }

    // ──────────────────────────────────────────────────────────────────────
    // VoidPosTransactionService tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_void_pos_transaction_success(): void
    {
        $transactionRepo = $this->mockTransactionRepo();
        $completed  = $this->makeTransaction(1, PosTransaction::STATUS_COMPLETED);
        $voided     = $this->makeTransaction(1, PosTransaction::STATUS_VOIDED);

        $transactionRepo->method('findById')->willReturn($completed);
        $transactionRepo->method('updateStatus')->willReturn($voided);

        $result = (new VoidPosTransactionService($transactionRepo))->void(1);
        $this->assertEquals(PosTransaction::STATUS_VOIDED, $result->getStatus());
    }

    public function test_void_pos_transaction_not_found_throws(): void
    {
        $transactionRepo = $this->mockTransactionRepo();
        $transactionRepo->method('findById')->willReturn(null);

        $this->expectException(PosTransactionNotFoundException::class);
        (new VoidPosTransactionService($transactionRepo))->void(99);
    }

    public function test_void_pos_transaction_already_voided_throws(): void
    {
        $transactionRepo = $this->mockTransactionRepo();
        $transactionRepo->method('findById')->willReturn(
            $this->makeTransaction(1, PosTransaction::STATUS_VOIDED)
        );

        $this->expectException(\DomainException::class);
        (new VoidPosTransactionService($transactionRepo))->void(1);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Exception message tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_pos_terminal_not_found_exception_message(): void
    {
        $ex = new PosTerminalNotFoundException(10);
        $this->assertStringContainsString('10', $ex->getMessage());
    }

    public function test_pos_session_not_found_exception_message(): void
    {
        $ex = new PosSessionNotFoundException(20);
        $this->assertStringContainsString('20', $ex->getMessage());
    }

    public function test_pos_transaction_not_found_exception_message(): void
    {
        $ex = new PosTransactionNotFoundException(30);
        $this->assertStringContainsString('30', $ex->getMessage());
    }

    // ──────────────────────────────────────────────────────────────────────
    // PosTransaction constants
    // ──────────────────────────────────────────────────────────────────────

    public function test_pos_transaction_constants(): void
    {
        $this->assertEquals('sale', PosTransaction::TYPE_SALE);
        $this->assertEquals('refund', PosTransaction::TYPE_REFUND);
        $this->assertEquals('pending', PosTransaction::STATUS_PENDING);
        $this->assertEquals('completed', PosTransaction::STATUS_COMPLETED);
        $this->assertEquals('voided', PosTransaction::STATUS_VOIDED);
    }

    public function test_pos_session_constants(): void
    {
        $this->assertEquals('open', PosSession::STATUS_OPEN);
        $this->assertEquals('closed', PosSession::STATUS_CLOSED);
    }
}
