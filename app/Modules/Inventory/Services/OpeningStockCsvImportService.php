<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\OpeningStockImportPreviewData;
use Modules\Inventory\DTOs\StockAdjustmentData;
use Modules\Inventory\DTOs\StockAdjustmentLineData;
use Modules\Inventory\Enums\AdjustmentType;
use Modules\Inventory\Models\InventoryAdjustment;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Models\InventorySerialNumber;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Validators\InventoryValidationService;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\UOM\Models\UnitOfMeasureModel;

final class OpeningStockCsvImportService
{
    public const FILE_FIELD = 'csv_file';

    private const ZERO = '0.000000';

    private const INVALID_ROW_MESSAGE_LIMIT = 10;

    private const IMPORT_REASON = 'Opening stock CSV import';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly InventoryValidationService $validator,
        private readonly InventoryUomService $uoms,
        private readonly StockAdjustmentService $adjustments,
        private readonly OpeningStockCsvReader $reader,
    ) {}

    public function preview(
        UploadedFile $file,
        int $tenantId,
        ?int $organizationUnitId,
        int $warehouseId,
        ?int $warehouseLocationId,
    ): OpeningStockImportPreviewData {
        $warehouse = $this->validator->warehouse($tenantId, $organizationUnitId, $warehouseId);
        $this->validator->location($warehouse, $warehouseLocationId);

        $rows = [];
        $lines = [];
        $seenDimensions = [];

        foreach ($this->reader->rows($file) as $parsedRow) {
            $row = $this->inspectRow(
                $parsedRow['row_number'],
                $parsedRow['values'],
                $parsedRow['errors'],
                $tenantId,
                $organizationUnitId,
                $warehouseId,
                $warehouseLocationId,
                $seenDimensions,
            );
            $rows[] = $row['preview'];
            if ($row['line'] instanceof StockAdjustmentLineData) {
                $lines[] = $row['line'];
            }
        }

        if ($rows === []) {
            throw new InvalidArgumentException('Opening stock CSV must contain at least one data row.');
        }

        return new OpeningStockImportPreviewData(
            fileName: $file->getClientOriginalName(),
            rows: $rows,
            lines: $lines,
        );
    }

    public function createDraft(
        UploadedFile $file,
        int $tenantId,
        ?int $organizationUnitId,
        string $adjustmentDate,
        int $warehouseId,
        ?int $warehouseLocationId,
        ?int $createdBy,
    ): InventoryAdjustment {
        $preview = $this->preview(
            $file,
            $tenantId,
            $organizationUnitId,
            $warehouseId,
            $warehouseLocationId,
        );

        if (! $preview->isValid()) {
            $messages = [];
            foreach ($preview->rows as $row) {
                if ($row['errors'] === []) {
                    continue;
                }
                $messages[] = 'Row '.$row['row_number'].': '.implode(' ', $row['errors']);
                if (count($messages) >= self::INVALID_ROW_MESSAGE_LIMIT) {
                    break;
                }
            }

            throw ValidationException::withMessages([
                self::FILE_FIELD => array_merge(
                    ['Opening stock CSV contains invalid rows. Correct them and preview the file again.'],
                    $messages,
                ),
            ]);
        }

        return $this->adjustments->create(new StockAdjustmentData(
            tenantId: $tenantId,
            adjustmentDate: $adjustmentDate,
            adjustmentType: AdjustmentType::OpeningBalance,
            warehouseId: $warehouseId,
            organizationUnitId: $organizationUnitId,
            warehouseLocationId: $warehouseLocationId,
            reason: self::IMPORT_REASON,
            notes: 'Imported from '.$preview->fileName.'.',
            createdBy: $createdBy,
            lines: $preview->lines,
        ));
    }

    /**
     * @param  list<string>  $initialErrors
     * @param  array<string, true>  $seenDimensions
     * @return array{preview: array<string, mixed>, line: StockAdjustmentLineData|null}
     */
    private function inspectRow(
        int $rowNumber,
        array $values,
        array $initialErrors,
        int $tenantId,
        ?int $organizationUnitId,
        int $warehouseId,
        ?int $warehouseLocationId,
        array &$seenDimensions,
    ): array {
        $errors = $initialErrors;
        $preview = [
            'row_number' => $rowNumber,
            'item_code' => $values['item_code'],
            'item_name' => null,
            'variant_code' => $values['variant_code'],
            'variant_name' => null,
            'uom_code' => $values['uom_code'],
            'uom_name' => null,
            'opening_quantity' => $values['opening_quantity'],
            'unit_cost' => $values['unit_cost'],
            'base_quantity' => null,
            'base_unit_cost' => null,
            'batch_number' => $values['batch_number'],
            'serial_number' => $values['serial_number'],
            'reason' => $values['reason'],
            'errors' => [],
        ];

        if ($errors !== []) {
            $preview['errors'] = array_values(array_unique($errors));

            return ['preview' => $preview, 'line' => null];
        }

        try {
            $itemCode = $this->required($values['item_code'], 'Item code is required.');
            $openingQuantity = $this->decimal($values['opening_quantity'], 'Opening quantity', true);
            $unitCost = $this->decimal($values['unit_cost'], 'Unit cost', false);

            $item = Item::query()
                ->forTenant($tenantId, $organizationUnitId)
                ->where('code', $itemCode)
                ->first();
            if (! $item instanceof Item) {
                throw new InvalidArgumentException("Item code {$itemCode} was not found for this organization.");
            }
            $item = $this->validator->item($tenantId, $organizationUnitId, (int) $item->getKey());
            $this->validator->assertStockable($item);
            if ($item->base_uom_id === null) {
                throw new InvalidArgumentException(
                    "Item {$item->code} requires a base UOM before opening stock can be imported.",
                );
            }
            $preview['item_name'] = (string) $item->name;

            $variant = $this->variant($item, $values['variant_code']);
            $variantId = $variant?->getKey();
            $preview['variant_name'] = $variant?->name;

            $uom = $this->uom($tenantId, $organizationUnitId, $item, $values['uom_code']);
            $uomId = $uom?->getKey();
            $preview['uom_code'] = $uom?->code;
            $preview['uom_name'] = $uom?->name;

            $batch = $this->batch($item, $variantId === null ? null : (int) $variantId, $values['batch_number']);
            $batchId = $batch?->getKey();
            $serial = $this->serial($item, $values['serial_number']);
            $serialId = $serial?->getKey();

            $basis = $this->uoms->basis(
                $tenantId,
                $organizationUnitId,
                $item,
                $uomId === null ? null : (int) $uomId,
                $openingQuantity,
                $unitCost,
            );
            $this->validator->batch($item, $batchId === null ? null : (int) $batchId, $variantId === null ? null : (int) $variantId);
            $this->validator->serial(
                $item,
                $serialId === null ? null : (int) $serialId,
                $basis->baseQuantity,
                $variantId === null ? null : (int) $variantId,
                $batchId === null ? null : (int) $batchId,
            );

            $preview['base_quantity'] = $basis->baseQuantity;
            $preview['base_unit_cost'] = $basis->baseUnitCost;

            $currentQuantity = $this->currentQuantity(
                $tenantId,
                (int) $item->getKey(),
                $variantId === null ? null : (int) $variantId,
                $warehouseId,
                $warehouseLocationId,
                $batchId === null ? null : (int) $batchId,
            );
            if (! $this->math->isZero($currentQuantity)) {
                throw new InvalidArgumentException(
                    'Opening stock can only be imported when the current stock for this item and dimensions is zero. Use a recount adjustment instead.',
                );
            }

            $dimensionKey = implode(':', [
                $item->getKey(),
                $variantId ?? 'null',
                $batchId ?? 'null',
                $serialId ?? 'null',
            ]);
            if (isset($seenDimensions[$dimensionKey])) {
                throw new InvalidArgumentException('Duplicate item and stock-dimension combination in the CSV.');
            }
            $seenDimensions[$dimensionKey] = true;

            $line = new StockAdjustmentLineData(
                itemId: (int) $item->getKey(),
                systemQuantity: self::ZERO,
                countedQuantity: $openingQuantity,
                adjustmentQuantity: $openingQuantity,
                unitCost: $unitCost,
                itemVariantId: $variantId === null ? null : (int) $variantId,
                batchId: $batchId === null ? null : (int) $batchId,
                serialNumberId: $serialId === null ? null : (int) $serialId,
                reason: $values['reason'] !== '' ? $values['reason'] : null,
                uomId: $uomId === null ? null : (int) $uomId,
            );
        } catch (InvalidArgumentException $exception) {
            $errors[] = $exception->getMessage();
            $line = null;
        }

        $preview['errors'] = array_values(array_unique($errors));
        if ($preview['errors'] !== []) {
            $line = null;
        }

        return ['preview' => $preview, 'line' => $line];
    }

    private function variant(Item $item, string $code): ?ItemVariant
    {
        if ($code === '') {
            return null;
        }

        $variant = ItemVariant::query()
            ->where('tenant_id', $item->tenant_id)
            ->where('item_id', $item->getKey())
            ->where('code', $code)
            ->where('is_active', true)
            ->first();
        if (! $variant instanceof ItemVariant) {
            throw new InvalidArgumentException("Variant code {$code} was not found or is inactive for item {$item->code}.");
        }

        $this->validator->variant($item, (int) $variant->getKey());

        return $variant;
    }

    private function uom(
        int $tenantId,
        ?int $organizationUnitId,
        Item $item,
        string $code,
    ): ?UnitOfMeasureModel {
        if ($code === '') {
            return $item->baseUom()->first();
        }

        $uom = UnitOfMeasureModel::query()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->where('is_active', true)
            ->where(function (Builder $query) use ($organizationUnitId): void {
                $query->whereNull('organization_unit_id');
                if ($organizationUnitId !== null) {
                    $query->orWhere('organization_unit_id', $organizationUnitId);
                }
            })
            ->first();
        if (! $uom instanceof UnitOfMeasureModel) {
            throw new InvalidArgumentException("UOM code {$code} was not found or is inactive for this organization.");
        }

        return $uom;
    }

    private function batch(Item $item, ?int $variantId, string $number): ?InventoryBatch
    {
        if ($number === '') {
            return null;
        }

        $batch = InventoryBatch::query()
            ->where('tenant_id', $item->tenant_id)
            ->where('item_id', $item->getKey())
            ->where('batch_number', $number)
            ->first();
        if (! $batch instanceof InventoryBatch) {
            throw new InvalidArgumentException("Batch number {$number} was not found for item {$item->code}.");
        }

        $this->validator->batch($item, (int) $batch->getKey(), $variantId);

        return $batch;
    }

    private function serial(Item $item, string $number): ?InventorySerialNumber
    {
        if ($number === '') {
            return null;
        }

        $serial = InventorySerialNumber::query()
            ->where('tenant_id', $item->tenant_id)
            ->where('serial_number', $number)
            ->first();
        if (! $serial instanceof InventorySerialNumber) {
            throw new InvalidArgumentException("Serial number {$number} was not found.");
        }

        return $serial;
    }

    private function currentQuantity(
        int $tenantId,
        int $itemId,
        ?int $variantId,
        int $warehouseId,
        ?int $warehouseLocationId,
        ?int $batchId,
    ): string {
        $query = InventoryStockBalance::query()
            ->where('tenant_id', $tenantId)
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId);

        $this->whereNullable($query, 'item_variant_id', $variantId);
        $this->whereNullable($query, 'warehouse_location_id', $warehouseLocationId);
        $this->whereNullable($query, 'batch_id', $batchId);

        return $this->math->normalize((string) $query->sum('quantity_on_hand'));
    }

    private function whereNullable(Builder $query, string $column, ?int $value): void
    {
        if ($value === null) {
            $query->whereNull($column);

            return;
        }

        $query->where($column, $value);
    }

    private function required(string $value, string $message): string
    {
        if ($value === '') {
            throw new InvalidArgumentException($message);
        }

        return $value;
    }

    private function decimal(string $value, string $label, bool $positive): string
    {
        if ($value === '') {
            throw new InvalidArgumentException($label.' is required.');
        }

        try {
            $normalized = $this->math->normalize($value);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException($label.' must be a plain number with no more than six decimal places.');
        }

        if ($positive && $this->math->compare($normalized, self::ZERO) <= 0) {
            throw new InvalidArgumentException($label.' must be greater than zero.');
        }
        if (! $positive && $this->math->isNegative($normalized)) {
            throw new InvalidArgumentException($label.' cannot be negative.');
        }

        return $normalized;
    }
}
