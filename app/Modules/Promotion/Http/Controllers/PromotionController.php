<?php

declare(strict_types=1);

namespace Modules\Promotion\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\Inventory\Constants\InventoryPermission;
use Modules\Inventory\Http\Resources\SellableBatchOptionResource;
use Modules\Item\Http\Resources\ItemSummaryResource;
use Modules\Item\Services\ItemAuthorizationService;
use Modules\Promotion\Constants\PromotionPermission;
use Modules\Promotion\Enums\PromotionBenefitKind;
use Modules\Promotion\Enums\PromotionCountScope;
use Modules\Promotion\Enums\PromotionDiscountType;
use Modules\Promotion\Enums\PromotionStatus;
use Modules\Promotion\Http\Requests\PromotionRequest;
use Modules\Promotion\Services\PromotionCardService;
use Modules\Promotion\Services\PromotionOfferService;
use Modules\Promotion\Services\PromotionRuntimeService;
use Modules\VehicleService\Services\VehicleServiceLineItemLookupService;

final class PromotionController extends Controller
{
    public function jobItems(
        PromotionRequest $request,
        VehicleServiceLineItemLookupService $lookup,
        PermissionCheckerInterface $permissions,
        ItemAuthorizationService $itemAuthorization,
    ): JsonResponse {
        $criteria = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);
        $userId = $request->currentUserId();
        $tenantId = $request->tenantId();
        if ($userId === null || (! $permissions->allows($userId, $tenantId, PromotionPermission::VIEW)
            && ! $permissions->allows($userId, $tenantId, PromotionPermission::MANAGE)
            && ! $permissions->allows($userId, $tenantId, PromotionPermission::REDEEM))) {
            throw new AuthorizationException('You do not have permission to search promotion items.');
        }
        $itemAuthorization->assert($userId, $tenantId, ItemAuthorizationService::VIEW);

        $result = $lookup->search(
            tenantId: $tenantId,
            organizationUnitId: $request->organizationUnitId(),
            search: trim((string) ($criteria['search'] ?? '')),
            page: (int) ($criteria['page'] ?? 1),
            perPage: (int) ($criteria['per_page'] ?? 20),
            includeBatchOptions: $permissions->allows($userId, $tenantId, InventoryPermission::STOCK_VIEW),
        );

        $data = array_map(function (array $option) use ($request): array {
            return $option['type'] === 'batch'
                ? (new SellableBatchOptionResource($option['model']))->resolve($request)
                : (new ItemSummaryResource($option['model']))->resolve($request);
        }, $result['options']);

