export type VehicleRentalAgreementStatus = 'draft' | 'active' | 'running' | 'invoiceable' | 'closed' | 'cancelled';

export type VehicleRentalSourceReference = {
    sourceId?: string;
    sourceModule: string;
    sourceNumber?: string;
    sourceType: string;
};

export type VehicleRentalAvailabilityPreview = {
    breakdown: Array<{ label: string; value: string }>;
    calculated: {
        availabilityDecision: string;
        conflicts: string;
        replacementOption: string;
        vehicleStatus: string;
    };
    errors: string[];
    input: Record<string, unknown>;
    warnings: string[];
};

export type VehicleRentalBillingPreview = {
    breakdown: Array<{ label: string; value: string }>;
    calculated: {
        discountTotal: string;
        grandTotal: string;
        providerPayable: string;
        rentalCharge: string;
        taxTotal: string;
    };
    errors: string[];
    input: Record<string, unknown>;
    warnings: string[];
};

export type VehicleRentalFinancePostingPreview = {
    breakdown: Array<{ account: string; effect: string }>;
    calculated: {
        arImpact: string;
        apImpact: string;
        eligibility: string;
        journalStatus: string;
    };
    errors: string[];
    input: Record<string, unknown>;
    warnings: string[];
};

export type VehicleRentalAgreementLine = {
    backendAmount: string;
    chargeScope: 'customer' | 'provider' | 'internal';
    description: string;
    id: string;
    item: string;
    rentalUnit: 'km' | 'hour' | 'day' | 'week' | 'month' | 'fixed';
    usageBasis: string;
};

export type VehicleRentalRatePlan = {
    baseRate: string;
    id: string;
    name: string;
    rentalUnit: string;
    status: string;
};

export type VehicleRentalRateRule = {
    id: string;
    ruleName: string;
    ruleType: string;
    scope: string;
    valuePreview: string;
};

export type VehicleRentalRunningChartLine = {
    chargePreview: string;
    driver: string;
    endReading: string;
    id: string;
    lineNumber: string;
    providerCostPreview: string;
    startReading: string;
    usagePreview: string;
    vehicle: string;
};

export type VehicleRentalRunningChart = {
    agreementNumber: string;
    billingPreview: VehicleRentalBillingPreview;
    chartNumber: string;
    customer: string;
    driver: string;
    endAt: string;
    id: string;
    lines: VehicleRentalRunningChartLine[];
    providerPayablePreview: string;
    startAt: string;
    status: string;
    vehicle: string;
};

export type VehicleRentalInvoice = {
    billingPreview: string;
    customer: string;
    documentStatus: string;
    id: string;
    invoiceNumber: string;
    sourceAgreement: string;
    status: string;
};

export type VehicleRentalPayment = {
    allocationPreview: string;
    amount: string;
    customer: string;
    id: string;
    method: string;
    paymentNumber: string;
    sourceInvoice: string;
    status: string;
};

export type VehicleRentalProviderPayable = {
    agreementNumber: string;
    financeStatus: string;
    id: string;
    payableNumber: string;
    payablePreview: string;
    paymentStatus: string;
    provider: string;
    sourceReference: string;
    status: string;
};

export type VehicleRentalReplacement = {
    agreementNumber: string;
    id: string;
    originalVehicle: string;
    reason: string;
    replacementNumber: string;
    replacementVehicle: string;
    status: string;
};

export type VehicleRentalBreakdown = {
    agreementNumber: string;
    breakdownNumber: string;
    id: string;
    resolution: string;
    status: string;
    vehicle: string;
};

export type VehicleRentalAuditEntry = {
    actor: string;
    id: string;
    note: string;
    timestamp: string;
    type: string;
};

export type VehicleRentalSettings = {
    allowExternalProviderVehicles: boolean;
    allowReplacementVehicle: boolean;
    allowWithDriverRental: boolean;
    agreementSequence: string;
    defaultProviderPayableAccount: string;
    defaultRatePlan: string;
    defaultTaxGroup: string;
    invoiceDocumentDefinition: string;
    invoiceSequence: string;
    runningChartSequence: string;
};

export type VehicleRentalAgreement = {
    activity: VehicleRentalAuditEntry[];
    agreementNumber: string;
    availabilityPreview: VehicleRentalAvailabilityPreview;
    billingPreview: VehicleRentalBillingPreview;
    customer: string;
    documentPreview: { documentNumber: string; status: string; template: string };
    driver: string;
    endAt: string;
    financePreview: VehicleRentalFinancePostingPreview;
    id: string;
    invoices: VehicleRentalInvoice[];
    lines: VehicleRentalAgreementLine[];
    mode: 'with_driver' | 'without_driver';
    payments: VehicleRentalPayment[];
    provider: string;
    providerPayables: VehicleRentalProviderPayable[];
    ratePlan: VehicleRentalRatePlan;
    rateRules: VehicleRentalRateRule[];
    replacements: VehicleRentalReplacement[];
    breakdowns: VehicleRentalBreakdown[];
    rentalUnit: 'km' | 'hour' | 'day' | 'week' | 'month';
    runningCharts: VehicleRentalRunningChart[];
    sourceReference: VehicleRentalSourceReference;
    startAt: string;
    status: VehicleRentalAgreementStatus;
    updatedAt: string;
    vehicle: string;
    vehicleSource: 'own_fleet' | 'external_provider';
    workflowStatus: string;
};

export type VehicleRentalDashboardMetric = {
    label: string;
    tone: string;
    value: string;
};
