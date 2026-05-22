<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Http\Resources\AccountCollectionResource;
use App\Http\Resources\AccountResource;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;

class AccountController extends Controller
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

        $accounts = AccountModel::with(['parent', 'children'])->paginate($perPage, $columns, $pageName, $page);

        $resource = new AccountCollectionResource($accounts);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function store(StoreAccountRequest $request)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $payload = $request->validated();
        $payload['tenant_id'] = $tenant_id;
        $payload['organization_unit_id'] = $organization_unit_id;

        DB::beginTransaction();

        try {
            $account = AccountModel::create($payload);


            // $account->fresh();

            $account->load([
                'parent', 'children'
            ]);

            DB::commit();

            $resource = new AccountResource($account);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Request $request, int $accountId)
    {
        $account = AccountModel::with(['parent', 'children'])->findOrFail($accountId);

        $resource = new AccountResource($account);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function update(UpdateAccountRequest $request, int $accountId)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $foundAccount = AccountModel::with(['parent', 'children'])->findOrFail($accountId);

        $payload = $request->validated();
        $payload['tenant_id'] = $tenant_id;
        $payload['organization_unit_id'] = $organization_unit_id;

        DB::beginTransaction();

        try {
            $foundAccount->update($payload);

            $foundAccount->fresh();

            $foundAccount->load([
                'parent', 'children'
            ]);

            DB::commit();

            $resource = new AccountResource($foundAccount);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(int $accountId)
    {
        //
        $foundAccount = AccountModel::findOrFail($accountId);

        $foundAccount->delete();
    }
}
