<?php

declare(strict_types=1);

namespace Modules\Voucher\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\View\View;
use Modules\Voucher\Enums\VoucherType;
use Modules\Voucher\Http\Requests\ListVoucherRequest;
use Modules\Voucher\Http\Resources\VoucherResource;
use Modules\Voucher\Services\VoucherQueryService;
use Modules\Voucher\Services\VoucherSourceResolver;
use Modules\Voucher\Services\VoucherTypeRegistry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class VoucherController
{
    public function __construct(private readonly VoucherTypeRegistry $registry) {}

    public function types(): JsonResponse
    {
        return response()->json(['data' => array_values($this->registry->all())]);
    }

    public function index(ListVoucherRequest $request, VoucherQueryService $query): AnonymousResourceCollection
    {
        return VoucherResource::collection($query->paginate(
            $request->validated(),
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
    ): VoucherResource {
        $type = $this->type($voucherType);

        return new VoucherResource($resolver->resolve(
            $type,
            $source,
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->filled('source_kind') ? (string) $request->input('source_kind') : null,
        )->toArray());
    }

    public function print(
        ListVoucherRequest $request,
        string $voucherType,
        int $source,
        VoucherSourceResolver $resolver,
    ): View {
        $type = $this->type($voucherType);
        $voucher = $resolver->resolve(
            $type,
            $source,
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->filled('source_kind') ? (string) $request->input('source_kind') : null,
        )->toArray();

        return view('voucher::print', ['voucher' => $voucher]);
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
