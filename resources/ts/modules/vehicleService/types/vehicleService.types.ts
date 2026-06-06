export type JobStatus = 'open' | 'in_progress' | 'completed' | 'invoiced' | 'paid' | 'cancelled';

export type Lookup = {
    id: number;
    code?: string;
    name: string;
    base_uom_id?: number;
    sales_uom_id?: number;
    cost_price?: string;
    sales_price?: string;
    track_inventory?: boolean;
    is_service_item?: boolean;
};

export type ServiceType = {
    id: number;
    name: string;
    code?: string;
    description?: string;
    standardHours?: string;
    isActive: boolean;
};

export type JobLineInput = {
    itemId?: number;
    uomId: number;
    description?: string;
    employeeId?: number;
    actualHours?: string;
    quantity: string;
    unitPrice: string;
    unitCost?: string;
    discountType?: 'percentage' | 'fixed' | '';
    discountValue?: string;
    taxAmount?: string;
    warehouseId?: number;
};

export type NonInventoryLineInput = Omit<JobLineInput, 'itemId' | 'warehouseId'> & {
    name: string;
};

export type JobCardInput = {
    jobCardNumber?: string;
    reference?: string;
    customerId: number;
    vehicleId: number;
    serviceTypeId?: number;
    warehouseId: number;
    priority: 'low' | 'medium' | 'high' | 'critical';
    reportedIssue?: string;
    resolutionNotes?: string;
    startOdometer?: number;
    estimatedHours?: string;
    promisedDeliveryDateTime?: string;
    headerDiscountType?: 'percentage' | 'fixed' | '';
    headerDiscountValue?: string;
    headerTaxAmount?: string;
    headerChargeAmount?: string;
    headerAdjustmentAmount?: string;
    headerAdjustmentEffect?: 'add' | 'deduct';
    notes?: string;
    parts: JobLineInput[];
    laborItems: JobLineInput[];
    nonInventoryItems: NonInventoryLineInput[];
};

export type JobCard = {
    id: number;
    jobCardNumber: string;
    reference?: string;
    customerId: number;
    customerName?: string;
    vehicleId: number;
    registrationNumber?: string;
    serviceTypeId?: number;
    serviceTypeName?: string;
    warehouseId: number;
    warehouseName?: string;
    priority: string;
    status: JobStatus;
    inventoryStatus: string;
    invoiceStatus: string;
    paymentStatus: string;
    financeStatus: string;
    reportedIssue?: string;
    resolutionNotes?: string;
    startOdometer?: number;
    estimatedHours?: string;
    promisedDeliveryDateTime?: string;
    subtotal: string;
    partsSubtotal: string;
    grossTotal: string;
    laborSubtotal: string;
    nonInventorySubtotal: string;
    lineDiscountTotal: string;
    headerDiscountTotal: string;
    discountTotal: string;
    lineTaxTotal: string;
    headerTaxTotal: string;
    taxTotal: string;
    chargeTotal: string;
    adjustmentTotal: string;
    debitAdjustmentTotal: string;
    creditAdjustmentTotal: string;
    grandTotal: string;
    paidAmount: string;
    balance: string;
    notes?: string;
    parts?: any[];
    laborItems?: any[];
    nonInventoryItems?: any[];
    invoiceLinks?: any[];
    payments?: any[];
};

export type Page<T> = {
    items: T[];
    meta: { currentPage: number; lastPage: number; perPage: number; total: number };
};

export type Dashboard = {
    open_jobs: number;
    completed_jobs: number;
    pending_invoice_jobs: number;
    unpaid_amount: string;
    service_value: string;
};
