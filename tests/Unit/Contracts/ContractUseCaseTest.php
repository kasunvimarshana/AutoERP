<?php

namespace Tests\Unit\Contracts;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Contracts\Application\UseCases\ActivateContractUseCase;
use Modules\Contracts\Application\UseCases\CreateContractUseCase;
use Modules\Contracts\Application\UseCases\TerminateContractUseCase;
use Modules\Contracts\Domain\Contracts\ContractRepositoryInterface;
use Modules\Contracts\Domain\Events\ContractActivated;
use Modules\Contracts\Domain\Events\ContractCreated;
use Modules\Contracts\Domain\Events\ContractTerminated;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Contracts module use cases.
 *
 * Covers contract creation with BCMath total_value and ContractCreated event dispatch,
 * activation lifecycle (draft-only guard), and termination lifecycle
 * (already-terminated/expired guard), along with domain event assertions.
 */
class ContractUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeContract(string $status = 'draft'): object
    {
        return (object) [
            'id'          => 'contract-uuid-1',
            'tenant_id'   => 'tenant-uuid-1',
            'title'       => 'Managed Services Agreement',
            'party_name'  => 'Acme Corp',
            'total_value' => '5000.00000000',
            'currency'    => 'USD',
            'status'      => $status,
        ];
    }

    // -------------------------------------------------------------------------
    // CreateContractUseCase
    // -------------------------------------------------------------------------

    public function test_create_contract_sets_status_draft_and_dispatches_event(): void
    {
        $contract     = $this->makeContract('draft');
        $contractRepo = Mockery::mock(ContractRepositoryInterface::class);

        $contractRepo->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                return $data['status'] === 'draft'
                    && $data['total_value'] === '5000.00000000';
            })
            ->andReturn($contract);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof ContractCreated
                && $event->title === 'Managed Services Agreement');

        $useCase = new CreateContractUseCase($contractRepo);
        $result  = $useCase->execute([
            'tenant_id'   => 'tenant-uuid-1',
            'title'       => 'Managed Services Agreement',
            'party_name'  => 'Acme Corp',
            'total_value' => '5000',
        ]);

        $this->assertSame('draft', $result->status);
    }

    public function test_create_contract_normalises_total_value_with_bcmath(): void
    {
        $normalised   = (object) [
            'id'          => 'contract-uuid-2',
            'tenant_id'   => 'tenant-uuid-1',
            'title'       => 'Contract',
            'party_name'  => 'Partner Ltd',
            'total_value' => '1000.00000000',
            'currency'    => 'USD',
            'status'      => 'draft',
        ];
        $contractRepo = Mockery::mock(ContractRepositoryInterface::class);

        $contractRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['total_value'] === '1000.00000000')
            ->andReturn($normalised);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->once();

        $useCase = new CreateContractUseCase($contractRepo);
        $result  = $useCase->execute([
            'tenant_id'   => 'tenant-uuid-1',
            'title'       => 'Contract',
            'party_name'  => 'Partner Ltd',
            'total_value' => '1000',
        ]);

        $this->assertSame('1000.00000000', $result->total_value);
    }

    // -------------------------------------------------------------------------
    // ActivateContractUseCase
    // -------------------------------------------------------------------------

    public function test_activate_throws_when_contract_not_found(): void
    {
        $contractRepo = Mockery::mock(ContractRepositoryInterface::class);
        $contractRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new ActivateContractUseCase($contractRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_activate_throws_when_contract_is_already_active(): void
    {
        $contractRepo = Mockery::mock(ContractRepositoryInterface::class);
        $contractRepo->shouldReceive('findById')->andReturn($this->makeContract('active'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new ActivateContractUseCase($contractRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/only draft/i');

        $useCase->execute('contract-uuid-1');
    }

    public function test_activate_transitions_draft_to_active_and_dispatches_event(): void
    {
        $contract = $this->makeContract('draft');
        $active   = (object) array_merge((array) $contract, ['status' => 'active']);

        $contractRepo = Mockery::mock(ContractRepositoryInterface::class);
        $contractRepo->shouldReceive('findById')->andReturn($contract);
        $contractRepo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'active' && isset($data['activated_at']))
            ->once()
            ->andReturn($active);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof ContractActivated
                && $event->contractId === 'contract-uuid-1');

        $useCase = new ActivateContractUseCase($contractRepo);
        $result  = $useCase->execute('contract-uuid-1');

        $this->assertSame('active', $result->status);
    }

    // -------------------------------------------------------------------------
    // TerminateContractUseCase
    // -------------------------------------------------------------------------

    public function test_terminate_throws_when_contract_not_found(): void
    {
        $contractRepo = Mockery::mock(ContractRepositoryInterface::class);
        $contractRepo->shouldReceive('findById')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new TerminateContractUseCase($contractRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_terminate_throws_when_already_terminated(): void
    {
        $contractRepo = Mockery::mock(ContractRepositoryInterface::class);
        $contractRepo->shouldReceive('findById')->andReturn($this->makeContract('terminated'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new TerminateContractUseCase($contractRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/already terminated or expired/i');

        $useCase->execute('contract-uuid-1');
    }

    public function test_terminate_throws_when_already_expired(): void
    {
        $contractRepo = Mockery::mock(ContractRepositoryInterface::class);
        $contractRepo->shouldReceive('findById')->andReturn($this->makeContract('expired'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new TerminateContractUseCase($contractRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/already terminated or expired/i');

        $useCase->execute('contract-uuid-1');
    }

    public function test_terminate_transitions_to_terminated_and_dispatches_event(): void
    {
        $contract    = $this->makeContract('active');
        $terminated  = (object) array_merge((array) $contract, ['status' => 'terminated']);

        $contractRepo = Mockery::mock(ContractRepositoryInterface::class);
        $contractRepo->shouldReceive('findById')->andReturn($contract);
        $contractRepo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'terminated'
                && $data['termination_reason'] === 'Project cancelled.')
            ->once()
            ->andReturn($terminated);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof ContractTerminated
                && $event->contractId === 'contract-uuid-1'
                && $event->reason === 'Project cancelled.');

        $useCase = new TerminateContractUseCase($contractRepo);
        $result  = $useCase->execute('contract-uuid-1', 'Project cancelled.');

        $this->assertSame('terminated', $result->status);
    }
}
