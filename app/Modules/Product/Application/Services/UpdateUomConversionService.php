<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\UpdateUomConversionServiceInterface;
use Modules\Product\Application\DTOs\UomConversionData;
use Modules\Product\Domain\Entities\UomConversion;
use Modules\Product\Domain\Exceptions\UomConversionNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;

class UpdateUomConversionService extends BaseService implements UpdateUomConversionServiceInterface
{
    public function __construct(private readonly UomConversionRepositoryInterface $uomConversionRepository)
    {
        parent::__construct($uomConversionRepository);
    }

    protected function handle(array $data): UomConversion
    {
        $id = (int) ($data['id'] ?? 0);
        $uomConversion = $this->uomConversionRepository->find($id);

        if (! $uomConversion) {
            throw new UomConversionNotFoundException($id);
        }

        $dto = UomConversionData::fromArray($data);

        $uomConversion->update(
            fromUomId: $dto->from_uom_id,
            toUomId: $dto->to_uom_id,
            factor: $dto->factor,
        );

        return $this->uomConversionRepository->save($uomConversion);
    }
}
