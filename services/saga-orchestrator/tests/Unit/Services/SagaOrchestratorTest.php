<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\Messaging\MessageBrokerInterface;
use App\Contracts\Repositories\SagaRepositoryInterface;
use App\Domain\Saga\Definitions\CreateOrderSaga;
use App\Domain\Saga\Models\SagaStep;
use App\Domain\Saga\Models\SagaTransaction;
use App\Services\SagaOrchestrator;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

/**
 * Saga Orchestrator Unit Tests
 * Validates state management and compensation logic.
 */
class SagaOrchestratorTest extends TestCase
{
    private SagaOrchestrator $orchestrator;
    private SagaRepositoryInterface $repository;
    private MessageBrokerInterface $broker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(SagaRepositoryInterface::class);
        $this->broker = Mockery::mock(MessageBrokerInterface::class)->shouldIgnoreMissing();
        $logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

        $this->orchestrator = new SagaOrchestrator(
            $this->repository,
            $this->broker,
            $logger
        );

        $this->orchestrator->registerDefinition(new CreateOrderSaga());
    }

    #[Test]
    public function it_throws_exception_for_unknown_saga_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Saga type 'unknown_type' is not registered.");

        $this->orchestrator->start('unknown_type', [], 'tenant-1');
    }

    #[Test]
    public function it_throws_exception_when_saga_not_found(): void
    {
        $this->repository->shouldReceive('findById')
            ->with('non-existent-id')
            ->andReturn(null);

        $this->expectException(\RuntimeException::class);

        $this->orchestrator->getStatus('non-existent-id');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
