<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Support;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Tenant\Application\Contracts\TenantRecordMapperInterface;
use Modules\Tenant\Application\DTOs\TenantValueData;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\ValueObjects\TenantIsolationKey;

final class TenantRecordMapper implements TenantRecordMapperInterface
{
    public function toValueData(DataRecord $record): TenantValueData
    {
        $metadata = $record->get('metadata', []);

        return new TenantValueData(
            $record->id(),
            (string) $record->require('uuid'),
            (string) $record->require('code'),
            (string) $record->require('name'),
            (string) $record->require('slug'),
            $record->get('logo_path') !== null ? (string) $record->get('logo_path') : null,
            (bool) $record->get('cross_org_transactions', false),
            $record->get('tenant_plan_id') !== null ? (int) $record->get('tenant_plan_id') : null,
            $record->get('currency_id') !== null ? (int) $record->get('currency_id') : null,
            (string) $record->require('status'),
            $record->get('trial_ends_at') !== null ? (string) $record->get('trial_ends_at') : null,
            $record->get('subscription_ends_at') !== null ? (string) $record->get('subscription_ends_at') : null,
            (bool) $record->get('is_active', false),
            (bool) $record->get('is_isolated', true),
            $record->get('isolation_key') !== null ? (string) $record->get('isolation_key') : null,
            $record->get('configuration_scope') !== null ? (string) $record->get('configuration_scope') : null,
            is_array($metadata) ? $metadata : [],
        );
    }

    public function toEntity(DataRecord $record): Tenant
    {
        $data = $this->toValueData($record);

        return new Tenant(
            $data->id,
            $data->uuid,
            $data->code,
            $data->name,
            $data->slug,
            $data->logoPath,
            $data->crossOrgTransactions,
            $data->tenantPlanId,
            $data->currencyId,
            $data->status,
            $data->trialEndsAt,
            $data->subscriptionEndsAt,
            $data->isActive,
            $data->isIsolated,
            $data->isolationKey !== null ? new TenantIsolationKey($data->isolationKey) : null,
            $data->configurationScope,
            $data->metadata,
        );
    }
}
