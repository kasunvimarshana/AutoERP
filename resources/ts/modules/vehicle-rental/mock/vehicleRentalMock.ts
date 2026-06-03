import type {
    VehicleRentalAgreement,
    VehicleRentalAvailabilityPreview,
    VehicleRentalBillingPreview,
    VehicleRentalDashboardMetric,
    VehicleRentalFinancePostingPreview,
    VehicleRentalProviderPayable,
    VehicleRentalRunningChart,
    VehicleRentalSettings,
} from '../types/vehicleRental.types';

export const rentalDashboardMetrics: VehicleRentalDashboardMetric[] = [
    { label: 'Active agreements', tone: 'info', value: '14' },
    { label: 'Available vehicles', tone: 'success', value: '22' },
    { label: 'Running charts due', tone: 'warning', value: '6' },
    { label: 'Invoices awaiting payment', tone: 'default', value: '8' },
    { label: 'Provider payables', tone: 'warning', value: '5' },
    { label: 'Breakdowns', tone: 'danger', value: '2' },
];

export const customers = [
    { id: 'cus-001', label: 'Metro Hire Services' },
    { id: 'cus-002', label: 'Northline Logistics' },
    { id: 'cus-003', label: 'City Events Lanka' },
];

export const vehicles = [
    { id: 'rv-001', label: 'WP CAD-4521 | Toyota HiAce | Available' },
    { id: 'rv-002', label: 'WP KA-7781 | Nissan Caravan | Booked' },
    { id: 'rv-003', label: 'CP CAB-9410 | Mitsubishi L200 | External provider' },
];

export const drivers = [
    { id: 'emp-001', label: 'Kasun Perera' },
    { id: 'emp-002', label: 'Nimal Fernando' },
    { id: 'emp-003', label: 'Tharindu Silva' },
];

export const providers = [
    { id: 'sup-001', label: 'Lanka Fleet Partners' },
    { id: 'sup-002', label: 'Metro Owner Pool' },
];

export const ratePlans = [
    { id: 'rate-001', label: 'Daily with KM cap' },
    { id: 'rate-002', label: 'Hourly with overtime' },
    { id: 'rate-003', label: 'Monthly external provider' },
];

export const availabilityPreview: VehicleRentalAvailabilityPreview = {
    breakdown: [
        { label: 'Requested period', value: 'Backend availability range' },
        { label: 'Existing allocations', value: 'Backend conflict scan' },
        { label: 'Replacement option', value: 'Backend replacement suggestion' },
    ],
    calculated: {
        availabilityDecision: 'Backend decision: available',
        conflicts: 'Backend conflict count',
        replacementOption: 'Backend suggested if needed',
        vehicleStatus: 'Backend vehicle availability status',
    },
    errors: [],
    input: { source: 'vehicle_rental_availability' },
    warnings: ['Availability is preview-only until the agreement is submitted.'],
};

export const billingPreview: VehicleRentalBillingPreview = {
    breakdown: [
        { label: 'Base rental', value: 'Backend calculated' },
        { label: 'Overtime / night / weekend', value: 'Backend calculated' },
        { label: 'Extra charges', value: 'Backend calculated' },
        { label: 'Provider payable', value: 'Backend calculated for external provider flow' },
    ],
    calculated: {
        discountTotal: 'Backend calculated',
        grandTotal: 'Backend calculated',
        providerPayable: 'Backend calculated',
        rentalCharge: 'Backend calculated',
        taxTotal: 'Backend calculated',
    },
    errors: [],
    input: { agreementId: 'agr-001' },
    warnings: ['Frontend does not calculate billing or provider payable values.'],
};

