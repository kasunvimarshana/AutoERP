<?php

declare(strict_types=1);

namespace Modules\Barcode\Application\Services;

use Modules\Barcode\Application\Contracts\ManageBarcodeServiceInterface;
use Modules\Barcode\Domain\Entities\BarcodeDefinition;
use Modules\Barcode\Domain\Exceptions\BarcodeNotFoundException;
use Modules\Barcode\Domain\RepositoryInterfaces\BarcodeDefinitionRepositoryInterface;
use Modules\Barcode\Domain\ValueObjects\BarcodeType;

class ManageBarcodeService implements ManageBarcodeServiceInterface
{
    public function __construct(
        private readonly BarcodeDefinitionRepositoryInterface $definitions,
    ) {}

    public function create(
        int $tenantId,
        string $type,
        string $value,
        ?string $label,
        ?string $entityType,
        ?int $entityId,
        array $metadata = [],
    ): BarcodeDefinition {
        BarcodeType::fromString($type);

        $now = new \DateTime();

        $definition = new BarcodeDefinition(
            null,
            $tenantId,
            $type,
            $value,
            $label,
            $entityType,
            $entityId,
            $metadata,
            true,
            $now,
            $now,
        );

        return $this->definitions->save($definition);
    }

    public function getById(int $id): BarcodeDefinition
    {
        $definition = $this->definitions->findById($id);

        if ($definition === null) {
            throw BarcodeNotFoundException::withId($id);
        }

        return $definition;
    }

    public function getByValue(int $tenantId, string $value): BarcodeDefinition
    {
        $definition = $this->definitions->findByValue($tenantId, $value);

        if ($definition === null) {
            throw BarcodeNotFoundException::withValue($value);
        }

        return $definition;
    }

    /** @return BarcodeDefinition[] */
    public function getForEntity(int $tenantId, string $entityType, int $entityId): array
    {
        return $this->definitions->findByEntity($tenantId, $entityType, $entityId);
    }

    /** @return BarcodeDefinition[] */
    public function listAll(int $tenantId): array
    {
        return $this->definitions->findAll($tenantId);
    }

    public function activate(int $id): BarcodeDefinition
    {
        $definition = $this->getById($id);
        $definition->activate();

        return $this->definitions->save($definition);
    }

    public function deactivate(int $id): BarcodeDefinition
    {
        $definition = $this->getById($id);
        $definition->deactivate();

        return $this->definitions->save($definition);
    }

    public function delete(int $id): void
    {
        $this->getById($id); // throws if not found
        $this->definitions->delete($id);
    }
}
