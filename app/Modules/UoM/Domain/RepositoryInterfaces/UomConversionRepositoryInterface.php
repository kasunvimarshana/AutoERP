<?php
namespace Modules\UoM\Domain\RepositoryInterfaces;
use Modules\UoM\Domain\Entities\UomConversion;

interface UomConversionRepositoryInterface
{
    public function findById(int $id): ?UomConversion;
    public function findByFromTo(int $fromId, int $toId, ?int $productId = null): ?UomConversion;
    public function create(array $data): UomConversion;
    public function update(UomConversion $conversion, array $data): UomConversion;
    public function delete(UomConversion $conversion): bool;
}
