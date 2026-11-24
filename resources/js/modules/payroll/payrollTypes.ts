/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

export type SalaryComponentType = 'earning' | 'deduction' | 'statutory' | 'employer_contribution';
export type CalculationType = 'fixed' | 'percentage' | 'hourly_rate' | 'formula';
export type PayrollCycleType = 'monthly' | 'bi_weekly' | 'weekly';
export type PayRunType = 'standard' | 'supplementary' | 'correction' | 'bonus';
export type PayRunStatus = 'draft' | 'calculated' | 'verified' | 'approved' | 'paid' | 'reversed';
export type PayslipStatus = 'draft' | 'approved' | 'paid' | 'reversed';
export type PayslipItemType = 'earning' | 'allowance' | 'overtime' | 'deduction' | 'employee_statutory' | 'employer_statutory' | 'tax' | 'adjustment';
export type AdjustmentType = 'addition' | 'deduction' | 'retro_pay' | 'reimbursement' | 'correction';
export type AdjustmentStatus = 'pending' | 'applied' | 'cancelled';

export interface SalaryComponent {
    id: number;
    tenant_id: number;
    organization_unit_id: number | null;
    code: string;
    name: string;
    type: SalaryComponentType;
    calculation_type: CalculationType;
    is_taxable: boolean;
    is_statutory_base: boolean;
    is_active: boolean;
    gl_account_name: string | null;
    description: string | null;
    created_at?: string;
    updated_at?: string;
}

export interface SalaryStructureItem {
    id?: number;
    salary_structure_id?: number;
    salary_component_id: number;
    component?: SalaryComponent;
    calculation_type: CalculationType;
    amount_or_rate: string | number;
    percentage_basis_component_id?: number | null;
    formula_expression?: string | null;
    sort_order: number;
}

export interface SalaryStructure {
    id: number;
    tenant_id: number;
    organization_unit_id: number | null;
    code: string;
    name: string;
    cycle_type: PayrollCycleType;
    currency_id: number | null;
    is_active: boolean;
    description: string | null;
    items?: SalaryStructureItem[];
    created_at?: string;
    updated_at?: string;
}

export interface EmployeeSalaryAssignment {
    id: number;
    tenant_id: number;
    organization_unit_id: number | null;
    employee_id: number;
    employee?: {
        id: number;
        employee_number: string;
        display_name: string;
        department?: { name: string };
        designation?: { name: string };
    };
    salary_structure_id: number;
    structure?: SalaryStructure;
    base_salary: string | number;
    hourly_rate: string | number;
    payment_method: string;
    bank_name: string | null;
    bank_account_number: string | null;
    effective_from: string;
    effective_to: string | null;
    is_active: boolean;
    created_at?: string;
}

export interface TaxBracket {
    id?: number;
    bracket_order: number;
    threshold_from: string | number;
    threshold_to: string | number | null;
    rate_percentage: string | number;
}

export interface TaxTable {
    id: number;
    tenant_id: number;
    code: string;
    name: string;
    effective_year: number;
    standard_personal_relief: string | number;
    is_active: boolean;
    brackets?: TaxBracket[];
}

export interface StatutoryRule {
    id: number;
    tenant_id: number;
    code: string;
    name: string;
    employee_rate_percentage: string | number;
    employer_rate_percentage: string | number;
    applies_to_component_types: string;
    is_active: boolean;
}

export interface PayPeriod {
    id: number;
    tenant_id: number;
    name: string;
    cycle_type: PayrollCycleType;
    period_start: string;
    period_end: string;
    payout_date: string;
    total_working_days: number;
    is_closed: boolean;
}

export interface PayRun {
    id: number;
    tenant_id: number;
    organization_unit_id: number | null;
    pay_run_number: string;
    pay_period_id: number;
    period?: PayPeriod;
    salary_structure_id: number | null;
    structure?: SalaryStructure;
    pay_run_type: PayRunType;
    status: PayRunStatus;
    total_gross_pay: string | number;
    total_deductions: string | number;
    total_employee_statutory: string | number;
    total_employer_statutory: string | number;
    total_tax: string | number;
    total_net_pay: string | number;
    total_employees: number;
    calculated_by: number | null;
    calculated_at: string | null;
    approved_by: number | null;
    approved_at: string | null;
    paid_by: number | null;
    paid_at: string | null;
    reversed_by: number | null;
    reversed_at: string | null;
    reversal_reason: string | null;
    finance_journal_id: number | null;
    notes: string | null;
    payslips?: Payslip[];
    created_at?: string;
}

