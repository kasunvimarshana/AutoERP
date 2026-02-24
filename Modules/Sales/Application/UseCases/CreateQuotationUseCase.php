<?php
namespace Modules\Sales\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Sales\Application\Services\OrderTotalsCalculator;
use Modules\Sales\Domain\Contracts\QuotationRepositoryInterface;
use Modules\Sales\Domain\Events\QuotationCreated;
class CreateQuotationUseCase
{
    public function __construct(
        private QuotationRepositoryInterface $repo,
        private OrderTotalsCalculator $calculator,
    ) {}
    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $calculated = $this->calculator->calculate($data['lines'] ?? []);
            $tenantId = auth()->user()?->tenant_id ?? $data['tenant_id'] ?? null;
            $quotation = $this->repo->create(array_merge($data, [
                'number' => $this->repo->nextNumber($tenantId),
                'lines' => $calculated['lines'],
                'subtotal' => $calculated['subtotal'],
                'tax_total' => $calculated['tax_total'],
                'total' => $calculated['total'],
                'status' => 'draft',
            ]));
            Event::dispatch(new QuotationCreated($quotation->id));
            return $quotation;
        });
    }
}
