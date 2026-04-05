<?php
declare(strict_types=1);
namespace Tests\Unit;

use Modules\Contract\Application\Services\ContractService;
use Modules\Contract\Domain\Entities\Contract;
use Modules\Contract\Domain\Entities\ContractLine;
use Modules\Contract\Domain\Exceptions\ContractNotFoundException;
use Modules\Contract\Domain\RepositoryInterfaces\ContractRepositoryInterface;
use PHPUnit\Framework\TestCase;

class ContractModuleTest extends TestCase
{
    private function makeContract(
        ?int $id = 1,
        string $status = Contract::STATUS_DRAFT,
    ): Contract {
        return new Contract(
            $id, 1, 'CTR-2024-001', Contract::TYPE_CUSTOMER, $status,
            'Annual Support Agreement', null, 2, null, null,
            12000.0, 'USD',
            new \DateTimeImmutable('2024-01-01'),
            new \DateTimeImmutable('2024-12-31'),
            'Net 30', false, null, null, null,
        );
    }

    private function makeLine(?int $id = 1): ContractLine
    {
        return new ContractLine(
            $id, 1, 'Monthly support fee', null,
            12.0, 1000.0, 12000.0,
            new \DateTimeImmutable('2024-12-31'),
            false, null, null,
        );
    }

    // ── Contract entity tests ────────────────────────────────────────────
    public function testContractCreation(): void
    {
        $c = $this->makeContract();
        $this->assertSame('CTR-2024-001', $c->getContractNumber());
        $this->assertSame(Contract::STATUS_DRAFT, $c->getStatus());
        $this->assertSame(12000.0, $c->getValue());
        $this->assertFalse($c->isActive());
    }

    public function testContractActivate(): void
    {
        $c = $this->makeContract();
        $c->activate();
        $this->assertTrue($c->isActive());
        $this->assertSame(Contract::STATUS_ACTIVE, $c->getStatus());
    }

    public function testContractActivateNonDraftThrows(): void
    {
        $this->expectException(\DomainException::class);
        $c = $this->makeContract(status: Contract::STATUS_ACTIVE);
        $c->activate();
    }

    public function testContractTerminate(): void
    {
        $c = $this->makeContract(status: Contract::STATUS_ACTIVE);
        $c->terminate();
        $this->assertSame(Contract::STATUS_TERMINATED, $c->getStatus());
        $this->assertNotNull($c->getTerminatedAt());
    }

    public function testContractTerminateAlreadyTerminatedThrows(): void
    {
        $this->expectException(\DomainException::class);
        $c = $this->makeContract(status: Contract::STATUS_TERMINATED);
        $c->terminate();
    }

    public function testContractIsExpired(): void
    {
        $c = $this->makeContract(status: Contract::STATUS_ACTIVE);
        $this->assertTrue($c->isExpired(new \DateTimeImmutable('2025-01-01')));
        $this->assertFalse($c->isExpired(new \DateTimeImmutable('2024-06-01')));
    }

    public function testContractAutoRenewDefault(): void
    {
        $this->assertFalse($this->makeContract()->isAutoRenew());
    }

    // ── ContractLine entity tests ─────────────────────────────────────────
    public function testContractLineCreation(): void
    {
        $line = $this->makeLine();
        $this->assertSame(1, $line->getContractId());
        $this->assertEqualsWithDelta(12.0, $line->getQuantity(), 0.001);
        $this->assertEqualsWithDelta(1000.0, $line->getUnitPrice(), 0.001);
        $this->assertEqualsWithDelta(12000.0, $line->getTotalPrice(), 0.001);
        $this->assertFalse($line->isDelivered());
    }

    public function testContractLineMarkDelivered(): void
    {
        $line = $this->makeLine();
        $line->markDelivered();
        $this->assertTrue($line->isDelivered());
        $this->assertNotNull($line->getDeliveredAt());
    }

    public function testContractLineMarkDeliveredTwiceThrows(): void
    {
        $this->expectException(\DomainException::class);
        $line = $this->makeLine();
        $line->markDelivered();
        $line->markDelivered();
    }

    // ── ContractService tests ─────────────────────────────────────────────
    public function testContractServiceFindById(): void
    {
        $contract = $this->makeContract();
        $repo     = $this->createMock(ContractRepositoryInterface::class);
        $repo->method('findById')->willReturn($contract);
        $result = (new ContractService($repo))->findById(1);
        $this->assertSame('CTR-2024-001', $result->getContractNumber());
    }

    public function testContractServiceFindNotFoundThrows(): void
    {
        $repo = $this->createMock(ContractRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);
        $this->expectException(ContractNotFoundException::class);
        (new ContractService($repo))->findById(99);
    }

    public function testContractServiceCreate(): void
    {
        $contract = $this->makeContract();
        $repo     = $this->createMock(ContractRepositoryInterface::class);
        $repo->method('create')->willReturn($contract);
        $result = (new ContractService($repo))->create(['contract_number' => 'CTR-2024-001']);
        $this->assertSame(1, $result->getId());
    }

    public function testContractServiceActivate(): void
    {
        $draft  = $this->makeContract();
        $active = $this->makeContract(status: Contract::STATUS_ACTIVE);
        $repo   = $this->createMock(ContractRepositoryInterface::class);
        $repo->method('findById')->willReturn($draft);
        $repo->method('update')->willReturn($active);
        $result = (new ContractService($repo))->activate(1);
        $this->assertTrue($result->isActive());
    }

    public function testContractServiceTerminate(): void
    {
        $active     = $this->makeContract(status: Contract::STATUS_ACTIVE);
        $terminated = $this->makeContract(status: Contract::STATUS_TERMINATED);
        $repo       = $this->createMock(ContractRepositoryInterface::class);
        $repo->method('findById')->willReturn($active);
        $repo->method('update')->willReturn($terminated);
        $result = (new ContractService($repo))->terminate(1, 'Changed mind');
        $this->assertSame(Contract::STATUS_TERMINATED, $result->getStatus());
    }

    public function testContractServiceFindExpiring(): void
    {
        $contract = $this->makeContract(status: Contract::STATUS_ACTIVE);
        $repo     = $this->createMock(ContractRepositoryInterface::class);
        $repo->method('findExpiring')->willReturn([$contract]);
        $results = (new ContractService($repo))->findExpiring(1, 30);
        $this->assertCount(1, $results);
    }

    public function testContractServiceFindAll(): void
    {
        $repo = $this->createMock(ContractRepositoryInterface::class);
        $repo->method('findAllByTenant')->willReturn([$this->makeContract(), $this->makeContract(2)]);
        $results = (new ContractService($repo))->findAllByTenant(1);
        $this->assertCount(2, $results);
    }

    // ── Exception messages ────────────────────────────────────────────────
    public function testContractNotFoundExceptionMessage(): void
    {
        $this->assertStringContainsString('77', (new ContractNotFoundException(77))->getMessage());
    }
}