export const financePreview: VehicleRentalFinancePostingPreview = {
    breakdown: [
        { account: 'Accounts receivable', effect: 'Backend AR posting preview' },
        { account: 'Rental income', effect: 'Backend income posting preview' },
        { account: 'Provider payable', effect: 'Backend AP posting preview when external provider applies' },
    ],
    calculated: {
        apImpact: 'Backend calculated',
        arImpact: 'Backend calculated',
        eligibility: 'Backend validates fiscal period and workflow',
        journalStatus: 'Preview only',
    },
    errors: [],
    input: { sourceModule: 'vehicle_rental' },
    warnings: [],
};

export const runningCharts: VehicleRentalRunningChart[] = [
    {
        agreementNumber: 'RA-MOCK-001',
        billingPreview,
        chartNumber: 'RC-MOCK-001',
        customer: 'Metro Hire Services',
        driver: 'Nimal Fernando',
        endAt: '2026-06-03 18:00',
        id: 'rc-001',
        lines: [
            { chargePreview: 'Backend calculated', driver: 'Nimal Fernando', endReading: '19,260 km', id: 'rcl-001', lineNumber: '1', providerCostPreview: 'Backend calculated', startReading: '18,920 km', usagePreview: 'Backend calculated', vehicle: 'WP CAD-4521' },
        ],
        providerPayablePreview: 'Backend calculated',
        startAt: '2026-06-01 08:00',
        status: 'draft',
        vehicle: 'WP CAD-4521 | Toyota HiAce',
    },
    {
        agreementNumber: 'RA-MOCK-003',
        billingPreview,
        chartNumber: 'RC-MOCK-002',
        customer: 'Northline Logistics',
        driver: 'Unassigned',
        endAt: '2026-06-30',
        id: 'rc-002',
        lines: [
            { chargePreview: 'Backend calculated', driver: 'Unassigned', endReading: 'Month end', id: 'rcl-002', lineNumber: '1', providerCostPreview: 'Backend calculated', startReading: 'Month start', usagePreview: 'Backend calculated', vehicle: 'CP CAB-9410' },
        ],
        providerPayablePreview: 'Backend calculated',
        startAt: '2026-06-01',
        status: 'submitted',
        vehicle: 'CP CAB-9410 | Mitsubishi L200',
    },
];

export const providerPayables: VehicleRentalProviderPayable[] = [
    { agreementNumber: 'RA-MOCK-003', financeStatus: 'draft', id: 'payable-001', payableNumber: 'VRP-MOCK-001', payablePreview: 'Backend calculated', paymentStatus: 'unpaid', provider: 'Lanka Fleet Partners', sourceReference: 'RC-MOCK-002', status: 'pending' },
    { agreementNumber: 'RA-MOCK-004', financeStatus: 'posted', id: 'payable-002', payableNumber: 'VRP-MOCK-002', payablePreview: 'Backend calculated', paymentStatus: 'partially_paid', provider: 'Metro Owner Pool', sourceReference: 'RA-MOCK-004', status: 'approved' },
];

const activity = [
    { actor: 'Kasun Perera', id: 'act-001', note: 'Rental agreement created from availability preview.', timestamp: '2026-05-29 09:00', type: 'created' },
    { actor: 'System', id: 'act-002', note: 'Billing preview requested by mock backend contract.', timestamp: '2026-05-29 09:20', type: 'preview' },
    { actor: 'Nimal Fernando', id: 'act-003', note: 'Running chart draft opened.', timestamp: '2026-05-30 08:15', type: 'running_chart' },
];

