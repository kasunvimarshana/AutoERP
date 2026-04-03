<?php

declare(strict_types=1);

namespace Modules\UoM\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\UoM\Application\Contracts\DeleteUomConversionServiceInterface;
use Modules\UoM\Domain\Events\UomConversionDeleted;
use Modules\UoM\Domain\Exceptions\UomConversionNotFoundException;
use Modules\UoM\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;

class DeleteUomConversionService extends BaseService implements DeleteUomConversionServiceInterface
{
    private UomConversionRepositoryInterface $conversionRepository;

    public function __construct(UomConversionRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->conversionRepository = $repository;
    }

    protected function handle(array $data): bool
    {
        $id         = $data['id'];
        $conversion = $this->conversionRepository->find($id);

        if (! $conversion) {
            throw new UomConversionNotFoundException($id);
        }

        $tenantId = $conversion->getTenantId();
        $deleted  = $this->conversionRepository->delete($id);

        if ($deleted) {
            $this->addEvent(new UomConversionDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
