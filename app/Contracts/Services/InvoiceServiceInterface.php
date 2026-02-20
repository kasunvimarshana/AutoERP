<?php

namespace App\Contracts\Services;

use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;

interface InvoiceServiceInterface
{
    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Invoice;

    public function send(string $id): Invoice;

    public function void(string $id): Invoice;
}
