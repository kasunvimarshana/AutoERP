export type VehicleServiceJobCardStatus =
    | 'draft'
    | 'open'
    | 'in_progress'
    | 'waiting_parts'
    | 'invoiceable'
    | 'completed'
    | 'invoiced'
    | 'closed'
    | 'cancelled';

export type VehicleServiceLineType =
    | 'service'
    | 'spare_part'
    | 'labour'
    | 'non_inventory'
    | 'customer_supplied'
    | 'external_service'
    | 'combo';

export type VehicleServiceType = {
    code: string;
    description: string;
    id: string;
    name: string;
    status: 'active' | 'inactive';
    updatedAt: string;
};

export type VehicleServiceSourceReference = {
    sourceId?: string;
    sourceModule: string;
    sourceNumber?: string;
    sourceType: string;
};

export type VehicleServicePartyReference = {
    id?: string;
    name: string;
    type: 'company' | 'customer' | 'supplier' | 'supplier_as_customer' | 'party' | 'external_party' | 'internal_company' | 'insurance_company' | 'employee' | 'provider' | 'other';
};

export type VehicleServiceOwnerSummary = {
    ownershipId?: string;
    ownershipRole: string;
    ownershipType: string;
    owner: VehicleServicePartyReference;
};

export type VehicleServicePartyContext = {
    billingCustomer: VehicleServicePartyReference;
    mismatchNotice?: string;
    payer: VehicleServicePartyReference;
    serviceCustomer: VehicleServicePartyReference;
    vehicleOwner: VehicleServiceOwnerSummary;
};

export type VehicleServiceJobCardLine = {
    backendCalculatedAmount: string;
    description: string;
    discountPreview: string;
    id: string;
    invoiceable: boolean;
    item: string;
    itemId?: string;
    lineType: VehicleServiceLineType;
    quantity: string;
    stockBehavior: string;
    taxPreview: string;
    unitPrice?: string;
    uom: string;
    uomId?: string;
};

export type VehicleServiceSparePartLine = VehicleServiceJobCardLine & {
    lineType: 'spare_part';
    stockAvailability: string;
};

export type VehicleServiceNonInventoryLine = VehicleServiceJobCardLine & {
    lineType: 'non_inventory';
};

export type VehicleServiceCustomerSuppliedLine = VehicleServiceJobCardLine & {
    lineType: 'customer_supplied';
    returnExpectation: string;
};

export type VehicleServiceExternalServiceLine = VehicleServiceJobCardLine & {
    lineType: 'external_service';
    provider: string;
};

export type VehicleServiceLabourItem = {
    id: string;
    item: string;
    role: string;
    uom: string;
};

export type VehicleServiceLabourAssignment = {
    assignmentType: string;
    employee: string;
    id: string;
    incentivePreview: string;
    labourItem: string;
    status: string;
};

export type VehicleServiceDiagnostic = {
    diagnosticNumber: string;
    findings: string;
    id: string;
    jobCardId: string;
    phase: string;
    recommendation: string;
    status: string;
};

export type VehicleServiceInspection = {
    id: string;
    inspectionNumber: string;
    jobCardId: string;
    notes: string;
    phase: string;
    result: string;
    status: string;
};

export type VehicleServiceCalculationPreview = {
    breakdown: Array<{ label: string; value: string }>;
    calculated: {
        discountTotal: string;
        grandTotal: string;
        subtotal: string;
        taxTotal: string;
    };
    errors: string[];
    input: Record<string, unknown>;
    warnings: string[];
};

export type VehicleServiceStockAvailabilityPreview = {
    breakdown: Array<{ label: string; value: string }>;
    calculated: {
        availabilityDecision: string;
        requestedQuantity: string;
        reservedQuantity: string;
        stockEffect: string;
    };
    errors: string[];
    input: Record<string, unknown>;
    warnings: string[];
};

export type VehicleServiceFinancePostingPreview = {
    breakdown: Array<{ account: string; effect: string }>;
    calculated: {
        arImpact: string;
        eligibility: string;
        journalStatus: string;
    };
    errors: string[];
    input: Record<string, unknown>;
    warnings: string[];
};

