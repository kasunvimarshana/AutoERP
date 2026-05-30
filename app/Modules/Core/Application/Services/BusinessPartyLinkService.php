<?php

declare(strict_types=1);

namespace Modules\Core\Application\Services;

use DateTimeImmutable;
use Modules\Core\Application\Contracts\Services\BusinessPartyLinkServiceInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\BusinessPartyLinkRepositoryInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;

final class BusinessPartyLinkService implements BusinessPartyLinkServiceInterface
{
    private const ERROR_INVALID = 'BUSINESS_PARTY_LINK_INVALID';
    private const ERROR_NOT_FOUND = 'BUSINESS_PARTY_LINK_NOT_FOUND';
    private const ERROR_CONFLICT = 'BUSINESS_PARTY_LINK_CONFLICT';

    private const PARTY_TYPES = [
        'customer',
        'supplier',
        'provider',
        'employee',
        'user',
        'company',
        'external_party',
        'partner',
        'party',
        'other',
    ];

    private const SYSTEM_PARTY_TYPES = ['customer', 'supplier', 'provider', 'employee', 'user', 'party'];

    private const RELATION_TYPES = [
        'same_party',
        'acts_as',
        'billing_relation',
        'provider_relation',
        'payer_relation',
        'payee_relation',
    ];

    public function __construct(private readonly BusinessPartyLinkRepositoryInterface $links)
    {
    }

    public function list(array $filters): Result
    {
        $tenantId = (int) ($filters['tenant_id'] ?? 0);
        if ($tenantId < 1) {
            return $this->invalid('Tenant is required.');
        }

        $sourceType = trim((string) ($filters['source_party_type'] ?? ''));
        $targetType = trim((string) ($filters['target_party_type'] ?? ''));

        if ($sourceType !== '') {
            if (! in_array($sourceType, self::PARTY_TYPES, true)) {
                return $this->invalid('Invalid source party type.');
            }

            return Result::success($this->links->listForSource(
                $tenantId,
                $sourceType,
                $this->nullableInt($filters['source_party_id'] ?? null),
            ));
        }

        if ($targetType !== '') {
            if (! in_array($targetType, self::PARTY_TYPES, true)) {
                return $this->invalid('Invalid target party type.');
            }

            return Result::success($this->links->listForTarget(
                $tenantId,
                $targetType,
                $this->nullableInt($filters['target_party_id'] ?? null),
            ));
        }

        return $this->invalid('A source or target party filter is required.');
    }

    public function create(array $payload): Result
    {
        $prepared = $this->preparePayload($payload);
        if ($prepared instanceof Result) {
            return $prepared;
        }

        if ($this->links->activeDuplicateExists(
            (int) $prepared['tenant_id'],
            (string) $prepared['source_party_type'],
            $this->nullableInt($prepared['source_party_id'] ?? null),
            (string) $prepared['target_party_type'],
            $this->nullableInt($prepared['target_party_id'] ?? null),
            (string) $prepared['relation_type'],
        )) {
            return Result::failure(new Error(self::ERROR_CONFLICT, 'An active party link already exists for this relation.'));
        }

        return Result::success($this->links->transaction(fn (): DataRecord => $this->links->create($prepared)));
    }

    public function update(int $linkId, array $payload): Result
    {
        $prepared = $this->preparePayload($payload);
        if ($prepared instanceof Result) {
            return $prepared;
        }

        $tenantId = (int) $prepared['tenant_id'];
        if ($this->links->findInTenant($tenantId, $linkId) === null) {
            return $this->notFound('Business party link not found.');
        }

        if ($this->links->activeDuplicateExists(
            $tenantId,
            (string) $prepared['source_party_type'],
            $this->nullableInt($prepared['source_party_id'] ?? null),
            (string) $prepared['target_party_type'],
            $this->nullableInt($prepared['target_party_id'] ?? null),
            (string) $prepared['relation_type'],
            $linkId,
        )) {
            return Result::failure(new Error(self::ERROR_CONFLICT, 'An active party link already exists for this relation.'));
        }

        return Result::success($this->links->transaction(fn (): DataRecord => $this->links->update($linkId, $prepared)));
    }

