export type StockMovementType = 'receipt' | 'issue' | 'consumption' | 'transfer_in' | 'transfer_out' | 'adjustment_in' | 'adjustment_out' | 'return_in' | 'return_out';

export type StockLevel = {
    available: string;
    batchOrSerial: string;
    id: string;
    itemCode: string;
    itemName: string;
    location: string;
    onHand: string;
    reserved: string;
    status: string;
    uom: string;
    updatedAt: string;
    warehouse: string;
};

export type StockMovement = {
    batchOrSerial?: string;
    costEffect: string;
    id: string;
    itemName: string;
    location: string;
    movementDate: string;
    movementNumber: string;
    movementType: StockMovementType;
    quantity: string;
    quantityEffect: string;
    sourceModule: string;
    sourceReference: string;
    status: string;
    uom: string;
    warehouse: string;
};

export type StockReservation = {
    availableDecision: string;
    expiresAt?: string;
    id: string;
    itemName: string;
    quantity: string;
    reservedFor: string;
    sourceModule: string;
    sourceReference: string;
    status: string;
    uom: string;
    warehouse: string;
};

export type StockTransferLine = {
    batchOrSerial?: string;
    id: string;
    itemName: string;
    requestedQuantity: string;
    uom: string;
};

export type StockTransferFormInput = {
    fromLocationId?: string;
    fromWarehouseId: string;
    lines: Array<{
        itemId: string;
        quantity: string;
        toLocationId?: string;
        uomId: string;
    }>;
    notes?: string;
    referenceNumber?: string;
    status?: string;
    toLocationId?: string;
    toWarehouseId: string;
};

export type StockTransfer = {
    destinationLocation: string;
    destinationWarehouse: string;
    id: string;
    lines: StockTransferLine[];
    reason: string;
    sourceLocation: string;
    sourceWarehouse: string;
    status: string;
    transferDate: string;
    transferNumber: string;
};

export type StockAdjustmentLine = {
    adjustmentType: 'increase' | 'decrease';
    batchOrSerial?: string;
    id: string;
    itemName: string;
    quantity: string;
    quantityImpact: string;
    uom: string;
};

export type StockAdjustment = {
    adjustmentDate: string;
    adjustmentNumber: string;
    id: string;
    lines: StockAdjustmentLine[];
    location: string;
    reason: string;
    status: string;
    warehouse: string;
};

export type StockAdjustmentFormInput = {
    lines: Array<{
        adjustmentQuantity: string;
        direction: 'DECREASE' | 'INCREASE';
        itemId: string;
        uomId: string;
    }>;
    locationId?: string;
    reason?: string;
    referenceNumber?: string;
    status?: string;
    type?: string;
    warehouseId: string;
};

export type CycleCount = {
    countNumber: string;
    countedDate?: string;
    id: string;
    lineSummary: string;
    scheduledDate: string;
    status: string;
    variance: string;
    warehouse: string;
};

export type CycleCountLine = {
    countedQuantity: string;
    expectedQuantity: string;
    id: string;
    itemName: string;
    variance: string;
};

export type InventoryBatch = {
    availableQuantity: string;
    batchNumber: string;
    expiryDate?: string;
    id: string;
    itemName: string;
    location: string;
    sourceReference: string;
    status: string;
    warehouse: string;
};

export type InventorySerial = {
    id: string;
    itemName: string;
    location: string;
    serialNumber: string;
    sourceReference: string;
    status: string;
    warehouse: string;
};

export type ReceiptInspection = {
    id: string;
    inspectionNumber: string;
    itemName: string;
    result: string;
    sourceReference: string;
    status: string;
};

export type PutAwayTask = {
    destinationLocation: string;
    id: string;
    itemName: string;
    quantity: string;
    sourceReference: string;
    status: string;
};

export type PickingTask = {
    id: string;
    itemName: string;
    quantity: string;
    sourceReference: string;
    status: string;
    warehouse: string;
};

export type InventoryValuation = {
    id: string;
    itemName: string;
    latestCostLayer: string;
    quantity: string;
    totalValue: string;
    unitCost: string;
    updatedAt: string;
    valuationMethod: string;
    warehouse: string;
};

export type CostLayer = {
    id: string;
    itemName: string;
    layerDate: string;
    quantity: string;
    remainingQuantity: string;
    sourceReference: string;
    unitCost: string;
};

export type StockAvailabilityPreviewRequest = {
    batchOrSerial?: string;
    itemId: string;
    location?: string;
    quantity: string;
    sourceModule?: string;
    uom: string;
    warehouse: string;
};

export type StockAvailabilityPreviewResult = {
    breakdown: Array<{ label: string; value: string }>;
    calculated: {
        availableQuantity: string;
        decision: 'available' | 'insufficient' | 'requires_batch_or_serial';
        requestedQuantity: string;
        reservedQuantity: string;
    };
    errors: string[];
    input: StockAvailabilityPreviewRequest | Record<string, unknown>;
    warnings: string[];
};

export type InventorySourceReference = {
    id: string;
    sourceModule: string;
    sourceReference: string;
    sourceType: string;
};

export type InventoryAuditEntry = {
    actor: string;
    description: string;
    id: string;
    time: string;
    type: string;
};

export type InventoryLookupOption = {
    id: string;
    label: string;
    secondary?: string;
};
