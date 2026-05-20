<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerCollectionResource;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\VehicleCollectionResource;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerVehicleModel;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

class CustomerController extends Controller
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

        $customers = CustomerModel::with(['user', 'user.roles', 'vehicles'])->paginate($perPage, $columns, $pageName, $page);

        $resource = new CustomerCollectionResource($customers);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function store(StoreCustomerRequest $request)
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

            $customer = CustomerModel::create([
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
                'ar_account_id' => $request->input('ar_account_id'),
            ]);

            // $customer->fresh();

            $customer->load([
                'user',
                'user.roles',
                'vehicles'
            ]);

            DB::commit();

            $resource = new CustomerResource($customer);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Request $request, int $customerId)
    {
        $customerEntity = CustomerModel::with(['user', 'user.roles', 'vehicles'])->findOrFail($customerId);

        $resource = new CustomerResource($customerEntity);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function update(UpdateCustomerRequest $request, int $customerId)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $foundCustomer = CustomerModel::with(['user'])->findOrFail($customerId);

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

        $oldAvatarPath = $foundCustomer->user->avatar_path;
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
            $foundCustomer->user->update($payload);

            $foundCustomer->user->roles()->sync($request->input('user.roles', []));

            $foundCustomer->update([
                'tenant_id' => $foundCustomer->user->tenant_id,
                'organization_unit_id' => $foundCustomer->user->organization_unit_id,
                'code' => $request->input('code'),
                'registration_number' => $request->input('registration_number'),
                'status' => $foundCustomer->user->status,
                'notes' => $request->input('notes'),
                'user_id' => $foundCustomer->user->id,

                'type' => $request->input('type'),
                'tax_number' => $request->input('tax_number'),
                'currency_id' => $request->input('currency_id'),
                'credit_limit' => $request->input('credit_limit'),
                'payment_terms_days' => $request->input('payment_terms_days'),
                'ar_account_id' => $request->input('ar_account_id'),
            ]);

            $foundCustomer->fresh();

            $foundCustomer->load([
                'user',
                'user.roles'
            ]);

            DB::commit();

            $resource = new CustomerResource($foundCustomer);

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

    public function delete(int $customerId)
    {
        //
        $foundCustomer = CustomerModel::with(['user'])->findOrFail($customerId);

        $this->deleteImageIfSafe($foundCustomer->user->avatar_path, $foundCustomer->tenant_id, 'users');

        $foundCustomer->delete();
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

        $customerVehicle = CustomerVehicleModel::create($payload);

        if ($customerVehicle->is_current) {
            $this->clearCurrentForCustomerVehicle($customerVehicle->vehicle_id, $customerVehicle->customer_id);
        }

        return response()->json([])->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function removeVehicle(int $customerVehicleId)
    {
        //
        $foundCustomerVehicle = CustomerVehicleModel::findOrFail($customerVehicleId);

        $foundCustomerVehicle->delete();
    }

    private function clearCurrentForCustomerVehicle(int $vehicleId, ?int $excludeCustomerId = null): void
    {
        $query = CustomerVehicleModel::where('vehicleId', $vehicleId)->where('is_current', true);

        if ($excludeCustomerId !== null) {
            $query->where('customer_id', '!=', $excludeCustomerId);
        }

        $query->update(['is_current' => false]);
    }

    public function vehicles(Request $request, int $customerId)
    {
        //
        $validated = $request->all();

        $pageName = config('core.pagination.page_name', 'page');
        $perPage = (int) ($validated['per_page'] ?? 15);
        $page = (int) ($validated[$pageName] ?? 1);
        $page = $page ?: Paginator::resolveCurrentPage($pageName);
        $columns = ['*'];

        $foundCustomer = CustomerModel::findOrFail($customerId);

        $foundCustomerVehicles = $foundCustomer->vehicles()->paginate($perPage, $columns, $pageName, $page);

        $resource = new VehicleCollectionResource($foundCustomerVehicles);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }
}
