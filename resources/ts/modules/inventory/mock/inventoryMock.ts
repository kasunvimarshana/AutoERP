import type {
    CostLayer,
    CycleCount,
    InventoryAuditEntry,
    InventoryBatch,
    InventorySerial,
    InventoryValuation,
    PickingTask,
    PutAwayTask,
    ReceiptInspection,
    StockAdjustment,
    StockAvailabilityPreviewResult,
    StockLevel,
    StockMovement,
    StockReservation,
    StockTransfer,
} from '../types/inventory.types';

export const inventoryDashboardMetrics = [
    { label: 'Active stock items', value: '1,284', status: 'active' },
    { label: 'Low stock', value: '37', status: 'warning' },
    { label: 'Out of stock', value: '11', status: 'danger' },
    { label: 'Pending reservations', value: '24', status: 'pending' },
    { label: 'Pending transfers', value: '8', status: 'pending' },
    { label: 'Pending adjustments', value: '5', status: 'draft' },
];

export const stockLevels: StockLevel[] = [
    { available: 'Backend provided', batchOrSerial: 'Batch tracked', id: 'sl-001', itemCode: 'OIL-10W40', itemName: 'Engine Oil 10W-40', location: 'A-01', onHand: 'Backend provided', reserved: 'Backend provided', status: 'active', uom: 'L', updatedAt: '2026-05-29 09:10', warehouse: 'Main Warehouse' },
    { available: 'Backend provided', batchOrSerial: 'None', id: 'sl-002', itemCode: 'FLT-OIL', itemName: 'Oil Filter', location: 'B-03', onHand: 'Backend provided', reserved: 'Backend provided', status: 'low_stock', uom: 'PCS', updatedAt: '2026-05-29 10:12', warehouse: 'Main Warehouse' },
    { available: 'Backend provided', batchOrSerial: 'Serial tracked', id: 'sl-003', itemCode: 'GPS-TRK', itemName: 'Rental GPS Tracker', location: 'R-02', onHand: 'Backend provided', reserved: 'Backend provided', status: 'out_of_stock', uom: 'PCS', updatedAt: '2026-05-28 16:45', warehouse: 'Rental Accessories' },
];

export const stockMovements: StockMovement[] = [
    { batchOrSerial: 'B-OIL-0526', costEffect: 'Backend valuation', id: 'mov-001', itemName: 'Engine Oil 10W-40', location: 'A-01', movementDate: '2026-05-22', movementNumber: 'MOV-2026-00101', movementType: 'receipt', quantity: 'Backend quantity', quantityEffect: 'Backend effect', sourceModule: 'purchase', sourceReference: 'GRN-2026-00051', status: 'posted', uom: 'L', warehouse: 'Main Warehouse' },
    { costEffect: 'Backend valuation', id: 'mov-002', itemName: 'Oil Filter', location: 'B-03', movementDate: '2026-05-23', movementNumber: 'MOV-2026-00102', movementType: 'issue', quantity: 'Backend quantity', quantityEffect: 'Backend effect', sourceModule: 'sales', sourceReference: 'GDN-2026-00077', status: 'posted', uom: 'PCS', warehouse: 'Main Warehouse' },
    { costEffect: 'Backend valuation', id: 'mov-003', itemName: 'Brake Cleaner', location: 'S-04', movementDate: '2026-05-24', movementNumber: 'MOV-2026-00103', movementType: 'consumption', quantity: 'Backend quantity', quantityEffect: 'Backend effect', sourceModule: 'vehicle_service', sourceReference: 'JOB-2026-00512', status: 'posted', uom: 'CAN', warehouse: 'Service Store' },
    { costEffect: 'Backend valuation', id: 'mov-004', itemName: 'Air Filter', location: 'A-02 to S-01', movementDate: '2026-05-25', movementNumber: 'MOV-2026-00104', movementType: 'transfer_out', quantity: 'Backend quantity', quantityEffect: 'Backend effect', sourceModule: 'inventory', sourceReference: 'TRF-2026-00018', status: 'posted', uom: 'PCS', warehouse: 'Main Warehouse' },
];

