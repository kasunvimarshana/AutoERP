<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Invoice\Models\Invoice;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Enums\AgreementAdjustmentCategory;
use Modules\VehicleRental\Enums\AgreementAdjustmentStatus;
use Modules\VehicleRental\Enums\AgreementAdjustmentType;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Http\Requests\AgreementAdjustmentRequest;
use Modules\VehicleRental\Http\Resources\AgreementAdjustmentResource;
use Modules\VehicleRental\Models\AgreementAdjustment;
use Modules\VehicleRental\Models\AgreementAdjustmentHistory;
use Modules\VehicleRental\Models\CustomerAgreement;
use Modules\VehicleRental\Models\OwnerAgreement;
use Modules\VehicleRental\Services\RentalAuthorization;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

final class AgreementAdjustmentController
{
    public function __construct(
        private readonly RentalAuthorization $authorization,
    ) {}

    public function index(AgreementAdjustmentRequest $request): AnonymousResourceCollection
    {
        $context = $request->context();
        $agreementId = $request->query('agreement_id');
        $kind = $request->query('kind');

        if ($kind === 'owner') {
            $this->authorization->assert($context, AgreementKind::Owner, false);
        } else {
            $this->authorization->assert($context, AgreementKind::Customer, false);
        }

        $query = AgreementAdjustment::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with(['invoice', 'customerAgreement.customer', 'ownerAgreement.supplier', 'authorizedBy'])
            ->orderByDesc('id');

        if ($agreementId !== null) {
            if ($kind === 'owner') {
                $query->where('owner_agreement_id', (int) $agreementId);
            } else {
                $query->where('customer_agreement_id', (int) $agreementId);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $term = '%'.trim((string) $request->query('search')).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('reason', 'like', $term)
                    ->orWhere('id', 'like', $term);
            });
        }

        return AgreementAdjustmentResource::collection($query->paginate($request->perPage()));
    }

    public function store(AgreementAdjustmentRequest $request): JsonResponse
    {
        $context = $request->context();
        $data = $request->validated();

        $customerAgId = ! empty($data['customer_agreement_id']) ? (int) $data['customer_agreement_id'] : null;
        $ownerAgId = ! empty($data['owner_agreement_id']) ? (int) $data['owner_agreement_id'] : null;

        if ($customerAgId !== null) {
            $this->authorization->assert($context, AgreementKind::Customer, true);
            $agreement = CustomerAgreement::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->findOrFail($customerAgId);
        } elseif ($ownerAgId !== null) {
            $this->authorization->assert($context, AgreementKind::Owner, true);
            $agreement = OwnerAgreement::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->findOrFail($ownerAgId);
        } else {
            throw ValidationException::withMessages(['agreement' => ['Specify either a customer agreement or owner agreement.']]);
        }

        if ($agreement->status === AgreementStatus::Closed) {
            throw ValidationException::withMessages(['agreement' => ['Cannot create adjustments for a closed agreement.']]);
        }

        if (! empty($data['invoice_id'])) {
            Invoice::query()
                ->where('tenant_id', $context->tenantId)
                ->where('organization_unit_id', $context->organizationUnitId)
                ->findOrFail((int) $data['invoice_id']);
        }

        $adjustment = DB::transaction(function () use ($context, $data, $customerAgId, $ownerAgId): AgreementAdjustment {
            $adjustment = new AgreementAdjustment;
            $adjustment->tenant_id = $context->tenantId;
            $adjustment->organization_unit_id = $context->organizationUnitId;
            $adjustment->customer_agreement_id = $customerAgId;
            $adjustment->owner_agreement_id = $ownerAgId;
            $adjustment->invoice_id = ! empty($data['invoice_id']) ? (int) $data['invoice_id'] : null;
            $adjustment->adjustment_type = AgreementAdjustmentType::from((string) $data['adjustment_type']);
            $adjustment->category = AgreementAdjustmentCategory::from((string) $data['category']);
            $adjustment->status = AgreementAdjustmentStatus::Authorized;
            $adjustment->amount = (string) $data['amount'];
            $adjustment->original_amount = (string) $data['amount'];
            $adjustment->tax_amount = '0.000000';
            $adjustment->reason = trim((string) $data['reason']);
            $adjustment->actor_id = $context->actorId;
            $adjustment->authorized_by_user_id = $context->actorId;
            $adjustment->authorized_at = now();
            $adjustment->row_version = AgreementFields::INITIAL_VERSION;
            $adjustment->save();

            $history = new AgreementAdjustmentHistory;
            $history->tenant_id = $context->tenantId;
            $history->adjustment_id = $adjustment->id;
            $history->from_status = AgreementAdjustmentStatus::Draft;
            $history->to_status = AgreementAdjustmentStatus::Authorized;
            $history->reason = 'Adjustment created and authorized';
            $history->user_id = $context->actorId;
            $history->save();

            return $adjustment;
        });

        return (new AgreementAdjustmentResource($adjustment->load(['customerAgreement.customer', 'ownerAgreement.supplier', 'invoice', 'authorizedBy'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(AgreementAdjustmentRequest $request, int $adjustment): AgreementAdjustmentResource
    {
        $context = $request->context();
        $record = AgreementAdjustment::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with(['customerAgreement.customer', 'ownerAgreement.supplier', 'invoice', 'history.user', 'authorizedBy'])
            ->findOrFail($adjustment);

        if ($record->owner_agreement_id !== null) {
            $this->authorization->assert($context, AgreementKind::Owner, false);
        } else {
            $this->authorization->assert($context, AgreementKind::Customer, false);
        }

        return new AgreementAdjustmentResource($record);
    }

    public function void(AgreementAdjustmentRequest $request, int $adjustment): AgreementAdjustmentResource
    {
        $context = $request->context();

        $updated = DB::transaction(function () use ($context, $request, $adjustment): AgreementAdjustment {
            $record = AgreementAdjustment::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($adjustment);

            if ($record->owner_agreement_id !== null) {
                $this->authorization->assert($context, AgreementKind::Owner, true);
            } else {
                $this->authorization->assert($context, AgreementKind::Customer, true);
            }

            if ((int) $record->row_version !== (int) $request->validated('expected_version')) {
                throw new ConflictHttpException('Agreement adjustment was modified by another request. Reload before voiding.');
            }

            if (! in_array($record->status, [AgreementAdjustmentStatus::Draft, AgreementAdjustmentStatus::Authorized], true)) {
                throw new ConflictHttpException("Only draft or authorized adjustments can be voided. Current status is {$record->status->value}.");
            }

            $fromStatus = $record->status;
            $record->status = AgreementAdjustmentStatus::Voided;
            $record->row_version += 1;
            $record->save();

            $history = new AgreementAdjustmentHistory;
            $history->tenant_id = $context->tenantId;
            $history->adjustment_id = $record->id;
            $history->from_status = $fromStatus;
            $history->to_status = AgreementAdjustmentStatus::Voided;
            $history->reason = 'Voided: '.trim((string) $request->validated('reason'));
            $history->user_id = $context->actorId;
            $history->save();

            return $record;
        });

        return new AgreementAdjustmentResource($updated->load(['customerAgreement.customer', 'ownerAgreement.supplier', 'invoice', 'history.user', 'authorizedBy']));
    }

    public function destroy(): JsonResponse
    {
        throw new MethodNotAllowedHttpException([], 'Rental agreement adjustments cannot be deleted to preserve financial audit history. Void the adjustment instead.');
    }
}
