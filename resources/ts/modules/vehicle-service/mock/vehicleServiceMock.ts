import type {
    VehicleServiceAuditEntry,
    VehicleServiceCalculationPreview,
    VehicleServiceDashboardMetric,
    VehicleServiceFinancePostingPreview,
    VehicleServiceInvoice,
    VehicleServiceJobCard,
    VehicleServiceJobCardLine,
    VehicleServicePayment,
    VehicleServiceSettings,
    VehicleServiceStockAvailabilityPreview,
    VehicleServiceType,
} from '../types/vehicleService.types';

export const serviceTypes: VehicleServiceType[] = [
    { code: 'FULL-SVC', description: 'Scheduled full service with inspection, service labour, and parts.', id: 'vst-001', name: 'Full Service', status: 'active', updatedAt: '2026-05-29' },
    { code: 'RUN-REP', description: 'Running repair workflow for customer complaints and diagnostics.', id: 'vst-002', name: 'Running Repair', status: 'active', updatedAt: '2026-05-29' },
    { code: 'PRE-INS', description: 'Vehicle inspection without required inventory consumption.', id: 'vst-003', name: 'Inspection', status: 'inactive', updatedAt: '2026-05-25' },
];

export const vehicleServiceDashboardMetrics: VehicleServiceDashboardMetric[] = [
    { label: 'Open jobs', tone: 'info', value: '18' },
    { label: 'Invoiceable jobs', tone: 'success', value: '7' },
    { label: 'Waiting parts', tone: 'warning', value: '4' },
    { label: 'Receivable jobs', tone: 'default', value: '9' },
    { label: 'Delayed jobs', tone: 'danger', value: '2' },
    { label: 'Spare shortages', tone: 'warning', value: '3' },
];

export const customers = [
    { id: 'cus-001', label: 'Northline Logistics' },
    { id: 'cus-002', label: 'Metro Hire Services' },
    { id: 'cus-003', label: 'John Workshop' },
];

export const vehicles = [
    { id: 'veh-001', label: 'WP CAD-4521 | Toyota HiAce' },
    { id: 'veh-002', label: 'WP KA-7781 | Nissan Caravan' },
    { id: 'veh-003', label: 'CP CAB-9410 | Mitsubishi L200' },
];

export const technicians = [
    { id: 'emp-001', label: 'Kasun Perera' },
    { id: 'emp-002', label: 'Nimal Fernando' },
    { id: 'emp-003', label: 'Tharindu Silva' },
];

export const serviceItems = [
    { id: 'svc-001', label: 'Full service package' },
    { id: 'svc-002', label: 'Engine tune-up' },
    { id: 'svc-003', label: 'Brake inspection' },
];

export const sparePartItems = [
    { id: 'itm-001', label: 'Oil filter' },
    { id: 'itm-002', label: 'Brake pads' },
    { id: 'itm-003', label: 'Engine oil 5W-30' },
];

export const nonInventoryItems = [
    { id: 'non-001', label: 'Workshop consumables' },
    { id: 'non-002', label: 'Environmental handling fee' },
];

export const uomOptions = [
    { id: 'pcs', label: 'PCS' },
    { id: 'ltr', label: 'Litre' },
    { id: 'hr', label: 'Hour' },
    { id: 'job', label: 'Job' },
];

const stockPreview: VehicleServiceStockAvailabilityPreview = {
    breakdown: [
        { label: 'Oil filter', value: 'Backend stock availability check' },
        { label: 'Engine oil', value: 'Backend UOM and batch eligibility check' },
    ],
    calculated: {
        availabilityDecision: 'Backend decision: available with warning',
        requestedQuantity: 'Backend echo',
        reservedQuantity: 'Backend calculated',
        stockEffect: 'Backend will issue only spare_part lines',
    },
    errors: [],
    input: { jobCardId: 'job-001' },
    warnings: ['Customer-supplied engine oil is recorded with no stock impact.'],
};

const invoicePreview: VehicleServiceCalculationPreview = {
    breakdown: [
        { label: 'Service items', value: 'Backend calculated' },
        { label: 'Spare parts', value: 'Backend calculated' },
        { label: 'Labour', value: 'Backend calculated' },
        { label: 'Customer supplied', value: 'No stock effect; billing rule from backend' },
    ],
    calculated: {
        discountTotal: 'Backend calculated',
        grandTotal: 'Backend calculated',
        subtotal: 'Backend calculated',
        taxTotal: 'Backend calculated',
    },
    errors: [],
    input: { jobCardId: 'job-001' },
    warnings: ['Official invoice generation is backend-owned.'],
};

