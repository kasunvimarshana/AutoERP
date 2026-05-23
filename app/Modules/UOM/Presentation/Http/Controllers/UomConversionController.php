<?php

declare(strict_types=1);

namespace Modules\UOM\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\UOM\Application\DTOs\UomConversionData;
use Modules\UOM\Application\Services\UomService;
use Modules\UOM\Domain\Exceptions\UomIntegrityException;
use Modules\UOM\Domain\Exceptions\UomRecordNotFoundException;
use Modules\UOM\Presentation\Http\Controllers\Concerns\HandlesUomHttp;
use Modules\UOM\Presentation\Http\Requests\ConvertUomRequest;
use Modules\UOM\Presentation\Http\Requests\StoreUomConversionRequest;
use Modules\UOM\Presentation\Http\Requests\UpdateUomConversionRequest;
use Modules\UOM\Presentation\Http\Resources\UomConversionResource;

class UomConversionController extends Controller
{
    use HandlesUomHttp;

    public function __construct(private readonly UomService $uoms) {}

    public function index(Request $request, int|string $tenant): mixed
    {
        try {
            return UomConversionResource::collection(
                $this->uoms->listConversions($tenant, $this->filters($request, [
                    'organization_unit_id',
                    'from_uom_id',
                    'to_uom_id',
                    'item_id',
                    'is_active',
                    'is_bidirectional',
                ]), $this->perPage($request))
            );
        } catch (UomRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(StoreUomConversionRequest $request, int|string $tenant): JsonResponse
    {
        try {
            $record = $this->uoms->createConversion(UomConversionData::fromArray($tenant, $request->validated()));

            return (new UomConversionResource($record))->response()->setStatusCode(201);
        } catch (UomIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (UomRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, int|string $conversion): UomConversionResource|JsonResponse
    {
        try {
            return new UomConversionResource($this->uoms->findConversion($tenant, $conversion));
        } catch (UomRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateUomConversionRequest $request, int|string $tenant, int|string $conversion): UomConversionResource|JsonResponse
    {
        try {
            return new UomConversionResource($this->uoms->updateConversion($tenant, $conversion, UomConversionData::fromArray($tenant, $request->validated())));
        } catch (UomIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (UomRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, int|string $conversion): JsonResponse
    {
        try {
            $this->uoms->deleteConversion($tenant, $conversion);

            return response()->json(null, 204);
        } catch (UomIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (UomRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function convert(ConvertUomRequest $request, int|string $tenant): JsonResponse
    {
        $data = $request->validated();

        try {
            $converted = $this->uoms->convert(
                $tenant,
                $data['from_uom_id'],
                $data['to_uom_id'],
                $data['quantity'],
                $data['item_id'] ?? null
            );

            return response()->json([
                'data' => [
                    'tenant_id' => (int) $tenant,
                    'from_uom_id' => (int) $data['from_uom_id'],
                    'to_uom_id' => (int) $data['to_uom_id'],
                    'item_id' => isset($data['item_id']) ? (int) $data['item_id'] : null,
                    'quantity' => (string) $data['quantity'],
                    'converted_quantity' => $converted,
                ],
            ]);
        } catch (UomIntegrityException $exception) {
            return $this->unprocessable($exception);
        } catch (UomRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