export const reservations: StockReservation[] = [
    { availableDecision: 'Backend decision', expiresAt: '2026-05-31', id: 'res-001', itemName: 'Oil Filter', quantity: 'Backend quantity', reservedFor: 'Sales delivery', sourceModule: 'sales', sourceReference: 'SO-2026-00231', status: 'active', uom: 'PCS', warehouse: 'Main Warehouse' },
    { availableDecision: 'Backend decision', id: 'res-002', itemName: 'Brake Pads', quantity: 'Backend quantity', reservedFor: 'Service job', sourceModule: 'vehicle_service', sourceReference: 'JOB-2026-00520', status: 'pending', uom: 'SET', warehouse: 'Service Store' },
];

export const transfers: StockTransfer[] = [
    { destinationLocation: 'S-01', destinationWarehouse: 'Service Store', id: 'trf-001', lines: [{ id: 'trfl-001', itemName: 'Air Filter', requestedQuantity: 'Backend quantity', uom: 'PCS' }], reason: 'Service replenishment', sourceLocation: 'A-02', sourceWarehouse: 'Main Warehouse', status: 'pending', transferDate: '2026-05-27', transferNumber: 'TRF-2026-00018' },
    { destinationLocation: 'R-02', destinationWarehouse: 'Rental Accessories', id: 'trf-002', lines: [{ id: 'trfl-002', itemName: 'Rental GPS Tracker', requestedQuantity: 'Backend quantity', uom: 'PCS' }], reason: 'Rental accessory pool', sourceLocation: 'A-04', sourceWarehouse: 'Main Warehouse', status: 'completed', transferDate: '2026-05-25', transferNumber: 'TRF-2026-00017' },
];

export const adjustments: StockAdjustment[] = [
    { adjustmentDate: '2026-05-26', adjustmentNumber: 'ADJ-2026-00012', id: 'adj-001', lines: [{ adjustmentType: 'decrease', id: 'adjl-001', itemName: 'Oil Filter', quantity: 'Backend quantity', quantityImpact: 'Backend impact', uom: 'PCS' }], location: 'B-03', reason: 'Cycle count variance', status: 'posted', warehouse: 'Main Warehouse' },
    { adjustmentDate: '2026-05-28', adjustmentNumber: 'ADJ-2026-00013', id: 'adj-002', lines: [{ adjustmentType: 'increase', id: 'adjl-002', itemName: 'Brake Cleaner', quantity: 'Backend quantity', quantityImpact: 'Backend impact', uom: 'CAN' }], location: 'S-04', reason: 'Found stock', status: 'draft', warehouse: 'Service Store' },
];

export const cycleCounts: CycleCount[] = [
    { countedDate: '2026-05-26', countNumber: 'CC-2026-00009', id: 'cc-001', lineSummary: '42 lines', scheduledDate: '2026-05-25', status: 'completed', variance: 'Backend variance', warehouse: 'Main Warehouse' },
    { countNumber: 'CC-2026-00010', id: 'cc-002', lineSummary: '18 lines', scheduledDate: '2026-05-31', status: 'scheduled', variance: 'Backend variance', warehouse: 'Service Store' },
];

export const batches: InventoryBatch[] = [
    { availableQuantity: 'Backend provided', batchNumber: 'B-OIL-0526', expiryDate: '2027-05-01', id: 'bat-001', itemName: 'Engine Oil 10W-40', location: 'A-01', sourceReference: 'GRN-2026-00051', status: 'active', warehouse: 'Main Warehouse' },
    { availableQuantity: 'Backend provided', batchNumber: 'B-CLN-0426', id: 'bat-002', itemName: 'Brake Cleaner', location: 'S-04', sourceReference: 'ADJ-2026-00013', status: 'quarantine', warehouse: 'Service Store' },
];

export const serials: InventorySerial[] = [
    { id: 'ser-001', itemName: 'Rental GPS Tracker', location: 'R-02', serialNumber: 'GPS-2026-00081', sourceReference: 'TRF-2026-00017', status: 'assigned', warehouse: 'Rental Accessories' },
    { id: 'ser-002', itemName: 'Diagnostic Scanner', location: 'S-TOOLS', serialNumber: 'SCAN-AX-4451', sourceReference: 'GRN-2026-00045', status: 'available', warehouse: 'Service Store' },
];

