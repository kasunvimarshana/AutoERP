<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierCollectionResource;
use App\Http\Resources\SupplierResource;
use App\Http\Resources\VehicleCollectionResource;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierVehicleModel;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

class SupplierController extends Controller
{
    //
    public function __construct(private readonly FileStorageServiceInterface $fileStorageService)
    {
        //
    }

    public function index(Request $request)
    {
        //
        $validated = $request->all();

        $pageName = config('core.pagination.page_name', 'page');
        $perPage = (int) ($validated['per_page'] ?? 15);
        $page = (int) ($validated[$pageName] ?? 1);
        $page = $page ?: Paginator::resolveCurrentPage($pageName);
        $columns = ['*'];

        $suppliers = SupplierModel::with(['user', 'user.roles', 'vehicles'])->paginate($perPage, $columns, $pageName, $page);

        $resource = new SupplierCollectionResource($suppliers);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function store(StoreSupplierRequest $request)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        // $payload = $request->validated();
        $payload = [
            'tenant_id' => $tenant_id,
            'organization_unit_id' => $organization_unit_id,
            'first_name' => $request->input('user.first_name'),
            'last_name' => $request->input('user.last_name'),
            'email' => $request->input('user.email'),
            'password' => $request->input('user.password'),
            'phone' => $request->input('user.phone'),
            'preferences' => $request->input('user.preferences'),
            'date_of_birth' => $request->input('user.date_of_birth'),
            'gender' => $request->input('user.gender'),
            'marital_status' => $request->input('user.marital_status'),
            'status' => $request->input('status'),
        ];

        if ($request->hasFile('user.avatar_file')) {
            $payload['avatar_path'] = $this->storeImage(
                $request->file('user.avatar_file'),
                (int) $tenant_id,
                'users'
            );
        }

        DB::beginTransaction();

        try {
            $user = UserModel::create($payload);

            $user->roles()->sync($request->input('user.roles', []));

            $supplier = SupplierModel::create([
                'tenant_id' => $user->tenant_id,
                'organization_unit_id' => $user->organization_unit_id,
                'code' => $request->input('code'),
                'registration_number' => $request->input('registration_number'),
                'status' => $user->status,
                'notes' => $request->input('notes'),
                'user_id' => $user->id,

                'type' => $request->input('type'),
                'tax_number' => $request->input('tax_number'),
                'currency_id' => $request->input('currency_id'),
                'credit_limit' => $request->input('credit_limit'),
                'payment_terms_days' => $request->input('payment_terms_days'),
                'ap_account_id' => $request->input('ap_account_id'),
            ]);

            // $supplier->fresh();

            $supplier->load([
                'user',
                'user.roles',
                'vehicles'
            ]);

            DB::commit();

            $resource = new SupplierResource($supplier);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Request $request, int $supplierId)
    {
        $supplierEntity = SupplierModel::with(['user', 'user.roles', 'vehicles'])->findOrFail($supplierId);

        $resource = new SupplierResource($supplierEntity);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function update(UpdateSupplierRequest $request, int $supplierId)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $foundSupplier = SupplierModel::with(['user'])->findOrFail($supplierId);

        // $payload = $request->validated();
        $payload = [
            'tenant_id' => $tenant_id,
            'organization_unit_id' => $organization_unit_id,
            'first_name' => $request->input('user.first_name'),
            'last_name' => $request->input('user.last_name'),
            'email' => $request->input('user.email'),
            'password' => $request->input('user.password'),
            'phone' => $request->input('user.phone'),
            'preferences' => $request->input('user.preferences'),
            'date_of_birth' => $request->input('user.date_of_birth'),
            'gender' => $request->input('user.gender'),
            'marital_status' => $request->input('user.marital_status'),
            'status' => $request->input('status'),
        ];

        $oldAvatarPath = $foundSupplier->user->avatar_path;
        $newAvatarPath = null;

        if ($request->hasFile('user.avatar_file')) {
            $newAvatarPath = $this->storeImage(
                $request->file('user.avatar_file'),
                (int) $tenant_id,
                'users'
            );

            $payload['avatar_path'] = $newAvatarPath;
        }

        DB::beginTransaction();

        try {
            $foundSupplier->user->update($payload);

            $foundSupplier->user->roles()->sync($request->input('user.roles', []));

            $foundSupplier->update([
                'tenant_id' => $foundSupplier->user->tenant_id,
                'organization_unit_id' => $foundSupplier->user->organization_unit_id,
                'code' => $request->input('code'),
                'registration_number' => $request->input('registration_number'),
                'status' => $foundSupplier->user->status,
                'notes' => $request->input('notes'),
                'user_id' => $foundSupplier->user->id,

                'type' => $request->input('type'),
                'tax_number' => $request->input('tax_number'),
                'currency_id' => $request->input('currency_id'),
                'credit_limit' => $request->input('credit_limit'),
                'payment_terms_days' => $request->input('payment_terms_days'),
                'ap_account_id' => $request->input('ap_account_id'),
            ]);

            $foundSupplier->fresh();

            $foundSupplier->load([
                'user',
                'user.roles'
            ]);

            DB::commit();

            $resource = new SupplierResource($foundSupplier);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        } finally {
            if ($newAvatarPath !== null) {
                $this->deleteImageIfSafe($oldAvatarPath, (int) $payload['tenant_id'], 'users', $newAvatarPath);
            }
        }
    }

    public function delete(int $supplierId)
    {
        //
        $foundSupplier = SupplierModel::with(['user'])->findOrFail($supplierId);

        $this->deleteImageIfSafe($foundSupplier->user->avatar_path, $foundSupplier->tenant_id, 'users');

        $foundSupplier->delete();
    }

    private function storeImage(UploadedFile $image, int $tenantId, string $baseDirectory): string
    {
        return $this->fileStorageService->storeFile($image, "{$baseDirectory}/{$tenantId}");
    }

    private function deleteImageIfSafe(?string $imagePath, int $tenantId, string $baseDirectory, ?string $excludePath = null): void
    {
        if ($imagePath === null || $imagePath === '' || $imagePath === $excludePath) {
            return;
        }

        $expectedPrefix = "{$baseDirectory}/{$tenantId}/";

        if (! str_starts_with($imagePath, $expectedPrefix)) {
            return;
        }

        if ($this->fileStorageService->exists($imagePath)) {
            $this->fileStorageService->delete($imagePath);
        }
    }

    public function assignVehicle(Request $request)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $payload = $request->validated();
        $payload['tenant_id'] = $tenant_id;
        $payload['organization_unit_id'] = $organization_unit_id;

        $supplierVehicle = SupplierVehicleModel::create($payload);

        if ($supplierVehicle->is_current) {
            $this->clearCurrentForSupplierVehicle($supplierVehicle->vehicle_id, $supplierVehicle->supplier_id);
        }

        return response()->json([])->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function removeVehicle(int $supplierVehicleId)
    {
        //
        $foundSupplierVehicle = SupplierVehicleModel::findOrFail($supplierVehicleId);

        $foundSupplierVehicle->delete();
    }

    private function clearCurrentForSupplierVehicle(int $vehicleId, ?int $excludeSupplierId = null): void
    {
        $query = SupplierVehicleModel::where('vehicleId', $vehicleId)->where('is_current', true);

        if ($excludeSupplierId !== null) {
            $query->where('supplier_id', '!=', $excludeSupplierId);
        }

        $query->update(['is_current' => false]);
    }

    public function vehicles(Request $request, int $supplierId)
    {
        //
        $validated = $request->all();

        $pageName = config('core.pagination.page_name', 'page');
        $perPage = (int) ($validated['per_page'] ?? 15);
        $page = (int) ($validated[$pageName] ?? 1);
        $page = $page ?: Paginator::resolveCurrentPage($pageName);
        $columns = ['*'];

        $foundSupplier = SupplierModel::findOrFail($supplierId);

        $foundSupplierVehicles = $foundSupplier->vehicles()->paginate($perPage, $columns, $pageName, $page);

        $resource = new VehicleCollectionResource($foundSupplierVehicles);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }
}
