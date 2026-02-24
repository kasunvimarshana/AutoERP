<?php
namespace Modules\Sales\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Sales\Domain\Contracts\PriceListRepositoryInterface;
use Modules\Sales\Domain\Events\PriceListCreated;

class CreatePriceListUseCase
{
    public function __construct(private PriceListRepositoryInterface $repo) {}

    public function execute(array $data): object
    {
        if (empty(trim($data['name'] ?? ''))) {
            throw new DomainException('Price list name must not be empty.');
        }
        if (empty(trim($data['currency_code'] ?? ''))) {
            throw new DomainException('Currency code must not be empty.');
        }
        if (strlen(trim($data['currency_code'])) !== 3) {
            throw new DomainException('Currency code must be exactly 3 characters.');
        }
        if (!empty($data['valid_from']) && !empty($data['valid_to'])) {
            if (strtotime($data['valid_to']) <= strtotime($data['valid_from'])) {
                throw new DomainException('valid_to must be after valid_from.');
            }
        }

        return DB::transaction(function () use ($data) {
            $priceList = $this->repo->create($data);
            Event::dispatch(new PriceListCreated($priceList->id));
            return $priceList;
        });
    }
}