export const rentalAgreements: VehicleRentalAgreement[] = [
    {
        activity,
        agreementNumber: 'RA-MOCK-001',
        availabilityPreview,
        billingPreview,
        breakdowns: [],
        customer: 'Metro Hire Services',
        documentPreview: { documentNumber: 'VRA-DOC-MOCK-001', status: 'Preview only', template: 'Rental agreement' },
        driver: 'Nimal Fernando',
        endAt: '2026-06-03 18:00',
        financePreview,
        id: 'agr-001',
        invoices: [
            { billingPreview: 'Backend calculated', customer: 'Metro Hire Services', documentStatus: 'Preview document available', id: 'inv-001', invoiceNumber: 'RINV-MOCK-001', sourceAgreement: 'RA-MOCK-001', status: 'draft' },
        ],
        lines: [
            { backendAmount: 'Backend calculated', chargeScope: 'customer', description: 'Daily rental charge with included KM', id: 'line-001', item: 'Daily rental charge', rentalUnit: 'day', usageBasis: 'Backend usage basis' },
            { backendAmount: 'Backend calculated', chargeScope: 'customer', description: 'Overtime / night / weekend placeholder', id: 'line-002', item: 'Overtime charge', rentalUnit: 'hour', usageBasis: 'Backend rule' },
        ],
        mode: 'with_driver',
        payments: [
            { allocationPreview: 'Backend allocation preview', amount: 'Backend amount', customer: 'Metro Hire Services', id: 'pay-001', method: 'Bank', paymentNumber: 'RPAY-MOCK-001', sourceInvoice: 'RINV-MOCK-001', status: 'draft' },
        ],
        provider: 'Own fleet',
        providerPayables: [],
        ratePlan: { baseRate: 'Backend resolved', id: 'rate-001', name: 'Daily with KM cap', rentalUnit: 'day/km', status: 'active' },
        rateRules: [
            { id: 'rule-001', ruleName: 'Overtime after included hours', ruleType: 'overtime', scope: 'customer', valuePreview: 'Backend calculated' },
            { id: 'rule-002', ruleName: 'Weekend multiplier', ruleType: 'weekend', scope: 'customer', valuePreview: 'Backend calculated' },
        ],
        rentalUnit: 'day',
        replacements: [],
        runningCharts: [runningCharts[0]],
        sourceReference: { sourceId: 'agr-001', sourceModule: 'vehicle_rental', sourceNumber: 'RA-MOCK-001', sourceType: 'agreement' },
        startAt: '2026-06-01 08:00',
        status: 'active',
        updatedAt: '2026-05-29',
        vehicle: 'WP CAD-4521 | Toyota HiAce',
        vehicleSource: 'own_fleet',
        workflowStatus: 'Backend workflow state',
    },
    {
        activity,
        agreementNumber: 'RA-MOCK-003',
        availabilityPreview: { ...availabilityPreview, calculated: { ...availabilityPreview.calculated, availabilityDecision: 'Backend decision: external provider confirmed' } },
        billingPreview,
        breakdowns: [
            { agreementNumber: 'RA-MOCK-003', breakdownNumber: 'BR-MOCK-001', id: 'br-001', resolution: 'Replacement vehicle requested', status: 'reported', vehicle: 'CP CAB-9410' },
        ],
        customer: 'Northline Logistics',
        documentPreview: { documentNumber: 'VRA-DOC-MOCK-003', status: 'Generated by backend placeholder', template: 'Rental agreement' },
        driver: 'Without driver',
        endAt: '2026-06-30',
        financePreview,
        id: 'agr-003',
        invoices: [
            { billingPreview: 'Backend calculated', customer: 'Northline Logistics', documentStatus: 'Generated', id: 'inv-003', invoiceNumber: 'RINV-MOCK-003', sourceAgreement: 'RA-MOCK-003', status: 'posted' },
        ],
        lines: [
            { backendAmount: 'Backend calculated', chargeScope: 'customer', description: 'Monthly rental charge', id: 'line-003', item: 'Monthly rental charge', rentalUnit: 'month', usageBasis: 'Backend period basis' },
            { backendAmount: 'Backend calculated', chargeScope: 'provider', description: 'External provider payable rule', id: 'line-004', item: 'Provider vehicle cost', rentalUnit: 'month', usageBasis: 'Backend provider basis' },
        ],
        mode: 'without_driver',
        payments: [],
        provider: 'Lanka Fleet Partners',
        providerPayables,
        ratePlan: { baseRate: 'Backend resolved', id: 'rate-003', name: 'Monthly external provider', rentalUnit: 'month', status: 'active' },
        rateRules: [
            { id: 'rule-003', ruleName: 'Provider monthly payable', ruleType: 'provider_payable', scope: 'provider', valuePreview: 'Backend calculated' },
        ],
        rentalUnit: 'month',
        replacements: [
            { agreementNumber: 'RA-MOCK-003', id: 'rep-001', originalVehicle: 'CP CAB-9410', reason: 'Breakdown', replacementNumber: 'VRR-MOCK-001', replacementVehicle: 'WP KA-7781', status: 'draft' },
        ],
        runningCharts: [runningCharts[1]],
        sourceReference: { sourceId: 'agr-003', sourceModule: 'vehicle_rental', sourceNumber: 'RA-MOCK-003', sourceType: 'agreement' },
        startAt: '2026-06-01',
        status: 'running',
        updatedAt: '2026-05-30',
        vehicle: 'CP CAB-9410 | Mitsubishi L200',
        vehicleSource: 'external_provider',
        workflowStatus: 'Backend workflow state',
    },
    {
        activity,
        agreementNumber: 'RA-MOCK-004',
        availabilityPreview,
        billingPreview,
        breakdowns: [],
        customer: 'City Events Lanka',
        documentPreview: { documentNumber: 'VRA-DOC-MOCK-004', status: 'Cancelled', template: 'Rental agreement' },
        driver: 'Kasun Perera',
        endAt: '2026-05-28',
        financePreview,
        id: 'agr-004',
        invoices: [],
        lines: [],
        mode: 'with_driver',
        payments: [],
        provider: 'Metro Owner Pool',
        providerPayables: [providerPayables[1]],
        ratePlan: { baseRate: 'Backend resolved', id: 'rate-002', name: 'Hourly with overtime', rentalUnit: 'hour', status: 'active' },
        rateRules: [],
        rentalUnit: 'hour',
        replacements: [],
        runningCharts: [],
        sourceReference: { sourceId: 'agr-004', sourceModule: 'vehicle_rental', sourceNumber: 'RA-MOCK-004', sourceType: 'agreement' },
        startAt: '2026-05-27',
        status: 'cancelled',
        updatedAt: '2026-05-28',
        vehicle: 'WP KA-7781 | Nissan Caravan',
        vehicleSource: 'external_provider',
        workflowStatus: 'Cancelled by backend workflow',
    },
];