export type VehicleServiceInvoice = {
    billingCustomer: string;
    id: string;
    invoiceNumber: string;
    invoiceStatus: string;
    jobCardNumber: string;
    previewTotal: string;
    status: string;
    updatedAt: string;
};

export type VehicleServicePayment = {
    allocationPreview: string;
    amount: string;
    payer: string;
    id: string;
    method: string;
    paymentNumber: string;
    sourceInvoice: string;
    status: string;
};

export type VehicleServiceSettings = {
    allowCustomerSuppliedItems: boolean;
    allowExternalServices: boolean;
    allowNegativeStock: boolean;
    defaultTaxGroup: string;
    defaultWarehouse: string;
    invoiceDefinition: string;
    invoiceSequence: string;
    jobCardSequence: string;
    stockConsumptionTiming: string;
};

export type VehicleServiceAuditEntry = {
    actor: string;
    id: string;
    note: string;
    timestamp: string;
    type: string;
};

export type VehicleServiceInvoiceRecord = {
    invoiceNumber: string;
    status: string;
};

export type VehicleServiceJobCard = {
    audit: VehicleServiceAuditEntry[];
    customer: string;
    customerComplaint: string;
    diagnostics: VehicleServiceDiagnostic[];
    expectedCompletion: string;
    financePreview: VehicleServiceFinancePostingPreview;
    id: string;
    initialDiagnosis: string;
    inspections: VehicleServiceInspection[];
    invoicePreview: VehicleServiceCalculationPreview;
    jobCardNumber: string;
    labourAssignments: VehicleServiceLabourAssignment[];
    lines: VehicleServiceJobCardLine[];
    nextServiceDate: string;
    odometer: string;
    openedAt: string;
    payments: VehicleServicePayment[];
    partyContext: VehicleServicePartyContext;
    serviceAdvisor: string;
    serviceType: string;
    sourceReference: VehicleServiceSourceReference;
    status: VehicleServiceJobCardStatus;
    stockPreview: VehicleServiceStockAvailabilityPreview;
    supervisor: string;
    updatedAt: string;
    vehicle: string;
    workflowStatus: string;
};

export type VehicleServiceDashboardMetric = {
    label: string;
    tone: string;
    value: string;
};

export type VehicleServiceLookupOption = {
    id: string;
    label: string;
    secondary?: string;
};

export type VehicleServiceJobCardLineFormInput = {
    accountId?: string;
    description: string;
    discountType?: string;
    discountValue?: string;
    id?: string;
    itemId: string;
    lineType: VehicleServiceLineType;
    quantity: string;
    requiresStockMovement: boolean;
    taxGroupId?: string;
    unitCost?: string;
    unitPrice: string;
    uomId: string;
    warehouseId?: string;
};

export type VehicleServiceJobCardFormInput = {
    billingCustomerId: string;
    billingCustomerName?: string;
    billingCustomerType: VehicleServicePartyReference['type'];
    customerComplaint: string;
    expectedCompletion: string;
    headerDiscountType?: string;
    headerDiscountValue?: string;
    headerTaxGroupId?: string;
    initialDiagnosis: string;
    jobCardNumber: string;
    laborItems: VehicleServiceJobCardLineFormInput[];
    lines: VehicleServiceJobCardLineFormInput[];
    nextServiceDate: string;
    nonInventoryItems: VehicleServiceJobCardLineFormInput[];
    notes: string;
    odometer: string;
    openedAt: string;
    payerId: string;
    payerName?: string;
    payerType: VehicleServicePartyReference['type'];
    priority: string;
    receivedAt: string;
    serviceCustomerId: string;
    serviceCustomerName?: string;
    serviceCustomerType: VehicleServicePartyReference['type'];
    serviceTypeId: string;
    status: VehicleServiceJobCardStatus;
    supervisorId: string;
    vehicleId: string;
    warehouseId: string;
};

export type VehicleServicePaymentFormInput = {
    amount: string;
    invoiceId: string;
    invoiceType: string;
    jobCardId: string;
    paymentId: string;
};
    invoiceRecord: VehicleServiceInvoiceRecord;
