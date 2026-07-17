<?php

declare(strict_types=1);

namespace Modules\Voucher\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\View\View;
use Modules\Voucher\DTOs\VoucherAccessScope;
use Modules\Voucher\Enums\VoucherType;
use Modules\Voucher\Http\Requests\ListVoucherRequest;
use Modules\Voucher\Http\Resources\VoucherResource;
use Modules\Voucher\Services\VoucherAccessPolicy;
use Modules\Voucher\Services\VoucherQueryService;
use Modules\Voucher\Services\VoucherSourceResolver;
use Modules\Voucher\Services\VoucherTypeRegistry;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class VoucherController
{
    public function __construct(private readonly VoucherTypeRegistry $registry) {}

    public function types(ListVoucherRequest $request, VoucherAccessPolicy $access): JsonResponse
    {
        return response()->json([
            'data' => $access->visibleTypeDefinitions(
                $this->registry->all(),
                $this->accessScope($request, $access),
            ),
        ]);
    }

    public function index(
        ListVoucherRequest $request,
        VoucherQueryService $query,
        VoucherAccessPolicy $access,
    ): AnonymousResourceCollection {
        $scope = $this->accessScope($request, $access);

        return VoucherResource::collection($query->paginate(
            $access->constrainListFilters($request->validated(), $scope),
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->perPage(),
        ));
    }

    public function show(
        ListVoucherRequest $request,
        string $voucherType,
        int $source,
        VoucherSourceResolver $resolver,
        VoucherAccessPolicy $access,
    ): VoucherResource {
        $type = $this->type($voucherType);
        $scope = $this->accessScope($request, $access);
        $sourceKind = $access->authorizedSourceKind(
            $type,
            $request->filled('source_kind') ? (string) $request->input('source_kind') : null,
            $scope,
        );

        return new VoucherResource($resolver->resolve(
            $type,
            $source,
            $request->tenantId(),
            $request->organizationUnitId(),
            $sourceKind,
        )->toArray());
    }

    public function print(
        ListVoucherRequest $request,
        string $voucherType,
        int $source,
        VoucherSourceResolver $resolver,
        VoucherAccessPolicy $access,
    ): View {
        $type = $this->type($voucherType);
        $scope = $this->accessScope($request, $access);
        $sourceKind = $access->authorizedSourceKind(
            $type,
            $request->filled('source_kind') ? (string) $request->input('source_kind') : null,
            $scope,
        );
        $voucher = $resolver->resolve(
            $type,
            $source,
            $request->tenantId(),
            $request->organizationUnitId(),
            $sourceKind,
        )->toArray();

        return view('voucher::print', ['voucher' => $voucher]);
    }

    private function accessScope(ListVoucherRequest $request, VoucherAccessPolicy $access): VoucherAccessScope
    {
        $userId = $request->currentUserId();
        if ($userId === null) {
            throw new AccessDeniedHttpException('A current user is required to view vouchers.');
        }

        return $access->scopeFor($userId, $request->tenantId());
    }

    private function type(string $voucherType): VoucherType
    {
        $type = VoucherType::tryFrom($voucherType);
        if (! $type instanceof VoucherType) {
            throw new NotFoundHttpException('Voucher type is not supported.');
        }

        return $type;
    }
}
