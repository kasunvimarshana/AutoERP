<?php
namespace Modules\GS1\Domain\RepositoryInterfaces;

use Modules\GS1\Domain\Entities\GS1Label;

interface GS1LabelRepositoryInterface
{
    public function findById(int $id): ?GS1Label;
    public function findByBarcode(int $barcodeId): array;
    public function create(array $data): GS1Label;
}
