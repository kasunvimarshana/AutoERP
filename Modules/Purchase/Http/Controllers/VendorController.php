<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Purchase\Enums\VendorStatus;
use Modules\Purchase\Http\Requests\StoreVendorRequest;
use Modules\Purchase\Http\Requests\UpdateVendorRequest;
use Modules\Purchase\Http\Resources\VendorResource;
use Modules\Purchase\Models\Vendor;
use Modules\Purchase\Services\VendorService;

class VendorController extends Controller
{
    public function __construct(
        private VendorService $vendorService
    ) {}

    /**
     * Display a listing of vendors.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Vendor::class);

        $query = Vendor::query()
            ->where('tenant_id', $request->user()->currentTenant()->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $vendors = $query->latest()->paginate($perPage);

        return ApiResponse::paginated(
            $vendors->setCollection(
                $vendors->getCollection()->map(fn ($vendor) => new VendorResource($vendor))
            ),
            'Vendors retrieved successfully'
        );
    }

    /**
     * Store a newly created vendor.
     */
    public function store(StoreVendorRequest $request): JsonResponse
    {
        $this->authorize('create', Vendor::class);

        $data = $request->validated();
        $data['tenant_id'] = $request->user()->currentTenant()->id;
        $data['current_balance'] = '0.00';

        $vendor = DB::transaction(function () use ($data) {
            return $this->vendorService->create($data);
        });

        return ApiResponse::created(
            new VendorResource($vendor),
            'Vendor created successfully'
        );
    }

    /**
     * Display the specified vendor.
     */
    public function show(Vendor $vendor): JsonResponse
    {
        $this->authorize('view', $vendor);

        return ApiResponse::success(
            new VendorResource($vendor),
            'Vendor retrieved successfully'
        );
    }

    /**
     * Update the specified vendor.
     */
    public function update(UpdateVendorRequest $request, Vendor $vendor): JsonResponse
    {
        $this->authorize('update', $vendor);

        $data = $request->validated();

        $vendor = DB::transaction(function () use ($vendor, $data) {
            return $this->vendorService->update($vendor->id, $data);
        });

        return ApiResponse::success(
            new VendorResource($vendor),
            'Vendor updated successfully'
        );
    }

    /**
     * Remove the specified vendor.
     */
    public function destroy(Vendor $vendor): JsonResponse
    {
        $this->authorize('delete', $vendor);

        DB::transaction(function () use ($vendor) {
            $this->vendorService->delete($vendor->id);
        });

        return ApiResponse::success(
            null,
            'Vendor deleted successfully'
        );
    }

    /**
     * Activate the vendor.
     */
    public function activate(Vendor $vendor): JsonResponse
    {
        $this->authorize('update', $vendor);

        if ($vendor->status === VendorStatus::ACTIVE) {
            return ApiResponse::error('Vendor is already active', 422);
        }

        $vendor = DB::transaction(function () use ($vendor) {
            return $this->vendorService->updateStatus($vendor->id, VendorStatus::ACTIVE);
        });

        return ApiResponse::success(
            new VendorResource($vendor),
            'Vendor activated successfully'
        );
    }

    /**
     * Deactivate the vendor.
     */
    public function deactivate(Vendor $vendor): JsonResponse
    {
        $this->authorize('update', $vendor);

        if ($vendor->status === VendorStatus::INACTIVE) {
            return ApiResponse::error('Vendor is already inactive', 422);
        }

        $vendor = DB::transaction(function () use ($vendor) {
            return $this->vendorService->updateStatus($vendor->id, VendorStatus::INACTIVE);
        });

        return ApiResponse::success(
            new VendorResource($vendor),
            'Vendor deactivated successfully'
        );
    }

    /**
     * Block the vendor.
     */
    public function block(Vendor $vendor): JsonResponse
    {
        $this->authorize('update', $vendor);

        if ($vendor->status === VendorStatus::BLOCKED) {
            return ApiResponse::error('Vendor is already blocked', 422);
        }

        $vendor = DB::transaction(function () use ($vendor) {
            return $this->vendorService->updateStatus($vendor->id, VendorStatus::BLOCKED);
        });

        return ApiResponse::success(
            new VendorResource($vendor),
            'Vendor blocked successfully'
        );
    }
}
