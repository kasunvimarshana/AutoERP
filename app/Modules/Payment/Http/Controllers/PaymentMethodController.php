<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Payment\Http\Requests\ListPaymentMethodRequest;
use Modules\Payment\Http\Resources\PaymentMethodResource;
use Modules\Payment\Models\PaymentMethod;

final class PaymentMethodController
{
    public function index(ListPaymentMethodRequest $request): AnonymousResourceCollection
    {
        $query = PaymentMethod::query()
            ->where(fn (Builder $scope): Builder => $scope
                ->whereNull('tenant_id')
                ->orWhere('tenant_id', $request->tenantId()))
            ->where(fn (Builder $scope): Builder => $scope
                ->whereNull('organization_unit_id')
                ->orWhere('organization_unit_id', $request->organizationUnitId()));

        if (! $request->has('is_active') || $request->boolean('is_active')) {
            $query->where('is_active', true);
        }

        if ($request->filled('direction')) {
            $direction = (string) $request->input('direction');
            $query->whereIn('direction_allowed', [$direction, 'both']);
        }

        if ($request->filled('method_type')) {
            $query->where('method_type', $request->input('method_type'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(fn (Builder $scope): Builder => $scope
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"));
        }

        return PaymentMethodResource::collection($query->orderBy('sort_order')->orderBy('name')->paginate($request->perPage()));
    }
}
