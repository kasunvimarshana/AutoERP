<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Enums\AgreementAdjustmentCategory;
use Modules\VehicleRental\Enums\AgreementAdjustmentStatus;
use Modules\VehicleRental\Enums\AgreementAdjustmentType;
use Modules\VehicleRental\Http\Requests\AgreementAdjustmentRequest;
use Modules\VehicleRental\Models\AgreementAdjustment;
use Modules\VehicleRental\Models\AgreementAdjustmentHistory;
use Symfony\Component\HttpFoundation\Response;

final class AgreementAdjustmentController
{
    public function index(AgreementAdjustmentRequest $request): JsonResponse
    {
        $context = $request->context();
        $agreementId = $request->query('agreement_id');
        $kind = $request->query('kind');

        $query = AgreementAdjustment::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with(['invoice', 'customerAgreement', 'ownerAgreement', 'authorizedBy'])
            ->orderByDesc('id');

        if ($agreementId !== null) {
            if ($kind === 'owner') {
                $query->where('owner_agreement_id', (int) $agreementId);
            } else {
                $query->where('customer_agreement_id', (int) $agreementId);
            }
        }

        return response()->json([
            'data' => $query->paginate($request->perPage()),
        ]);
    }

    public function store(AgreementAdjustmentRequest $request): JsonResponse
    {
        $context = $request->context();
        $data = $request->validated();

        $adjustment = new AgreementAdjustment();
        $adjustment->tenant_id = $context->tenantId;
        $adjustment->organization_unit_id = $context->organizationUnitId;
        $adjustment->customer_agreement_id = ! empty($data['customer_agreement_id']) ? (int) $data['customer_agreement_id'] : null;
        $adjustment->owner_agreement_id = ! empty($data['owner_agreement_id']) ? (int) $data['owner_agreement_id'] : null;
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

        $history = new AgreementAdjustmentHistory();
        $history->tenant_id = $context->tenantId;
        $history->adjustment_id = $adjustment->id;
        $history->from_status = AgreementAdjustmentStatus::Draft;
        $history->to_status = AgreementAdjustmentStatus::Authorized;
        $history->reason = 'Adjustment created and authorized';
        $history->user_id = $context->actorId;
        $history->save();

        return response()->json(['data' => $adjustment->load(['customerAgreement', 'ownerAgreement', 'invoice'])], Response::HTTP_CREATED);
    }

    public function show(AgreementAdjustmentRequest $request, int $adjustment): JsonResponse
    {
        $context = $request->context();
        $record = AgreementAdjustment::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with(['customerAgreement', 'ownerAgreement', 'invoice', 'history.user'])
            ->findOrFail($adjustment);

        return response()->json(['data' => $record]);
    }
}
