import { apiClient } from '@/shared/api/apiClient';

export interface ReportHeader {
    company_name: string;
    report_title: string;
    duration: string;
    start_date: string;
    end_date: string;
    report_date: string;
}

export interface ReportFooter {
    software_provider: string;
}

// 1. Fleet Utilization Report
export interface FleetUtilizationReportParams {
    start_date: string;
    end_date: string;
    ownership_type?: 'all' | 'company' | 'owner_leased';
    custody_status?: 'all' | 'in_custody' | 'planned' | 'available';
    vehicle_id?: number | null;
}

export interface FleetUtilizationReportData {
    header: ReportHeader;
    filters: { ownership_type: string; custody_status: string };
    summary: {
        total_vehicles: number;
        company_owned_count: number;
        owner_leased_count: number;
        in_custody_count: number;
        available_count: number;
        average_utilization_rate: number;
        total_distance_km: number;
    };
    rows: Array<{
        vehicle_id: number;
        registration_number: string;
        vehicle_make_model: string;
        ownership_type: string;
        owner_name: string;
        owner_agreement_number: string;
        custody_status: string;
        status_key: string;
        current_customer: string;
        current_agreement: string;
        current_driver: string;
        current_odometer: string;
        period_distance_km: number;
        days_in_custody: number;
        utilization_rate: number;
    }>;
    footer: ReportFooter;
}

export const fetchFleetUtilizationReport = (params: FleetUtilizationReportParams, signal?: AbortSignal) =>
    apiClient.get<FleetUtilizationReportData>('/api/v1/vehicle-rental/reports/fleet-utilization', { params, signal }).then(r => r.data);

export const getFleetUtilizationReportExportUrl = (params: FleetUtilizationReportParams, format: 'pdf' | 'print' | 'html') => {
    const query = new URLSearchParams({
        start_date: params.start_date,
        end_date: params.end_date,
        ...(params.ownership_type ? { ownership_type: params.ownership_type } : {}),
        ...(params.custody_status ? { custody_status: params.custody_status } : {}),
        ...(params.vehicle_id ? { vehicle_id: String(params.vehicle_id) } : {}),
    });
    return `/api/v1/vehicle-rental/reports/fleet-utilization/export/${format}?${query.toString()}`;
};

// 2. Rental Revenue Report
export interface RentalRevenueReportParams {
    start_date: string;
    end_date: string;
    customer_id?: number | null;
    agreement_id?: number | null;
}

export interface RentalRevenueReportData {
    header: ReportHeader;
    filters: { customer_id: number | null; agreement_id: number | null };
    summary: {
        total_agreements: number;
        total_base_rent: string;
        total_excess_mileage: string;
        total_overtime: string;
        total_night_out: string;
        total_toll_pass: string;
        grand_total_revenue: string;
    };
    rows: Array<{
        agreement_id: number;
        agreement_reference: string;
        customer_id: number;
        customer_name: string;
        customer_code: string;
        vehicle_number: string;
        rental_basis: string;
        driver_mode: string;
        status: string;
        period_start: string;
        period_end: string;
        base_rent: string;
        excess_mileage: string;
        overtime: string;
        night_out: string;
        toll_pass: string;
        total_revenue: string;
    }>;
    footer: ReportFooter;
}

export const fetchRentalRevenueReport = (params: RentalRevenueReportParams, signal?: AbortSignal) =>
    apiClient.get<RentalRevenueReportData>('/api/v1/vehicle-rental/reports/rental-revenue', { params, signal }).then(r => r.data);

export const getRentalRevenueReportExportUrl = (params: RentalRevenueReportParams, format: 'pdf' | 'print' | 'html') => {
    const query = new URLSearchParams({
        start_date: params.start_date,
        end_date: params.end_date,
        ...(params.customer_id ? { customer_id: String(params.customer_id) } : {}),
        ...(params.agreement_id ? { agreement_id: String(params.agreement_id) } : {}),
    });
    return `/api/v1/vehicle-rental/reports/rental-revenue/export/${format}?${query.toString()}`;
};

// 3. Owner Disbursement Report
export interface OwnerDisbursementReportParams {
    start_date: string;
    end_date: string;
    supplier_id?: number | null;
    owner_agreement_id?: number | null;
    wht_rate?: number;
    report_type?: 'summary' | 'wht_ledger';
}

export interface WhtLedgerEntry {
    date: string;
    raw_date: string;
    reference: string;
    description: string;
    debit: string;
    credit: string;
    balance: string;
    owner_name: string;
    owner_code: string;
    vehicle_number: string;
    wht_amount: string;
}