    public function deactivate(int $tenantId, int $linkId, ?string $endDate = null): Result
    {
        if ($tenantId < 1) {
            return $this->invalid('Tenant is required.');
        }

        $record = $this->links->findInTenant($tenantId, $linkId);
        if ($record === null) {
            return $this->notFound('Business party link not found.');
        }

        $endDate = $endDate === null || trim($endDate) === '' ? now()->toDateString() : $endDate;
        if (! $this->isValidDate($endDate)) {
            return $this->invalid('End date must be a valid date.');
        }

        return Result::success($this->links->transaction(fn (): DataRecord => $this->links->update($linkId, [
            'is_active' => false,
            'end_date' => $endDate,
        ])));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|Result
     */
    private function preparePayload(array $payload): array|Result
    {
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        if ($tenantId < 1) {
            return $this->invalid('Tenant is required.');
        }

        $sourceType = trim((string) ($payload['source_party_type'] ?? ''));
        $targetType = trim((string) ($payload['target_party_type'] ?? ''));
        $relationType = trim((string) ($payload['relation_type'] ?? ''));

        if (! in_array($sourceType, self::PARTY_TYPES, true)) {
            return $this->invalid('Invalid source party type.');
        }

        if (! in_array($targetType, self::PARTY_TYPES, true)) {
            return $this->invalid('Invalid target party type.');
        }

        if (! in_array($relationType, self::RELATION_TYPES, true)) {
            return $this->invalid('Invalid relation type.');
        }

        $sourceId = $this->nullableInt($payload['source_party_id'] ?? null);
        $targetId = $this->nullableInt($payload['target_party_id'] ?? null);

        $sourceCheck = $this->guardPartyReference(
            $sourceType,
            $sourceId,
            $tenantId,
            'source',
            $payload['source_party_name'] ?? null,
        );
        if ($sourceCheck !== null) {
            return $sourceCheck;
        }

        $targetCheck = $this->guardPartyReference(
            $targetType,
            $targetId,
            $tenantId,
            'target',
            $payload['target_party_name'] ?? null,
        );
        if ($targetCheck !== null) {
            return $targetCheck;
        }

        $startDate = $payload['start_date'] ?? null;
        $endDate = $payload['end_date'] ?? null;
        if ($startDate !== null && ! $this->isValidDate((string) $startDate)) {
            return $this->invalid('Start date must be a valid date.');
        }

        if ($endDate !== null && ! $this->isValidDate((string) $endDate)) {
            return $this->invalid('End date must be a valid date.');
        }

        if (is_string($startDate) && is_string($endDate) && new DateTimeImmutable($endDate) < new DateTimeImmutable($startDate)) {
            return $this->invalid('End date must be on or after start date.');
        }

        if (! array_key_exists('row_version', $payload)) {
            $payload['row_version'] = 1;
        }

        $payload['tenant_id'] = $tenantId;
        $payload['source_party_type'] = $sourceType;
        $payload['target_party_type'] = $targetType;
        $payload['relation_type'] = $relationType;
        $payload['source_party_id'] = $sourceId;
        $payload['target_party_id'] = $targetId;
        $payload['is_active'] = (bool) ($payload['is_active'] ?? true);

        return $payload;
    }

    private function guardPartyReference(
        string $partyType,
        ?int $partyId,
        int $tenantId,
        string $side,
        mixed $partyName,
    ): ?Result
    {
        if (in_array($partyType, self::SYSTEM_PARTY_TYPES, true)) {
            if ($partyId === null || $partyId < 1) {
                return $this->invalid(sprintf('%s party id is required for system party types.', ucfirst($side)));
            }

            if (! $this->links->partyReferenceExists($partyType, $partyId, $tenantId)) {
                return $this->invalid(sprintf('%s party id must reference a same-tenant %s record.', ucfirst($side), $partyType));
            }

            return null;
        }

        if ($partyType === 'external_party' && trim((string) $partyName) === '') {
            return $this->invalid(sprintf('%s party name is required for external parties.', ucfirst($side)));
        }

        return null;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function isValidDate(string $value): bool
    {
        return DateTimeImmutable::createFromFormat('Y-m-d', $value) !== false;
    }

    private function invalid(string $message): Result
    {
        return Result::failure(new Error(self::ERROR_INVALID, $message));
    }

    private function notFound(string $message): Result
    {
        return Result::failure(new Error(self::ERROR_NOT_FOUND, $message));
    }
}