const financePreview: VehicleServiceFinancePostingPreview = {
    breakdown: [
        { account: 'Accounts receivable', effect: 'Backend posting preview' },
        { account: 'Service income', effect: 'Backend posting preview' },
        { account: 'COGS / inventory', effect: 'Backend posting preview for stock lines only' },
    ],
    calculated: {
        arImpact: 'Backend calculated',
        eligibility: 'Backend validates fiscal period and workflow',
        journalStatus: 'Preview only',
    },
    errors: [],
    input: { sourceModule: 'vehicle_service' },
    warnings: [],
};

const supplierOwnedPartyContext = {
    billingCustomer: { id: 'cus-004', name: 'ABC Motors customer role', type: 'customer' },
    mismatchNotice: 'Vehicle owner is a supplier/provider; billing customer is a linked customer role.',
    payer: { id: 'cus-004', name: 'ABC Motors customer role', type: 'customer' },
    serviceCustomer: { id: 'cus-004', name: 'ABC Motors customer role', type: 'customer' },
    vehicleOwner: {
        ownershipId: 'own-001',
        ownershipRole: 'legal_owner',
        ownershipType: 'supplier',
        owner: { id: 'sup-001', name: 'ABC Motors', type: 'supplier' },
    },
} satisfies VehicleServiceJobCard['partyContext'];

const customerOwnedPartyContext = {
    billingCustomer: { id: 'cus-001', name: 'Northline Logistics', type: 'customer' },
    payer: { id: 'cus-001', name: 'Northline Logistics', type: 'customer' },
    serviceCustomer: { id: 'cus-001', name: 'Northline Logistics', type: 'customer' },
    vehicleOwner: {
        ownershipId: 'own-002',
        ownershipRole: 'legal_owner',
        ownershipType: 'customer',
        owner: { id: 'cus-001', name: 'Northline Logistics', type: 'customer' },
    },
} satisfies VehicleServiceJobCard['partyContext'];

const commonLines: VehicleServiceJobCardLine[] = [
    { backendCalculatedAmount: 'Backend calculated', description: 'Scheduled 40-point inspection and service', discountPreview: 'Backend calculated', id: 'line-001', invoiceable: true, item: 'Full service package', lineType: 'service', quantity: '1', stockBehavior: 'No direct stock impact', taxPreview: 'Backend calculated', uom: 'Job' },
    { backendCalculatedAmount: 'Backend calculated', description: 'Oil filter replacement', discountPreview: 'Backend calculated', id: 'line-002', invoiceable: true, item: 'Oil filter', lineType: 'spare_part', quantity: '1', stockBehavior: 'Affects Inventory after backend stock consumption', taxPreview: 'Backend calculated', uom: 'PCS' },
    { backendCalculatedAmount: 'Backend calculated', description: 'Engine oil customer supplied at intake', discountPreview: 'Backend rule', id: 'line-003', invoiceable: false, item: 'Customer supplied engine oil', lineType: 'customer_supplied', quantity: '4', stockBehavior: 'No stock impact', taxPreview: 'Backend rule', uom: 'Litre' },
    { backendCalculatedAmount: 'Backend calculated', description: 'Technician inspection labour', discountPreview: 'Backend calculated', id: 'line-004', invoiceable: true, item: 'Inspection labour', lineType: 'labour', quantity: '2', stockBehavior: 'No stock impact', taxPreview: 'Backend calculated', uom: 'Hour' },
    { backendCalculatedAmount: 'Backend calculated', description: 'Workshop consumables charge', discountPreview: 'Backend calculated', id: 'line-005', invoiceable: true, item: 'Workshop consumables', lineType: 'non_inventory', quantity: '1', stockBehavior: 'No stock impact', taxPreview: 'Backend calculated', uom: 'Job' },
    { backendCalculatedAmount: 'Backend calculated', description: 'External wheel alignment provider', discountPreview: 'Backend calculated', id: 'line-006', invoiceable: true, item: 'Wheel alignment', lineType: 'external_service', quantity: '1', stockBehavior: 'No internal stock impact', taxPreview: 'Backend calculated', uom: 'Job' },
    { backendCalculatedAmount: 'Backend calculated', description: 'Premium service package expansion', discountPreview: 'Backend calculated', id: 'line-007', invoiceable: true, item: 'Premium service combo', lineType: 'combo', quantity: '1', stockBehavior: 'Backend expands component stock/labour effects', taxPreview: 'Backend calculated', uom: 'Bundle' },
];

