<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerAddress;
use Modules\Customer\Models\CustomerBankAccount;
use Modules\Customer\Models\CustomerContact;
use Modules\Customer\Models\CustomerDocument;

final class CustomerRelationQueryService
{
    public function contacts(Customer $customer, int $perPage): LengthAwarePaginator
    {
        return $customer->contacts()->orderByDesc('is_primary')->orderBy('contact_name')->paginate($perPage);
    }

    public function addresses(Customer $customer, int $perPage): LengthAwarePaginator
    {
        return $customer->addresses()->orderBy('address_type')->orderByDesc('is_primary')->paginate($perPage);
    }

    public function bankAccounts(Customer $customer, int $perPage): LengthAwarePaginator
    {
        return $customer->bankAccounts()->with('currency')->orderByDesc('is_primary')->orderBy('bank_name')->paginate($perPage);
    }

    public function categories(Customer $customer, int $perPage): LengthAwarePaginator
    {
        return $customer->categories()->with('parent')->orderBy('name')->paginate($perPage);
    }

    public function documents(Customer $customer, int $perPage): LengthAwarePaginator
    {
        return $customer->documents()->orderBy('document_type')->orderByDesc('expiry_date')->paginate($perPage);
    }

    public function statusHistory(Customer $customer, int $perPage): LengthAwarePaginator
    {
        return $customer->statusHistories()->orderByDesc('changed_at')->orderByDesc('id')->paginate($perPage);
    }

    public function contact(Customer $customer, int $id): CustomerContact
    {
        return $this->relation($customer, CustomerContact::class, $id);
    }

    public function address(Customer $customer, int $id): CustomerAddress
    {
        return $this->relation($customer, CustomerAddress::class, $id);
    }

    public function bankAccount(Customer $customer, int $id): CustomerBankAccount
    {
        return $this->relation($customer, CustomerBankAccount::class, $id);
    }

    public function document(Customer $customer, int $id): CustomerDocument
    {
        return $this->relation($customer, CustomerDocument::class, $id);
    }

    private function relation(Customer $customer, string $model, int $id): Model
    {
        return $model::query()->where('customer_id', $customer->getKey())->findOrFail($id);
    }
}