export const rentalInvoices = rentalAgreements.flatMap((agreement) => agreement.invoices);
export const rentalPayments = rentalAgreements.flatMap((agreement) => agreement.payments);
export const replacements = rentalAgreements.flatMap((agreement) => agreement.replacements);
export const breakdowns = rentalAgreements.flatMap((agreement) => agreement.breakdowns);

export const rentalSettings: VehicleRentalSettings = {
    agreementSequence: 'RA-{YYYY}-{####}',
    allowExternalProviderVehicles: true,
    allowReplacementVehicle: true,
    allowWithDriverRental: true,
    defaultProviderPayableAccount: 'Rental provider payable',
    defaultRatePlan: 'Daily with KM cap',
    defaultTaxGroup: 'VAT-STANDARD',
    invoiceDocumentDefinition: 'rental_invoice_default',
    invoiceSequence: 'RINV-{YYYY}-{####}',
    runningChartSequence: 'RC-{YYYY}-{####}',
};

export function getAgreementById(id: string) {
    return rentalAgreements.find((agreement) => agreement.id === id || agreement.agreementNumber === id) ?? rentalAgreements[0];
}

export function getRunningChartById(id: string) {
    return runningCharts.find((chart) => chart.id === id || chart.chartNumber === id) ?? runningCharts[0];
}

export function getProviderPayableById(id: string) {
    return providerPayables.find((payable) => payable.id === id || payable.payableNumber === id) ?? providerPayables[0];
}