export interface OwnerDisbursementReportData {
    header: ReportHeader & {
        account_code?: string;
        report_type?: string;
        wht_rate?: number;
    };
    filters: {
        supplier_id: number | null;
        owner_agreement_id: number | null;
        wht_rate?: number;
        report_type?: string;
    };
    summary: {
        total_owner_agreements: number;
        total_base_payable: string;
        total_mileage_payable: string;
        total_overtime_payable: string;
        total_gross_payable: string;
        total_wht_amount: string;
        total_deductions: string;
        total_net_payable: string;
    };
    rows: Array<{
        owner_agreement_id: number;
        agreement_reference: string;
        supplier_id: number;
        owner_name: string;
        owner_code: string;
        vehicle_number: string;
        rental_basis: string;
        status: string;
        period_start: string;
        period_end: string;
        base_payable: string;
        mileage_payable: string;
        overtime_payable: string;
        gross_payable: string;
        wht_rate: number;
        wht_amount: string;
        deductions: string;
        net_payable: string;
    }>;
    wht_ledger?: WhtLedgerEntry[];
    footer: ReportFooter;
}

export const fetchOwnerDisbursementReport = (params: OwnerDisbursementReportParams, signal?: AbortSignal) =>
    apiClient.get<OwnerDisbursementReportData>('/api/v1/vehicle-rental/reports/owner-disbursements', { params, signal }).then(r => r.data);

export const getOwnerDisbursementReportExportUrl = (params: OwnerDisbursementReportParams, format: 'pdf' | 'print' | 'html') => {
    const query = new URLSearchParams({
        start_date: params.start_date,
        end_date: params.end_date,
        ...(params.supplier_id ? { supplier_id: String(params.supplier_id) } : {}),
        ...(params.owner_agreement_id ? { owner_agreement_id: String(params.owner_agreement_id) } : {}),
        ...(params.wht_rate !== undefined ? { wht_rate: String(params.wht_rate) } : {}),
        ...(params.report_type ? { report_type: params.report_type } : {}),
    });
    return `/api/v1/vehicle-rental/reports/owner-disbursements/export/${format}?${query.toString()}`;
};

// 4. Vehicle Profitability Report
export interface VehicleProfitabilityReportParams {
    start_date: string;
    end_date: string;
    ownership_type?: 'all' | 'company' | 'owner_leased';
    vehicle_id?: number | null;
}

export interface VehicleProfitabilityReportData {
    header: ReportHeader;
    filters: { ownership_type: string };
    summary: {
        total_vehicles_evaluated: number;
        fleet_total_revenue: string;
        fleet_total_direct_costs: string;
        fleet_gross_margin: string;
        fleet_margin_percentage: number;
        fleet_total_distance_km: number;
        fleet_avg_revenue_per_km: string;
        fleet_avg_cost_per_km: string;
    };
    rows: Array<{
        vehicle_id: number;
        registration_number: string;
        vehicle_make_model: string;
        ownership_type: string;
        customer_revenue: string;
        owner_lease_cost: string;
        fuel_cost: string;
        maintenance_cost: string;
        toll_cost: string;
        other_cost: string;
        total_direct_costs: string;
        gross_margin: string;
        margin_percentage: number;
        distance_km: number;
        revenue_per_km: string;
        cost_per_km: string;
    }>;
    footer: ReportFooter;
}

export const fetchVehicleProfitabilityReport = (params: VehicleProfitabilityReportParams, signal?: AbortSignal) =>
    apiClient.get<VehicleProfitabilityReportData>('/api/v1/vehicle-rental/reports/vehicle-profitability', { params, signal }).then(r => r.data);

export const getVehicleProfitabilityReportExportUrl = (params: VehicleProfitabilityReportParams, format: 'pdf' | 'print' | 'html') => {
    const query = new URLSearchParams({
        start_date: params.start_date,
        end_date: params.end_date,
        ...(params.ownership_type ? { ownership_type: params.ownership_type } : {}),
        ...(params.vehicle_id ? { vehicle_id: String(params.vehicle_id) } : {}),
    });
    return `/api/v1/vehicle-rental/reports/vehicle-profitability/export/${format}?${query.toString()}`;
};

// 5. Rental Incident Report
export interface RentalIncidentReportParams {
    start_date: string;
    end_date: string;
    incident_type?: string;
    status?: string;
    vehicle_id?: number | null;
}

export interface RentalIncidentReportData {
    header: ReportHeader;
    filters: { incident_type: string | null; status: string | null; vehicle_id: number | null };
    summary: {
        total_incidents: number;
        recorded_count: number;
        confirmed_count: number;
        rejected_count: number;
        type_breakdown: Record<string, number>;
    };
    rows: Array<{
        incident_id: number;
        reference: string;
        occurred_on: string;
        vehicle_number: string;
        customer_name: string;
        agreement_reference: string;
        incident_type: string;
        status: string;
        evidence_reference: string;
        description: string;
        attachment_count: number;
        reviewed_at: string;
    }>;
    footer: ReportFooter;
}

