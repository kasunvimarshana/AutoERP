<?php
namespace Modules\Sales\Infrastructure\Repositories;
use Modules\Sales\Domain\Contracts\QuotationRepositoryInterface;
use Modules\Sales\Infrastructure\Models\QuotationModel;
use Modules\Sales\Infrastructure\Models\QuotationLineModel;
use Illuminate\Support\Facades\DB;
class QuotationRepository implements QuotationRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return QuotationModel::with('lines')->find($id);
    }
    public function paginate(array $filters, int $perPage = 15): object
    {
        $query = QuotationModel::query();
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['customer_id'])) $query->where('customer_id', $filters['customer_id']);
        return $query->latest()->paginate($perPage);
    }
    public function create(array $data): object
    {
        $lines = $data['lines'] ?? [];
        unset($data['lines']);
        $quotation = QuotationModel::create($data);
        foreach ($lines as $index => $line) {
            $line['quotation_id'] = $quotation->id;
            $line['sort_order'] = $index + 1;
            QuotationLineModel::create($line);
        }
        return $quotation->load('lines');
    }
    public function update(string $id, array $data): object
    {
        $quotation = QuotationModel::findOrFail($id);
        $lines = $data['lines'] ?? null;
        unset($data['lines']);
        $quotation->update($data);
        if ($lines !== null) {
            $quotation->lines()->delete();
            foreach ($lines as $index => $line) {
                $line['quotation_id'] = $quotation->id;
                $line['sort_order'] = $index + 1;
                QuotationLineModel::create($line);
            }
        }
        return $quotation->load('lines');
    }
    public function delete(string $id): bool
    {
        return QuotationModel::findOrFail($id)->delete();
    }
    public function nextNumber(string $tenantId): string
    {
        $count = QuotationModel::withTrashed()->where('tenant_id', $tenantId)->count();
        return 'QUO-'.str_pad($count + 1, 6, '0', STR_PAD_LEFT);
    }
}
