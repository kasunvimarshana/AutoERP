<?php
namespace Modules\Sales\Application\UseCases;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Sales\Domain\Contracts\PriceListRepositoryInterface;
use Modules\Sales\Domain\Enums\PricingStrategy;
use Modules\Sales\Domain\Events\PriceListItemAdded;

class AddPriceListItemUseCase
{
    public function __construct(private PriceListRepositoryInterface $repo) {}

    public function execute(string $priceListId, array $data): object
    {
        $priceList = $this->repo->findById($priceListId);
        if (!$priceList) {
            throw new ModelNotFoundException('Price list not found.');
        }

        $strategy = $data['strategy'] ?? PricingStrategy::Flat->value;
        $allowedStrategies = array_column(PricingStrategy::cases(), 'value');
        if (!in_array($strategy, $allowedStrategies, true)) {
            throw new DomainException('Invalid pricing strategy.');
        }

        $amount = $data['amount'] ?? '0';
        if (bccomp($amount, '0', 8) <= 0) {
            throw new DomainException('Price list item amount must be greater than zero.');
        }

        if ($strategy === PricingStrategy::PercentageDiscount->value && bccomp($amount, '100', 8) > 0) {
            throw new DomainException('Percentage discount cannot exceed 100.');
        }

        $minQty = $data['min_qty'] ?? '1';
        if (bccomp($minQty, '0', 8) <= 0) {
            throw new DomainException('Minimum quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($priceListId, $data) {
            $item = $this->repo->addItem(array_merge($data, [
                'price_list_id' => $priceListId,
                'tenant_id'     => $data['tenant_id'],
            ]));
            Event::dispatch(new PriceListItemAdded($priceListId, $item->id));
            return $item;
        });
    }
}