export const receiptInspections: ReceiptInspection[] = [
    { id: 'insp-001', inspectionNumber: 'INSP-2026-00031', itemName: 'Engine Oil 10W-40', result: 'Accepted', sourceReference: 'GRN-2026-00051', status: 'completed' },
    { id: 'insp-002', inspectionNumber: 'INSP-2026-00032', itemName: 'Brake Cleaner', result: 'Hold for review', sourceReference: 'GRN-2026-00052', status: 'pending' },
];

export const putAwayTasks: PutAwayTask[] = [
    { destinationLocation: 'A-01', id: 'put-001', itemName: 'Engine Oil 10W-40', quantity: 'Backend quantity', sourceReference: 'GRN-2026-00051', status: 'open' },
    { destinationLocation: 'B-03', id: 'put-002', itemName: 'Oil Filter', quantity: 'Backend quantity', sourceReference: 'GRN-2026-00053', status: 'completed' },
];

export const pickingTasks: PickingTask[] = [
    { id: 'pick-001', itemName: 'Oil Filter', quantity: 'Backend quantity', sourceReference: 'GDN-2026-00077', status: 'open', warehouse: 'Main Warehouse' },
    { id: 'pick-002', itemName: 'Brake Pads', quantity: 'Backend quantity', sourceReference: 'JOB-2026-00520', status: 'assigned', warehouse: 'Service Store' },
];

export const valuations: InventoryValuation[] = [
    { id: 'val-001', itemName: 'Engine Oil 10W-40', latestCostLayer: 'GRN-2026-00051', quantity: 'Backend quantity', totalValue: 'Backend valuation', unitCost: 'Backend cost', updatedAt: '2026-05-29', valuationMethod: 'FIFO', warehouse: 'Main Warehouse' },
    { id: 'val-002', itemName: 'Oil Filter', latestCostLayer: 'GRN-2026-00053', quantity: 'Backend quantity', totalValue: 'Backend valuation', unitCost: 'Backend cost', updatedAt: '2026-05-28', valuationMethod: 'Weighted average', warehouse: 'Main Warehouse' },
];

export const costLayers: CostLayer[] = [
    { id: 'cl-001', itemName: 'Engine Oil 10W-40', layerDate: '2026-05-22', quantity: 'Backend quantity', remainingQuantity: 'Backend quantity', sourceReference: 'GRN-2026-00051', unitCost: 'Backend cost' },
    { id: 'cl-002', itemName: 'Oil Filter', layerDate: '2026-05-20', quantity: 'Backend quantity', remainingQuantity: 'Backend quantity', sourceReference: 'GRN-2026-00053', unitCost: 'Backend cost' },
];

export const availabilityPreview: StockAvailabilityPreviewResult = {
    breakdown: [
        { label: 'Requested quantity', value: 'Backend preview' },
        { label: 'Available quantity', value: 'Backend preview' },
        { label: 'Reserved quantity', value: 'Backend preview' },
    ],
    calculated: {
        availableQuantity: 'Backend calculated',
        decision: 'available',
        requestedQuantity: 'Backend requested quantity',
        reservedQuantity: 'Backend calculated',
    },
    errors: [],
    input: {},
    warnings: ['Availability preview is backend/mock only. Frontend does not calculate stock.'],
};

export const traceability: InventoryAuditEntry[] = [
    { actor: 'Purchase', description: 'Received from supplier document GRN-2026-00051.', id: 'trace-001', time: '2026-05-22 09:00', type: 'receipt' },
    { actor: 'Inventory', description: 'Transferred from Main Warehouse to Service Store.', id: 'trace-002', time: '2026-05-25 11:30', type: 'transfer' },
    { actor: 'Vehicle Service', description: 'Consumed against service job JOB-2026-00512.', id: 'trace-003', time: '2026-05-26 14:10', type: 'consumption' },
    { actor: 'Inventory', description: 'Adjusted after cycle count variance approval.', id: 'trace-004', time: '2026-05-27 10:20', type: 'adjustment' },
];

export function getMovementById(id: string) {
    return stockMovements.find((movement) => movement.id === id) ?? stockMovements[0];
}

export function getTransferById(id: string) {
    return transfers.find((transfer) => transfer.id === id) ?? transfers[0];
}

export function getAdjustmentById(id: string) {
    return adjustments.find((adjustment) => adjustment.id === id) ?? adjustments[0];
}