const audit: VehicleServiceAuditEntry[] = [
    { actor: 'Kasun Perera', id: 'act-001', note: 'Job card opened from service intake.', timestamp: '2026-05-29 09:10', type: 'created' },
    { actor: 'Nimal Fernando', id: 'act-002', note: 'Spare part availability preview requested.', timestamp: '2026-05-29 09:35', type: 'preview' },
    { actor: 'System', id: 'act-003', note: 'Invoice preview generated by mock backend contract.', timestamp: '2026-05-29 10:15', type: 'preview' },
];

export const jobCards: VehicleServiceJobCard[] = [
    {
        audit,
        customer: 'Northline Logistics',
        customerComplaint: 'Engine service due and brake noise on low-speed turns.',
        diagnostics: [
            { diagnosticNumber: 'DIAG-MOCK-001', findings: 'Brake pad wear and engine oil service due.', id: 'diag-001', jobCardId: 'job-001', phase: 'pre-service', recommendation: 'Replace pads, service oil/filter, road test.', status: 'open' },
        ],
        documentPreview: { documentNumber: 'JC-DOC-MOCK-001', status: 'Preview only', template: 'Vehicle service job card' },
        expectedCompletion: '2026-05-31 16:00',
        financePreview,
        id: 'job-001',
        initialDiagnosis: 'Routine service with brake inspection.',
        inspections: [
            { id: 'insp-001', inspectionNumber: 'INSP-MOCK-001', jobCardId: 'job-001', notes: 'Tyres and lights acceptable; brakes need service.', phase: 'intake', result: 'Attention required', status: 'open' },
        ],
        invoicePreview,
        jobCardNumber: 'JC-MOCK-001',
        labourAssignments: [
            { assignmentType: 'primary', employee: 'Nimal Fernando', id: 'lab-001', incentivePreview: 'Backend incentive preview', labourItem: 'Inspection labour', status: 'assigned' },
            { assignmentType: 'support', employee: 'Tharindu Silva', id: 'lab-002', incentivePreview: 'Backend share preview', labourItem: 'Detailing labour', status: 'assigned' },
        ],
        lines: commonLines,
        nextServiceDate: '2026-11-30',
        odometer: '84,220 km',
        openedAt: '2026-05-29 09:10',
        payments: [
            { allocationPreview: 'Backend allocation preview', amount: 'Backend amount', id: 'pay-001', method: 'Bank', payer: 'Northline Logistics', paymentNumber: 'SPAY-MOCK-001', sourceInvoice: 'SINV-MOCK-001', status: 'draft' },
        ],
        partyContext: customerOwnedPartyContext,
        serviceAdvisor: 'Kasun Perera',
        serviceType: 'Full Service',
        sourceReference: { sourceId: 'job-001', sourceModule: 'vehicle_service', sourceNumber: 'JC-MOCK-001', sourceType: 'job_card' },
        status: 'in_progress',
        stockPreview,
        supervisor: 'Kasun Perera',
        updatedAt: '2026-05-29',
        vehicle: 'WP CAD-4521 | Toyota HiAce',
        workflowStatus: 'Backend workflow state',
    },
    {
        audit,
        customer: 'Metro Hire Services',
        customerComplaint: 'Air conditioning weak and rental return inspection requested.',
        diagnostics: [],
        documentPreview: { documentNumber: 'JC-DOC-MOCK-002', status: 'Draft', template: 'Vehicle service job card' },
        expectedCompletion: '2026-06-01 12:00',
        financePreview,
        id: 'job-002',
        initialDiagnosis: 'Inspection before rental redeployment.',
        inspections: [],
        invoicePreview,
        jobCardNumber: 'JC-MOCK-002',
        labourAssignments: [],
        lines: commonLines.filter((line) => line.lineType !== 'external_service'),
        nextServiceDate: '2026-12-01',
        odometer: '62,430 km',
        openedAt: '2026-05-29 10:30',
        payments: [],
        partyContext: {
            billingCustomer: { id: 'cus-002', name: 'Metro Hire Services', type: 'customer' },
            payer: { id: 'cus-002', name: 'Metro Hire Services', type: 'customer' },
            serviceCustomer: { id: 'cus-002', name: 'Metro Hire Services', type: 'customer' },
            vehicleOwner: {
                ownershipId: 'own-003',
                ownershipRole: 'provider',
                ownershipType: 'own',
                owner: { name: 'Internal Fleet', type: 'company' },
            },
        },
        serviceAdvisor: 'Nimal Fernando',
        serviceType: 'Inspection',
        sourceReference: { sourceId: 'job-002', sourceModule: 'vehicle_service', sourceNumber: 'JC-MOCK-002', sourceType: 'job_card' },
        status: 'open',
        stockPreview,
        supervisor: 'Nimal Fernando',
        updatedAt: '2026-05-29',
        vehicle: 'WP KA-7781 | Nissan Caravan',
        workflowStatus: 'Backend workflow state',
    },
    {
        audit,
        customer: 'John Workshop',
        customerComplaint: 'Completed running repair. Customer collected vehicle.',
        diagnostics: [],
        documentPreview: { documentNumber: 'SINV-DOC-MOCK-003', status: 'Generated by backend placeholder', template: 'Service invoice' },
        expectedCompletion: '2026-05-28 15:00',
        financePreview,
        id: 'job-003',
        initialDiagnosis: 'Running repair completed.',
        inspections: [],
        invoicePreview,
        jobCardNumber: 'JC-MOCK-003',
        labourAssignments: [],
        lines: commonLines.slice(0, 4),
        nextServiceDate: '2026-11-28',
        odometer: '112,900 km',
        openedAt: '2026-05-28 08:15',
        payments: [],
        partyContext: supplierOwnedPartyContext,
        serviceAdvisor: 'Kasun Perera',
        serviceType: 'Running Repair',
        sourceReference: { sourceId: 'job-003', sourceModule: 'vehicle_service', sourceNumber: 'JC-MOCK-003', sourceType: 'job_card' },
        status: 'closed',
        stockPreview,
        supervisor: 'Kasun Perera',
        updatedAt: '2026-05-28',
        vehicle: 'CP CAB-9410 | Mitsubishi L200',
        workflowStatus: 'Closed by backend workflow',
    },
];