export interface PayslipItem {
    id: number;
    payslip_id: number;
    salary_component_id: number | null;
    component?: SalaryComponent;
    item_type: PayslipItemType;
    item_code: string;
    item_name: string;
    rate: string | number;
    hours_or_quantity: string | number;
    amount: string | number;
    is_taxable: boolean;
    is_statutory: boolean;
    calculation_basis: string | null;
}

export interface Payslip {
    id: number;
    tenant_id: number;
    organization_unit_id: number | null;
    payslip_number: string;
    pay_run_id: number;
    pay_run?: PayRun;
    employee_id: number;
    employee?: {
        id: number;
        employee_number: string;
        display_name: string;
        department?: { name: string };
        designation?: { name: string };
        joined_date?: string;
    };
    salary_structure_id: number;
    structure?: SalaryStructure;
    period_start: string;
    period_end: string;
    status: PayslipStatus;
    eligible_days: string | number;
    standard_days: string | number;
    worked_days: string | number;
    unpaid_leave_days: string | number;
    absent_days: string | number;
    overtime_hours: string | number;
    double_time_hours: string | number;
    base_salary: string | number;
    prorated_basic_salary: string | number;
    gross_pay: string | number;
    total_allowances: string | number;
    total_deductions: string | number;
    employee_statutory_deductions: string | number;
    employer_statutory_contributions: string | number;
    tax_deduction: string | number;
    net_pay: string | number;
    payment_method: string;
    items?: PayslipItem[];
    notes: string | null;
    created_at?: string;
}

export interface PayrollAdjustment {
    id: number;
    tenant_id: number;
    organization_unit_id: number | null;
    employee_id: number;
    employee?: {
        id: number;
        employee_number: string;
        display_name: string;
    };
    adjustment_type: AdjustmentType;
    salary_component_id: number | null;
    component?: SalaryComponent;
    amount: string | number;
    effective_pay_period_id: number | null;
    pay_period?: PayPeriod;
    status: AdjustmentStatus;
    applied_payslip_id: number | null;
    reason: string;
    approved_by: number | null;
    approved_at: string | null;
    created_at?: string;
}

export type LoanType = 'loan' | 'advance';
export type LoanStatus = 'draft' | 'approved' | 'active' | 'completed' | 'cancelled';
export type LoanRepaymentStatus = 'scheduled' | 'deducted' | 'manual_payment' | 'reversed';

export interface PayrollLoanRepayment {
    id: number;
    tenant_id: number;
    payroll_employee_loan_id: number;
    pay_run_id: number | null;
    payslip_id: number | null;
    repayment_date: string;
    principal_amount: string | number;
    interest_amount: string | number;
    total_repaid: string | number;
    balance_after: string | number;
    status: LoanRepaymentStatus;
    notes: string | null;
    created_at?: string;
}

export interface PayrollLoan {
    id: number;
    tenant_id: number;
    organization_unit_id: number | null;
    employee_id: number;
    employee?: {
        id: number;
        employee_number: string;
        display_name: string;
        department?: { name: string };
        designation?: { name: string };
    };
    loan_reference: string;
    loan_type: LoanType;
    principal_amount: string | number;
    annual_interest_rate: string | number;
    term_months: number;
    monthly_installment: string | number;
    total_payable: string | number;
    total_paid: string | number;
    balance_remaining: string | number;
    disbursed_date: string | null;
    deduction_start_period_id: number | null;
    deduction_start_period?: PayPeriod;
    status: LoanStatus;
    approved_by: number | null;
    approved_at: string | null;
    notes: string | null;
    repayments?: PayrollLoanRepayment[];
    created_at?: string;
}

export interface PayrollLoanFormState {
    employee_id: number | '';
    loan_type: LoanType;
    principal_amount: string;
    annual_interest_rate: string;
    term_months: number;
    deduction_start_period_id: number | '';
    notes: string;
}
