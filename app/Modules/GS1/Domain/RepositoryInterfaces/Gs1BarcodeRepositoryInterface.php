<?php
namespace Modules\GS1\Domain\RepositoryInterfaces;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\GS1\Domain\Entities\GS1Barcode;

interface GS1BarcodeRepositoryInterface
{
    public function findById(int $id): ?GS1Barcode;
    public function findByGtin(string $gtin): ?GS1Barcode;
    public function findByProduct(int $productId): array;
    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function create(array $data): GS1Barcode;
    public function update(GS1Barcode $barcode, array $data): GS1Barcode;
}
