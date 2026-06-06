<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Customer\DTOs\CustomerStatusChangeData;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerStatusHistory;

final class CustomerStatusService
{
    public function recordInitial(Customer $customer, ?int $changedBy = null): void
    {
        CustomerStatusHistory::query()->create([
            'tenant_id' => $customer->tenant_id,
            'organization_unit_id' => $customer->organization_unit_id,
            'customer_id' => $customer->getKey(),
            'old_status' => null,
            'new_status' => $customer->status,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }

    public function change(Customer $customer, CustomerStatusChangeData $data): Customer
    {
        $from = $customer->status instanceof CustomerStatus
            ? $customer->status
            : CustomerStatus::from((string) $customer->status);

        $this->assertTransition($from, $data->newStatus);

        if ($from === $data->newStatus) {
            return $customer;
        }

        return DB::transaction(function () use ($customer, $data, $from): Customer {
            CustomerStatusHistory::query()->create([
                'tenant_id' => $customer->tenant_id,
                'organization_unit_id' => $customer->organization_unit_id,
                'customer_id' => $customer->getKey(),
                'old_status' => $from,
                'new_status' => $data->newStatus,
                'reason' => $data->reason,
                'changed_by' => $data->changedBy,
                'changed_at' => now(),
            ]);

            $customer->status = $data->newStatus;
            if ($data->newStatus === CustomerStatus::Active && $customer->approved_at === null) {
                $customer->approved_by = $data->changedBy;
                $customer->approved_at = now();
            }
            $customer->save();

            return $customer->refresh();
        });
    }

    public function changeTo(
        Customer $customer,
        CustomerStatus $status,
        ?int $changedBy = null,
        ?string $reason = null,
    ): Customer {
        return $this->change($customer, new CustomerStatusChangeData(
            newStatus: $status,
            reason: $reason,
            changedBy: $changedBy,
        ));
    }

    public function assertTransition(CustomerStatus $from, CustomerStatus $to): void
    {
        $allowed = [
            CustomerStatus::PendingApproval->value => [
                CustomerStatus::Active,
                CustomerStatus::Inactive,
                CustomerStatus::Blacklisted,
            ],
            CustomerStatus::Active->value => [
                CustomerStatus::Inactive,
                CustomerStatus::OnHold,
                CustomerStatus::Blacklisted,
            ],
            CustomerStatus::Inactive->value => [
                CustomerStatus::Active,
                CustomerStatus::PendingApproval,
                CustomerStatus::Blacklisted,
            ],
            CustomerStatus::OnHold->value => [
                CustomerStatus::Active,
                CustomerStatus::Inactive,
                CustomerStatus::Blacklisted,
            ],
            CustomerStatus::Blacklisted->value => [
                CustomerStatus::Inactive,
                CustomerStatus::PendingApproval,
            ],
        ];

        if ($from !== $to && ! in_array($to, $allowed[$from->value] ?? [], true)) {
            throw new InvalidArgumentException('Invalid customer status transition.');
        }
    }
}