export const fetchRentalIncidentReport = (params: RentalIncidentReportParams, signal?: AbortSignal) =>
    apiClient.get<RentalIncidentReportData>('/api/v1/vehicle-rental/reports/incidents-summary', { params, signal }).then(r => r.data);

export const getRentalIncidentReportExportUrl = (params: RentalIncidentReportParams, format: 'pdf' | 'print' | 'html') => {
    const query = new URLSearchParams({
        start_date: params.start_date,
        end_date: params.end_date,
        ...(params.incident_type ? { incident_type: params.incident_type } : {}),
        ...(params.status ? { status: params.status } : {}),
        ...(params.vehicle_id ? { vehicle_id: String(params.vehicle_id) } : {}),
    });
    return `/api/v1/vehicle-rental/reports/incidents-summary/export/${format}?${query.toString()}`;
};

// 6. Vehicle Revenue License Expiry Report (pmcrelicence.frx)
export interface VehicleRevenueLicenseReportParams {
    start_date?: string;
    end_date?: string;
    status?: 'all' | 'expired' | 'expiring_soon' | 'active';
    vehicle_id?: number | null;
}

export interface VehicleRevenueLicenseReportData {
    header: {
        company_name: string;
        system_title: string;
        report_title: string;
        period_description: string;
        start_date: string | null;
        end_date: string | null;
        generated_at: string;
    };
    summary: {
        total_vehicles: number;
        expired_count: number;
        expiring_soon_count: number;
        active_count: number;
    };
    data: Array<{
        vehicle_id: number;
        vehicle_no: string;
        owner_name: string;
        ownership_type: string;
        expire_date: string;
        raw_expire_date: string | null;
        days_remaining: number | null;
        document_number: string;
        status: string;
        status_label: string;
    }>;
}

export const fetchVehicleRevenueLicenseReport = (params: VehicleRevenueLicenseReportParams, signal?: AbortSignal) =>
    apiClient.get<VehicleRevenueLicenseReportData>('/api/v1/vehicle-rental/reports/revenue-licenses', { params, signal }).then(r => r.data);

export const getVehicleRevenueLicenseReportExportUrl = (params: VehicleRevenueLicenseReportParams, format: 'pdf' | 'print' | 'html') => {
    const query = new URLSearchParams({
        ...(params.start_date ? { start_date: params.start_date } : {}),
        ...(params.end_date ? { end_date: params.end_date } : {}),
        ...(params.status ? { status: params.status } : {}),
        ...(params.vehicle_id ? { vehicle_id: String(params.vehicle_id) } : {}),
    });
    return `/api/v1/vehicle-rental/reports/revenue-licenses/export/${format}?${query.toString()}`;
};

// 7. Lessee Outstanding Age Analysis Report (pmdebinv5.frx)
export interface LesseeOutstandingReportParams {
    as_of_date?: string;
    customer_id?: number | null;
    agreement_id?: number | null;
    aging_bucket?: 'all' | '0_30' | '31_60' | '61_90' | '90_plus';
}

export interface LesseeOutstandingReportData {
    header: {
        company_name: string;
        system_title: string;
        report_title: string;
        as_of_date: string;
        generated_at: string;
    };
    summary: {
        total_lessees_count: number;
        total_invoices_count: number;
        grand_total_original_amount: string;
        grand_total_balance_amount: string;
    };
    groups: Array<{
        customer_id: number;
        lessee_code: string;
        lessee_name: string;
        phone: string;
        contact_person: string;
        subtotal_original_amount: string;
        subtotal_balance_amount: string;
        invoices: Array<{
            invoice_id: number;
            txn_type: string;
            invoice_date: string;
            raw_invoice_date: string;
            invoice_number: string;
            agreement_duration: string;
            agreement_reference: string;
            vehicle_no: string;
            original_amount: string;
            balance_amount: string;
            age_days: number;
        }>;
    }>;
}

export const fetchLesseeOutstandingReport = (params: LesseeOutstandingReportParams, signal?: AbortSignal) =>
    apiClient.get<LesseeOutstandingReportData>('/api/v1/vehicle-rental/reports/lessee-outstanding', { params, signal }).then(r => r.data);

export const getLesseeOutstandingReportExportUrl = (params: LesseeOutstandingReportParams, format: 'pdf' | 'print' | 'html') => {
    const query = new URLSearchParams({
        ...(params.as_of_date ? { as_of_date: params.as_of_date } : {}),
        ...(params.customer_id ? { customer_id: String(params.customer_id) } : {}),
        ...(params.agreement_id ? { agreement_id: String(params.agreement_id) } : {}),
        ...(params.aging_bucket ? { aging_bucket: params.aging_bucket } : {}),
    });
    return `/api/v1/vehicle-rental/reports/lessee-outstanding/export/${format}?${query.toString()}`;
};
