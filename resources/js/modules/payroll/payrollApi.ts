/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { apiClient } from '@/shared/api/apiClient';
import { endpoints } from '@/shared/api/endpoints';
import type { ApiResource } from '@/shared/types/api';
import type {
    EmployeeSalaryAssignment,
    PayPeriod,
    PayrollAdjustment,
    PayRun,
    Payslip,
    SalaryComponent,
    SalaryStructure,
    StatutoryRule,
    TaxTable,
} from './payrollTypes';

// --- Salary Components ---
export const listComponents = (params?: { type?: string; is_active?: boolean; search?: string }) =>
    apiClient.get<ApiResource<SalaryComponent[]>>(endpoints.payrollComponents, { params }).then((r) => r.data.data);

export const createComponent = (payload: Partial<SalaryComponent>) =>
    apiClient.post<ApiResource<SalaryComponent>>(endpoints.payrollComponents, payload).then((r) => r.data.data);

export const updateComponent = (id: number, payload: Partial<SalaryComponent>) =>
    apiClient.put<ApiResource<SalaryComponent>>(`${endpoints.payrollComponents}/${id}`, payload).then((r) => r.data.data);

export const deleteComponent = (id: number) =>
    apiClient.delete(`${endpoints.payrollComponents}/${id}`).then((r) => r.data);

// --- Salary Structures ---
export const listStructures = () =>
    apiClient.get<ApiResource<SalaryStructure[]>>(endpoints.payrollStructures).then((r) => r.data.data);

export const getStructure = (id: number) =>
    apiClient.get<ApiResource<SalaryStructure>>(`${endpoints.payrollStructures}/${id}`).then((r) => r.data.data);

export const createStructure = (payload: Partial<SalaryStructure>) =>
    apiClient.post<ApiResource<SalaryStructure>>(endpoints.payrollStructures, payload).then((r) => r.data.data);

export const updateStructure = (id: number, payload: Partial<SalaryStructure>) =>
    apiClient.put<ApiResource<SalaryStructure>>(`${endpoints.payrollStructures}/${id}`, payload).then((r) => r.data.data);

export const deleteStructure = (id: number) =>
    apiClient.delete(`${endpoints.payrollStructures}/${id}`).then((r) => r.data);

// --- Employee Assignments ---
export const listAssignments = (params?: { employee_id?: number; structure_id?: number; is_active?: boolean }) =>
    apiClient.get<ApiResource<EmployeeSalaryAssignment[]>>(endpoints.payrollAssignments, { params }).then((r) => r.data.data);

export const createAssignment = (payload: Partial<EmployeeSalaryAssignment>) =>
    apiClient.post<ApiResource<EmployeeSalaryAssignment>>(endpoints.payrollAssignments, payload).then((r) => r.data.data);

export const updateAssignment = (id: number, payload: Partial<EmployeeSalaryAssignment>) =>
    apiClient.put<ApiResource<EmployeeSalaryAssignment>>(`${endpoints.payrollAssignments}/${id}`, payload).then((r) => r.data.data);

// --- Tax Tables & Statutory Rules ---
export const listTaxTables = () =>
    apiClient.get<ApiResource<TaxTable[]>>(endpoints.payrollTaxTables).then((r) => r.data.data);

export const createTaxTable = (payload: Partial<TaxTable>) =>
    apiClient.post<ApiResource<TaxTable>>(endpoints.payrollTaxTables, payload).then((r) => r.data.data);

export const listStatutoryRules = () =>
    apiClient.get<ApiResource<StatutoryRule[]>>(endpoints.payrollStatutoryRules).then((r) => r.data.data);

export const createStatutoryRule = (payload: Partial<StatutoryRule>) =>
    apiClient.post<ApiResource<StatutoryRule>>(endpoints.payrollStatutoryRules, payload).then((r) => r.data.data);

// --- Pay Periods ---
export const listPayPeriods = () =>
    apiClient.get<ApiResource<PayPeriod[]>>(endpoints.payrollPeriods).then((r) => r.data.data);

export const createPayPeriod = (payload: Partial<PayPeriod>) =>
    apiClient.post<ApiResource<PayPeriod>>(endpoints.payrollPeriods, payload).then((r) => r.data.data);

// --- Pay Runs ---
export const listPayRuns = (params?: { pay_period_id?: number; status?: string }) =>
    apiClient.get<ApiResource<PayRun[]>>(endpoints.payrollPayRuns, { params }).then((r) => r.data.data);

export const createPayRun = (payload: { pay_period_id: number; salary_structure_id?: number | null; pay_run_type?: string; notes?: string }) =>
    apiClient.post<ApiResource<PayRun>>(endpoints.payrollPayRuns, payload).then((r) => r.data.data);

export const getPayRun = (id: number) =>
    apiClient.get<ApiResource<PayRun>>(`${endpoints.payrollPayRuns}/${id}`).then((r) => r.data.data);

export const calculatePayRun = (id: number) =>
    apiClient.post<ApiResource<PayRun>>(`${endpoints.payrollPayRuns}/${id}/calculate`).then((r) => r.data.data);

export const approvePayRun = (id: number) =>
    apiClient.post<ApiResource<PayRun>>(`${endpoints.payrollPayRuns}/${id}/approve`).then((r) => r.data.data);

export const payPayRun = (id: number) =>
    apiClient.post<ApiResource<PayRun>>(`${endpoints.payrollPayRuns}/${id}/pay`).then((r) => r.data.data);

export const reversePayRun = (id: number, reason: string) =>
    apiClient.post<ApiResource<PayRun>>(`${endpoints.payrollPayRuns}/${id}/reverse`, { reason }).then((r) => r.data.data);

// --- Payslips ---
export const listPayslips = (params?: { pay_run_id?: number; employee_id?: number; status?: string }) =>
    apiClient.get<ApiResource<Payslip[]>>(endpoints.payrollPayslips, { params }).then((r) => r.data.data);

export const getPayslip = (id: number) =>
    apiClient.get<ApiResource<Payslip>>(`${endpoints.payrollPayslips}/${id}`).then((r) => r.data.data);

// --- Adjustments ---
export const listAdjustments = (params?: { employee_id?: number; status?: string }) =>
    apiClient.get<ApiResource<PayrollAdjustment[]>>(endpoints.payrollAdjustments, { params }).then((r) => r.data.data);

export const createAdjustment = (payload: Partial<PayrollAdjustment>) =>
    apiClient.post<ApiResource<PayrollAdjustment>>(endpoints.payrollAdjustments, payload).then((r) => r.data.data);

export const cancelAdjustment = (id: number) =>
    apiClient.patch<ApiResource<PayrollAdjustment>>(`${endpoints.payrollAdjustments}/${id}/cancel`).then((r) => r.data.data);
