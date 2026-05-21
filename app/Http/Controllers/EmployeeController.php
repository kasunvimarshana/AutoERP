<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeCollectionResource;
use App\Http\Resources\EmployeeResource;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

class EmployeeController extends Controller
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

        $employees = EmployeeModel::with(['user', 'department', 'designation', 'type'])->paginate($perPage, $columns, $pageName, $page);

        $resource = new EmployeeCollectionResource($employees);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function store(StoreEmployeeRequest $request)
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

            $employee = EmployeeModel::create([
                'tenant_id' => $user->tenant_id,
                'organization_unit_id' => $user->organization_unit_id,
                'code' => $request->input('code'),
                'registration_number' => $request->input('registration_number'),
                'status' => $user->status,
                'notes' => $request->input('notes'),
                'user_id' => $user->id,

                'department_id' => $request->input('department_id'),
                'designation_id' => $request->input('designation_id'),
                'employment_type_id' => $request->input('employment_type_id'),
                'hire_date' => $request->input('hire_date'),
                'confirmation_date' => $request->input('confirmation_date'),
                'termination_date' => $request->input('termination_date'),
                'termination_reason' => $request->input('termination_reason'),
                'personal_email' => $request->input('personal_email'),
                'phone' => $request->input('phone'),
                'mobile' => $request->input('mobile'),
                'address_line1' => $request->input('address_line1'),
                'address_line2' => $request->input('address_line2'),
                'city' => $request->input('city'),
                'state' => $request->input('state'),
                'postal_code' => $request->input('postal_code'),
                'country_id' => $request->input('country_id'),
                'tax_number' => $request->input('tax_number'),
                'social_security_number' => $request->input('social_security_number'),
                'bank_name' => $request->input('bank_name'),
                'bank_account_number' => $request->input('bank_account_number'),
                'bank_routing_number' => $request->input('bank_routing_number'),
            ]);

            // $employee->fresh();

            $employee->load([
                'user',
                'department',
                'designation',
                'type'
            ]);

            DB::commit();

            $resource = new EmployeeResource($employee);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Request $request, int $employeeId)
    {
        $employeeEntity = EmployeeModel::with(['user', 'department', 'designation', 'type'])->findOrFail($employeeId);

        $resource = new EmployeeResource($employeeEntity);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function update(UpdateEmployeeRequest $request, int $employeeId)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $foundEmployee = EmployeeModel::with(['user'])->findOrFail($employeeId);

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

        $oldAvatarPath = $foundEmployee->user->avatar_path;
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
            $foundEmployee->user->update($payload);

            $foundEmployee->user->roles()->sync($request->input('user.roles', []));

            $foundEmployee->update([
                'tenant_id' => $foundEmployee->user->tenant_id,
                'organization_unit_id' => $foundEmployee->user->organization_unit_id,
                'code' => $request->input('code'),
                'registration_number' => $request->input('registration_number'),
                'status' => $foundEmployee->user->status,
                'notes' => $request->input('notes'),
                'user_id' => $foundEmployee->user->id,

                'department_id' => $request->input('department_id'),
                'designation_id' => $request->input('designation_id'),
                'employment_type_id' => $request->input('employment_type_id'),
                'hire_date' => $request->input('hire_date'),
                'confirmation_date' => $request->input('confirmation_date'),
                'termination_date' => $request->input('termination_date'),
                'termination_reason' => $request->input('termination_reason'),
                'personal_email' => $request->input('personal_email'),
                'phone' => $request->input('phone'),
                'mobile' => $request->input('mobile'),
                'address_line1' => $request->input('address_line1'),
                'address_line2' => $request->input('address_line2'),
                'city' => $request->input('city'),
                'state' => $request->input('state'),
                'postal_code' => $request->input('postal_code'),
                'country_id' => $request->input('country_id'),
                'tax_number' => $request->input('tax_number'),
                'social_security_number' => $request->input('social_security_number'),
                'bank_name' => $request->input('bank_name'),
                'bank_account_number' => $request->input('bank_account_number'),
                'bank_routing_number' => $request->input('bank_routing_number'),
            ]);

            $foundEmployee->fresh();

            $foundEmployee->load([
                'user',
                'department',
                'designation',
                'type'
            ]);

            DB::commit();

            $resource = new EmployeeResource($foundEmployee);

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

    public function delete(int $employeeId)
    {
        //
        $foundEmployee = EmployeeModel::with(['user'])->findOrFail($employeeId);

        $this->deleteImageIfSafe($foundEmployee->user->avatar_path, $foundEmployee->tenant_id, 'users');

        $foundEmployee->delete();
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
}