export const serviceInvoices: VehicleServiceInvoice[] = [
    { billingCustomer: 'Northline Logistics', documentStatus: 'Preview document available', id: 'svc-inv-001', invoiceNumber: 'SINV-MOCK-001', jobCardNumber: 'JC-MOCK-001', previewTotal: 'Backend calculated', status: 'draft', updatedAt: '2026-05-29' },
    { billingCustomer: 'ABC Motors customer role', documentStatus: 'Generated', id: 'svc-inv-002', invoiceNumber: 'SINV-MOCK-002', jobCardNumber: 'JC-MOCK-003', previewTotal: 'Backend calculated', status: 'posted', updatedAt: '2026-05-28' },
];

export const servicePayments: VehicleServicePayment[] = [
    { allocationPreview: 'Backend allocation preview', amount: 'Backend amount', id: 'svc-pay-001', method: 'Bank', payer: 'Northline Logistics', paymentNumber: 'SPAY-MOCK-001', sourceInvoice: 'SINV-MOCK-001', status: 'draft' },
    { allocationPreview: 'Backend allocation preview', amount: 'Backend amount', id: 'svc-pay-002', method: 'Cash', payer: 'ABC Motors customer role', paymentNumber: 'SPAY-MOCK-002', sourceInvoice: 'SINV-MOCK-002', status: 'posted' },
];

export const vehicleServiceSettings: VehicleServiceSettings = {
    allowCustomerSuppliedItems: true,
    allowExternalServices: true,
    allowNegativeStock: false,
    defaultTaxGroup: 'VAT-STANDARD',
    defaultWarehouse: 'MAIN-WORKSHOP',
    documentDefinition: 'service_invoice_default',
    invoiceSequence: 'SINV-{YYYY}-{####}',
    jobCardSequence: 'JC-{YYYY}-{####}',
    stockConsumptionTiming: 'on_invoice_or_close',
};

export function getJobCardById(id: string) {
    return jobCards.find((jobCard) => jobCard.id === id || jobCard.jobCardNumber === id) ?? jobCards[0];
}

export function getInvoiceById(id: string) {
    return serviceInvoices.find((invoice) => invoice.id === id || invoice.invoiceNumber === id) ?? serviceInvoices[0];
}