        return response()->json(['data' => $data, 'meta' => $result['meta']]);
    }

    public function offers(PromotionRequest $request, PromotionOfferService $service): JsonResponse
    {
        return response()->json(['data' => $service->list($request->tenantId(), $request->organizationUnitId())]);
    }

    public function createOffer(PromotionRequest $request, PromotionOfferService $service): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:80'], 'name' => ['required', 'string', 'max:255'],
            'qualifying_item_id' => ['required', 'integer', 'min:1'], 'reward_item_id' => ['required', 'integer', 'min:1'],
            'paid_quantity' => ['required', 'integer', 'min:1'], 'reward_quantity' => ['required', 'integer', 'min:1'],
            'count_scope' => ['required', Rule::enum(PromotionCountScope::class)],
            'starts_on' => ['required', 'date'], 'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ]);

        return response()->json(['data' => $service->create($request->tenantId(), $request->organizationUnitId(), (int) $request->currentUserId(), $data)], 201);
    }

    public function assignOffer(PromotionRequest $request, int $offer, PromotionOfferService $service, PermissionCheckerInterface $permissions): JsonResponse
    {
        $userId = $request->currentUserId();
        if ($userId === null || (! $permissions->allows($userId, $request->tenantId(), PromotionPermission::MANAGE)
            && ! $permissions->allows($userId, $request->tenantId(), PromotionPermission::REDEEM))) {
            throw new AuthorizationException('You do not have permission to assign offers.');
        }
        $data = $request->validate(['customer_id' => ['required', 'integer', 'min:1'], 'vehicle_id' => ['nullable', 'integer', 'min:1']]);

        return response()->json(['data' => $service->assign($request->tenantId(), $request->organizationUnitId(), (int) $request->currentUserId(), $offer, $data)], 201);
    }

    public function offerStatus(PromotionRequest $request, int $offer, PromotionOfferService $service): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([PromotionStatus::Active->value, PromotionStatus::Paused->value])],
            'expected_version' => ['required', 'integer', 'min:1'],
        ]);

        return response()->json(['data' => $service->changeStatus($request->tenantId(), $request->organizationUnitId(), (int) $request->currentUserId(), $offer, (int) $data['expected_version'], $data['status'])]);
    }

    public function deleteOffer(PromotionRequest $request, int $offer, PromotionOfferService $service): JsonResponse
    {
        $data = $request->validate(['expected_version' => ['required', 'integer', 'min:1']]);
        $service->deleteUnused($request->tenantId(), $request->organizationUnitId(), (int) $request->currentUserId(), $offer, (int) $data['expected_version']);

        return response()->json(['message' => 'Offer deleted.']);
    }

    public function templates(PromotionRequest $request, PromotionCardService $service): JsonResponse
    {
        return response()->json(['data' => $service->templates($request->tenantId(), $request->organizationUnitId())]);
    }

    public function createTemplate(PromotionRequest $request, PromotionCardService $service): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:80'], 'name' => ['required', 'string', 'max:255'],
            'starts_on' => ['required', 'date'], 'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'benefits' => ['required', 'array', 'min:1'], 'benefits.*.kind' => ['required', Rule::enum(PromotionBenefitKind::class)],
            'benefits.*.item_id' => ['required_if:benefits.*.kind,direct', 'nullable', 'integer', 'min:1'],
            'benefits.*.discount_type' => ['required_if:benefits.*.kind,direct', 'nullable', Rule::enum(PromotionDiscountType::class)],
            'benefits.*.discount_rate' => ['required_if:benefits.*.discount_type,percentage', 'nullable', 'numeric', 'gt:0', 'lte:100'],
            'benefits.*.use_limit' => ['required_if:benefits.*.kind,direct', 'nullable', 'integer', 'min:1'],
            'benefits.*.offer_id' => ['required_if:benefits.*.kind,earned', 'nullable', 'integer', 'min:1'],
            'benefits.*.valid_weekdays' => ['nullable', 'array'],
            'benefits.*.valid_weekdays.*' => ['integer', 'between:1,7'],
        ]);

        return response()->json(['data' => $service->createTemplate($request->tenantId(), $request->organizationUnitId(), (int) $request->currentUserId(), $data)], 201);
    }

    public function cards(PromotionRequest $request, PromotionCardService $service): JsonResponse
    {
        $filter = $request->validate(['customer_id' => ['nullable', 'integer', 'min:1'], 'vehicle_id' => ['nullable', 'integer', 'min:1']]);

        return response()->json(['data' => $service->cards(
            $request->tenantId(),
            $request->organizationUnitId(),
            isset($filter['customer_id']) ? (int) $filter['customer_id'] : null,
            isset($filter['vehicle_id']) ? (int) $filter['vehicle_id'] : null,
        )]);
    }

    public function issueCard(PromotionRequest $request, PromotionCardService $service): JsonResponse
    {
        $data = $request->validate([
            'template_id' => ['required', 'integer', 'min:1'], 'card_number' => ['nullable', 'string', 'max:100'],
            'customer_id' => ['required', 'integer', 'min:1'], 'vehicle_id' => ['required', 'integer', 'min:1'],
        ]);

        return response()->json(['data' => $service->issue($request->tenantId(), $request->organizationUnitId(), (int) $request->currentUserId(), $data)], 201);
    }

    public function transferCard(PromotionRequest $request, int $card, PromotionCardService $service): JsonResponse
    {
        $data = $request->validate([
            'vehicle_id' => ['required', 'integer', 'min:1'], 'expected_version' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        return response()->json(['data' => $service->transfer($request->tenantId(), $request->organizationUnitId(), (int) $request->currentUserId(), $card, (int) $data['vehicle_id'], (int) $data['expected_version'], $data['reason'])]);
    }

    public function cardStatus(PromotionRequest $request, int $card, PromotionCardService $service): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([PromotionStatus::Active->value, PromotionStatus::Paused->value])],
            'expected_version' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:1000'],
        ]);

        return response()->json(['data' => $service->status($request->tenantId(), $request->organizationUnitId(), (int) $request->currentUserId(), $card, $data['status'], (int) $data['expected_version'], $data['reason'])]);
    }

    public function available(PromotionRequest $request, PromotionRuntimeService $service, PermissionCheckerInterface $permissions): JsonResponse
    {
        $userId = $request->currentUserId();
        $tenantId = $request->tenantId();
        if ($userId === null || (! $permissions->allows($userId, $tenantId, PromotionPermission::VIEW)
            && ! $permissions->allows($userId, $tenantId, PromotionPermission::MANAGE)
            && ! $permissions->allows($userId, $tenantId, PromotionPermission::REDEEM))) {
            throw new AuthorizationException('You do not have permission to view available promotions.');
        }
        $data = $request->validate(['customer_id' => ['required', 'integer', 'min:1'], 'vehicle_id' => ['required', 'integer', 'min:1']]);

        return response()->json(['data' => $service->available($request->tenantId(), $request->organizationUnitId(), (int) $data['customer_id'], (int) $data['vehicle_id'])]);
    }

    public function choose(PromotionRequest $request, int $job, int $line, PromotionRuntimeService $service): JsonResponse
    {
        $data = $request->validate([
            'expected_version' => ['required', 'integer', 'min:1'],
            'card_id' => ['required_with:card_benefit_id', 'nullable', 'integer', 'min:1'],
            'card_benefit_id' => ['required_without:offer_assignment_id', 'nullable', 'integer', 'min:1'],
            'offer_assignment_id' => ['required_without:card_benefit_id', 'nullable', 'integer', 'min:1'],
        ]);

        return response()->json(['data' => $service->choose($request->tenantId(), $request->organizationUnitId(), (int) $request->currentUserId(), $job, $line, (int) $data['expected_version'], $data)]);
    }

    public function removeChoice(PromotionRequest $request, int $job, int $line, PromotionRuntimeService $service): JsonResponse
    {
        $data = $request->validate(['expected_version' => ['required', 'integer', 'min:1']]);

        return response()->json(['data' => $service->removeChoice($request->tenantId(), $request->organizationUnitId(), $job, $line, (int) $data['expected_version'])]);
    }
}
