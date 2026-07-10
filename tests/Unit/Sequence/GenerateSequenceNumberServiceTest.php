<?php

declare(strict_types=1);

namespace Tests\Unit\Sequence;

use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Sequence\Constants\SequenceErrorCode;
use Modules\Sequence\Repositories\SequenceRepositoryInterface;
use Modules\Sequence\Services\Rules\SequenceDomainService;
use Modules\Sequence\Services\Sequences\GenerateSequenceNumberService;
use RuntimeException;
use Tests\TestCase;

final class GenerateSequenceNumberServiceTest extends TestCase
{
    private const TENANT_ID = 11;

    private const ORGANIZATION_UNIT_ID = 7;

    private const SEQUENCE_ID = 41;

    private const DOCUMENT_TYPE = 'invoice';

    private const PERIOD_VALUE = '2026';

    public function test_first_use_generation_reuses_a_concurrently_inserted_sequence(): void
    {
        $repository = $this->createMock(SequenceRepositoryInterface::class);
        $repository->expects(self::exactly(2))
            ->method('findByScopeForUpdate')
            ->with(
                self::TENANT_ID,
                self::ORGANIZATION_UNIT_ID,
                self::DOCUMENT_TYPE,
                self::PERIOD_VALUE,
            )
            ->willReturnOnConsecutiveCalls(null, $this->sequenceRecord());
        $repository->expects(self::once())
            ->method('insertIfMissing')
            ->with(self::callback(static function (array $attributes): bool {
                return $attributes['tenant_id'] === self::TENANT_ID
                    && $attributes['organization_unit_id'] === self::ORGANIZATION_UNIT_ID
                    && $attributes['document_type'] === self::DOCUMENT_TYPE
                    && $attributes['scope_key'] === self::ORGANIZATION_UNIT_ID.':'.self::PERIOD_VALUE;
            }))
            ->willReturn(false);
        $repository->expects(self::once())
            ->method('updateNextNumberWithVersion')
            ->with(self::SEQUENCE_ID, 1, 2)
            ->willReturn($this->sequenceRecord(nextNumber: 2, rowVersion: 2));

        $result = $this->service($repository)->execute($this->payload());

        self::assertTrue($result->isSuccess());
        self::assertSame([
            'sequence_id' => self::SEQUENCE_ID,
            'tenant_id' => self::TENANT_ID,
            'organization_unit_id' => self::ORGANIZATION_UNIT_ID,
            'document_type' => self::DOCUMENT_TYPE,
            'period_type' => 'yearly',
            'period_value' => self::PERIOD_VALUE,
            'generated_number' => 'INV-00001',
            'consumed_number' => 1,
            'next_number' => 2,
            'row_version' => 2,
        ], $result->valueOrFail());
    }

    public function test_optimistic_update_conflict_returns_a_typed_concurrency_error(): void
    {
        $repository = $this->createMock(SequenceRepositoryInterface::class);
        $repository->method('findByScopeForUpdate')->willReturn($this->sequenceRecord());
        $repository->method('updateNextNumberWithVersion')->willReturn(null);

        $result = $this->service($repository)->execute($this->payload());

        self::assertTrue($result->isFailure());
        self::assertSame(SequenceErrorCode::CONCURRENCY_CONFLICT, $result->errorOrFail()->code);
        self::assertSame(
            'Sequence number generation conflicted with another request.',
            $result->errorOrFail()->message,
        );
    }

    public function test_unexpected_repository_failure_is_not_reported_as_invalid_input(): void
    {
        $repository = $this->createMock(SequenceRepositoryInterface::class);
        $repository->method('findByScopeForUpdate')->willThrowException(new RuntimeException('database unavailable'));

        $result = $this->service($repository)->execute($this->payload());

        self::assertTrue($result->isFailure());
        self::assertSame(SequenceErrorCode::INTERNAL_ERROR, $result->errorOrFail()->code);
        self::assertSame('Unable to generate sequence number.', $result->errorOrFail()->message);
        self::assertStringNotContainsString('database unavailable', $result->errorOrFail()->message);
    }

    private function service(SequenceRepositoryInterface $repository): GenerateSequenceNumberService
    {
        $transactions = $this->createMock(TransactionManagerInterface::class);
        $transactions->method('runInTransaction')
            ->willReturnCallback(static fn (callable $callback): mixed => $callback());

        return new GenerateSequenceNumberService(
            $repository,
            new SequenceDomainService(),
            $transactions,
        );
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'tenant_id' => self::TENANT_ID,
            'organization_unit_id' => self::ORGANIZATION_UNIT_ID,
            'document_type' => self::DOCUMENT_TYPE,
            'period_type' => 'yearly',
            'period_value' => self::PERIOD_VALUE,
            'prefix' => 'INV-',
            'suffix' => '',
            'padding' => 5,
            'next_number' => 1,
            'metadata' => [],
        ];
    }

    private function sequenceRecord(int $nextNumber = 1, int $rowVersion = 1): DataRecord
    {
        return new DataRecord([
            'id' => self::SEQUENCE_ID,
            'tenant_id' => self::TENANT_ID,
            'organization_unit_id' => self::ORGANIZATION_UNIT_ID,
            'document_type' => self::DOCUMENT_TYPE,
            'prefix' => 'INV-',
            'suffix' => '',
            'padding' => 5,
            'next_number' => $nextNumber,
            'period_type' => 'yearly',
            'period_value' => self::PERIOD_VALUE,
            'row_version' => $rowVersion,
        ]);
    }
}
