<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierAddress;
use Modules\Supplier\Models\SupplierBankAccount;
use Modules\Supplier\Models\SupplierContact;
use Modules\Supplier\Models\SupplierDocument;
use Modules\Supplier\Models\SupplierItemMapping;

final class SupplierRelationQueryService
{
    public function contacts(Supplier $supplier, int $perPage): LengthAwarePaginator
    {
        return $supplier->contacts()->orderByDesc('is_primary')->orderBy('contact_name')->paginate($perPage);
    }

    public function addresses(Supplier $supplier, int $perPage): LengthAwarePaginator
    {
        return $supplier->addresses()->orderBy('address_type')->orderByDesc('is_primary')->paginate($perPage);
    }

    public function bankAccounts(Supplier $supplier, int $perPage): LengthAwarePaginator
    {
        return $supplier->bankAccounts()->with('currency')->orderByDesc('is_primary')->orderBy('bank_name')->paginate($perPage);
    }

    public function categories(Supplier $supplier, int $perPage): LengthAwarePaginator
    {
        return $supplier->categories()->with('parent')->orderBy('name')->paginate($perPage);
    }

    public function documents(Supplier $supplier, int $perPage): LengthAwarePaginator
    {
        return $supplier->documents()->orderBy('document_type')->orderByDesc('expiry_date')->paginate($perPage);
    }

    public function itemMappings(Supplier $supplier, int $perPage): LengthAwarePaginator
    {
        return $supplier->itemMappings()
            ->with(['item.category', 'item.brand', 'variant', 'defaultPurchaseUom'])
            ->orderByDesc('is_preferred')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function statusHistory(Supplier $supplier, int $perPage): LengthAwarePaginator
    {
        return $supplier->statusHistories()->orderByDesc('changed_at')->orderByDesc('id')->paginate($perPage);
    }

    public function contact(Supplier $supplier, int $id): SupplierContact
    {
        return $this->relation($supplier, SupplierContact::class, $id);
    }

    public function address(Supplier $supplier, int $id): SupplierAddress
    {
        return $this->relation($supplier, SupplierAddress::class, $id);
    }

    public function bankAccount(Supplier $supplier, int $id): SupplierBankAccount
    {
        return $this->relation($supplier, SupplierBankAccount::class, $id);
    }

    public function document(Supplier $supplier, int $id): SupplierDocument
    {
        return $this->relation($supplier, SupplierDocument::class, $id);
    }

    public function itemMapping(Supplier $supplier, int $id): SupplierItemMapping
    {
        return $this->relation($supplier, SupplierItemMapping::class, $id);
    }

    private function relation(Supplier $supplier, string $model, int $id): Model
    {
        return $model::query()->where('supplier_id', $supplier->getKey())->findOrFail($id);
    }
}
