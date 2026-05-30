export type VehicleServiceJobCardStatus =
    | 'draft'
    | 'open'
    | 'in_progress'
    | 'waiting_parts'
    | 'invoiceable'
    | 'completed'
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
    lineType: VehicleServiceLineType;
    quantity: string;
    stockBehavior: string;
    taxPreview: string;
    uom: string;
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
    documentStatus: string;
    id: string;
    invoiceNumber: string;
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
    documentDefinition: string;
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

export type VehicleServiceDocumentPreview = {
    documentNumber: string;
    status: string;
    template: string;
};

export type VehicleServiceJobCard = {
    audit: VehicleServiceAuditEntry[];
    customer: string;
    customerComplaint: string;
    diagnostics: VehicleServiceDiagnostic[];
    documentPreview: VehicleServiceDocumentPreview;
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
