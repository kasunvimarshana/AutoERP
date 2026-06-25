<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Plans;

use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Repositories\TenantPlanRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantDomainServiceInterface;
use Throwable;

final class CreateTenantPlanService
{
    public function __construct(
        private readonly TenantPlanRepositoryInterface $plans,
        private readonly TenantDomainServiceInterface $rules,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly AuditRecorderInterface $audit,
        private readonly ErrorNormalizerInterface $errors,
    ) {}

    /** @param array<string, mixed> $payload */
    public function execute(array $payload): Result
    {
        try {
            $slug = $this->rules->normalizeSlug((string) ($payload['slug'] ?? ''));
            if ($this->plans->findBySlug($slug) !== null) {
                return Result::failure(new Error(
                    TenantErrorCode::CONFLICT,
                    'Tenant plan slug already exists.',
                ));
            }

            $record = $this->plans->create([
                'name' => $this->rules->normalizeName((string) ($payload['name'] ?? '')),
                'slug' => $slug,
                'features' => is_array($payload['features'] ?? null)
                    ? $payload['features']
                    : [],
                'limits' => is_array($payload['limits'] ?? null)
                    ? $payload['limits']
                    : [],
                'price' => (string) ($payload['price'] ?? '0.000000'),
                'currency_id' => isset($payload['currency_id'])
                    ? (int) $payload['currency_id']
                    : null,
                'billing_interval' => $this->rules->normalizeBillingInterval(
                    isset($payload['billing_interval'])
                        ? (string) $payload['billing_interval']
                        : null,
                ),
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'metadata' => [],
                'row_version' => 1,
                'created_by' => $this->currentUser->currentUserId(),
                'updated_by' => $this->currentUser->currentUserId(),
            ]);

            $this->audit->record(new AuditEventData(
                eventName: 'tenant.plan.created',
                eventCategory: 'administration',
                sourceModule: 'tenant',
                subjectType: 'tenant_plan',
                subjectId: (string) $record->id(),
                subjectReference: $slug,
                changes: [
                    'new' => [
                        'name' => $record->get('name'),
                        'billing_interval' => $record->get('billing_interval'),
                        'price' => $record->get('price'),
                    ],
                ],
                tags: ['tenant', 'plan'],
            ));

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure($this->errors->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.plan.create'],
            ));
        }
    }
}
