<?php
use Illuminate\Support\Facades\Route;
use Modules\OrgUnit\Application\Services\OrgUnitService;

Route::prefix('api')->group(function () {
    Route::prefix('org-units')->group(function () {
        Route::get('/', fn(OrgUnitService $s) =>
            response()->json($s->findAllByTenant(request()->integer('tenant_id')))
        );
        Route::get('/tree', fn(OrgUnitService $s) =>
            response()->json($s->getTree(request()->integer('tenant_id')))
        );
        Route::get('/{id}', fn(int $id, OrgUnitService $s) =>
            response()->json($s->findById($id))
        );
        Route::get('/{id}/children', fn(int $id, OrgUnitService $s) =>
            response()->json($s->getChildren(request()->integer('tenant_id'), $id))
        );
        Route::get('/{id}/descendants', fn(int $id, OrgUnitService $s) =>
            response()->json($s->getDescendants($id))
        );
        Route::get('/{id}/ancestors', fn(int $id, OrgUnitService $s) =>
            response()->json($s->getAncestors($id))
        );
        Route::post('/', fn(OrgUnitService $s) =>
            response()->json($s->create(request()->all()), 201)
        );
        Route::put('/{id}', fn(int $id, OrgUnitService $s) =>
            response()->json($s->update($id, request()->all()))
        );
        Route::patch('/{id}/move', fn(int $id, OrgUnitService $s) =>
            response()->json($s->move($id, request()->input('parent_id') ? (int) request()->input('parent_id') : null))
        );
        Route::patch('/{id}/activate', fn(int $id, OrgUnitService $s) =>
            response()->json($s->activate($id))
        );
        Route::patch('/{id}/deactivate', fn(int $id, OrgUnitService $s) =>
            response()->json($s->deactivate($id))
        );
        Route::delete('/{id}', fn(int $id, OrgUnitService $s) => [
            $s->delete($id), response()->json(null, 204)
        ][1]);
    });
});
