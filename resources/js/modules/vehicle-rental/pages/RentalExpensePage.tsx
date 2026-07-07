import { useState, type FormEvent } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { CustomerLookupSelect } from '@/modules/customer/components/CustomerLookupSelect';
import type { CustomerSummary } from '@/modules/customer/customerTypes';
import { searchEmployees } from '@/modules/hr/hrApi';
import type { EmployeeSummary } from '@/modules/hr/hrTypes';
import { SupplierLookupSelect } from '@/modules/supplier/components/SupplierLookupSelect';
import type { SupplierSummary } from '@/modules/supplier/supplierTypes';
import { VehicleLookupSelect } from '@/modules/vehicle/components/VehicleLookupSelect';
import type { VehicleSummary } from '@/modules/vehicle/vehicleTypes';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import type { NamedResource } from '@/shared/types/common';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { formatDate } from '@/shared/utils/formatDate';
import { readableRelation } from '@/shared/utils/object';
import {
    RentalAgreementLookupSelect,
    RentalAllocationLookupSelect,
    RentalCurrencyLookupSelect,
    RentalTaxGroupLookupSelect,
} from '../components/RentalLookups';
import { RentalPage } from '../components/RentalPage';
import {
    createRentalExpense,
    listRentalExpenses,
    transitionRentalExpense,
} from '../vehicleRentalApi';
import { vehicleRentalPermissions } from '../vehicleRentalPermissions';
import type { RentalExpense } from '../vehicleRentalTypes';

interface ExpenseForm {
    expenseType: string;
    expenseDate: string;
    netAmount: string;
    taxAmount: string;
    referenceNumber: string;
    description: string;
    allocationType: string;
    withholdingAmount: string;
    markupAmount: string;
}

const emptyForm = (): ExpenseForm => ({
    expenseType: 'fuel',
    expenseDate: businessDateInputValue(),
    netAmount: '',
    taxAmount: '0',
    referenceNumber: '',
    description: '',
    allocationType: 'company_cost',
    withholdingAmount: '0',
    markupAmount: '0',
});

export default function RentalExpensePage() {
    const auth = useAuth();
    const [form, setForm] = useState<ExpenseForm>(emptyForm);
    const [vehicle, setVehicle] = useState<VehicleSummary | null>(null);
    const [currency, setCurrency] = useState<NamedResource | null>(null);
    const [taxGroup, setTaxGroup] = useState<NamedResource | null>(null);
    const [targetAgreement, setTargetAgreement] = useState<NamedResource | null>(null);
    const [targetAllocation, setTargetAllocation] = useState<NamedResource | null>(null);
    const [customer, setCustomer] = useState<CustomerSummary | null>(null);
    const [supplier, setSupplier] = useState<SupplierSummary | null>(null);
    const [employee, setEmployee] = useState<EmployeeSummary | null>(null);
    const [saving, setSaving] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [status, setStatus] = useState('');

    const result = useApi(
        (signal) => listRentalExpenses({ status: status || undefined, per_page: 50 }, signal),
        [status],
    );
    const canRecord = hasPermission(auth, vehicleRentalPermissions.expensesRecord);
    const canApprove = hasPermission(auth, vehicleRentalPermissions.expensesApprove);

    const changeAllocationType = (allocationType: string) => {
        setForm((current) => ({ ...current, allocationType }));
        setTargetAgreement(null);
        setTargetAllocation(null);
        setCustomer(null);
        setSupplier(null);
        setEmployee(null);
    };

    const save = async (event: FormEvent) => {
        event.preventDefault();
        if (!vehicle || !currency) return;

        setSaving(true);
        setActionError(null);
        try {
            const agreementBacked = form.allocationType === 'customer_recovery'
                || form.allocationType === 'owner_deduction';
            await createRentalExpense({
                vehicle_id: vehicle.id,
                expense_type: form.expenseType,
                expense_date: form.expenseDate,
                currency_id: currency.id,
                net_amount: form.netAmount,
                tax_group_id: taxGroup?.id ?? null,
                tax_amount: form.taxAmount,
                reference_number: form.referenceNumber || null,
                description: form.description || null,
                allocations: [
                    {
                        allocation_type: form.allocationType,
                        target_agreement_id: agreementBacked ? targetAgreement?.id ?? null : null,
                        target_vehicle_allocation_id: agreementBacked ? targetAllocation?.id ?? null : null,
                        customer_id: form.allocationType === 'customer_recovery' ? customer?.id ?? null : null,
                        supplier_id: form.allocationType === 'owner_deduction' ? supplier?.id ?? null : null,
                        employee_id: form.allocationType === 'employee_reimbursement' ? employee?.id ?? null : null,
                        net_amount: form.netAmount,
                        tax_group_id: taxGroup?.id ?? null,
                        tax_amount: form.taxAmount,
                        withholding_amount: form.withholdingAmount,
                        markup_amount: form.markupAmount,
                    },
                ],
            });
            setForm(emptyForm());
            setVehicle(null);
            setCurrency(null);
            setTaxGroup(null);
            setTargetAgreement(null);
            setTargetAllocation(null);
            setCustomer(null);
            setSupplier(null);
            setEmployee(null);
            result.reload();
        } catch (error: unknown) {
            setActionError(toApiError(error));
        } finally {
            setSaving(false);
        }
    };

    const transition = async (row: RentalExpense, nextStatus: string) => {
        setActionError(null);
        try {
            await transitionRentalExpense(row.id, row.row_version, nextStatus);
            result.reload();
        } catch (error: unknown) {
            setActionError(toApiError(error));
        }
    };

    const columns: DataColumn<RentalExpense>[] = [
        { key: 'number', header: 'Expense', render: (row) => row.expense_number },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.expense_date) },
        { key: 'vehicle', header: 'Vehicle', render: (row) => readableRelation(row.vehicle) },
        { key: 'type', header: 'Type', render: (row) => row.expense_type.replaceAll('_', ' ') },
        { key: 'amount', header: 'Gross amount', render: (row) => row.gross_amount },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (row) => (
                <div className="flex justify-end gap-2">
                    {canRecord && row.status === 'draft' && (
                        <Button variant="ghost" onClick={() => void transition(row, 'submitted')}>
                            Submit
                        </Button>
                    )}
                    {canApprove && row.status === 'submitted' && (
                        <Button variant="ghost" onClick={() => void transition(row, 'approved')}>
                            Approve
                        </Button>
                    )}
                    {canApprove && row.status === 'submitted' && (
                        <Button variant="ghost" onClick={() => void transition(row, 'rejected')}>
                            Reject
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    const partySelectionValid = form.allocationType === 'customer_recovery'
        ? customer !== null
        : form.allocationType === 'owner_deduction'
            ? supplier !== null
            : form.allocationType === 'employee_reimbursement'
                ? employee !== null
                : true;
    const agreementBackedAllocation = form.allocationType === 'customer_recovery'
        || form.allocationType === 'owner_deduction';

    return (
        <RentalPage>
            <ContentHeader
                title="Rental expenses"
                description="Record an operational expense once, then allocate it as company cost, customer recovery or owner deduction through guided relationships."
            />
            <ErrorAlert error={actionError ?? result.error} />
            {canRecord && (
                <form onSubmit={save} className="mb-5">
                    <Panel title="New expense">
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <VehicleLookupSelect value={vehicle} onChange={setVehicle} required />
                            <Select
                                label="Expense type"
                                value={form.expenseType}
                                onChange={(event) => setForm({ ...form, expenseType: event.target.value })}
                                options={[
                                    'fuel',
                                    'toll',
                                    'parking',
                                    'repair',
                                    'service',
                                    'driver_allowance',
                                    'licence',
                                    'insurance',
                                    'damage',
                                    'other',
                                ].map((value) => ({ value, label: value.replaceAll('_', ' ') }))}
                            />
                            <Input
                                label="Expense date"
                                type="date"
                                required
                                value={form.expenseDate}
                                onChange={(event) => setForm({ ...form, expenseDate: event.target.value })}
                            />
                            <RentalCurrencyLookupSelect value={currency} onChange={setCurrency} required />
                            <Input
                                label="Net amount"
                                type="number"
                                min="0.000001"
                                step="0.000001"
                                required
                                value={form.netAmount}
                                onChange={(event) => setForm({ ...form, netAmount: event.target.value })}
                            />
                            <Input
                                label="Tax amount"
                                type="number"
                                min="0"
                                step="0.000001"
                                value={form.taxAmount}
                                onChange={(event) => setForm({ ...form, taxAmount: event.target.value })}
                            />
                            <RentalTaxGroupLookupSelect value={taxGroup} onChange={setTaxGroup} />
                            <Input
                                label="Reference"
                                value={form.referenceNumber}
                                onChange={(event) => setForm({ ...form, referenceNumber: event.target.value })}
                            />
                            <Select
                                label="Financial treatment"
                                value={form.allocationType}
                                onChange={(event) => changeAllocationType(event.target.value)}
                                options={[
                                    'company_cost',
                                    'customer_recovery',
                                    'owner_deduction',
                                    'employee_reimbursement',
                                ].map((value) => ({ value, label: value.replaceAll('_', ' ') }))}
                            />
                            <RentalAgreementLookupSelect
                                value={targetAgreement}
                                onChange={(value) => {
                                    setTargetAgreement(value);
                                    setTargetAllocation(null);
                                }}
                                direction={form.allocationType === 'owner_deduction' ? 'outbound' : 'inbound'}
                                disabled={!agreementBackedAllocation}
                            />
                            <RentalAllocationLookupSelect
                                value={targetAllocation}
                                onChange={setTargetAllocation}
                                agreementId={targetAgreement?.id}
                                disabled={!agreementBackedAllocation}
                            />
                            <Input
                                label="Withholding amount"
                                type="number"
                                min="0"
                                step="0.000001"
                                value={form.withholdingAmount}
                                onChange={(event) => setForm({ ...form, withholdingAmount: event.target.value })}
                            />
                            <Input
                                label="Markup amount"
                                type="number"
                                min="0"
                                step="0.000001"
                                value={form.markupAmount}
                                onChange={(event) => setForm({ ...form, markupAmount: event.target.value })}
                            />
                            {form.allocationType === 'customer_recovery' && (
                                <CustomerLookupSelect value={customer} onChange={setCustomer} />
                            )}
                            {form.allocationType === 'owner_deduction' && (
                                <SupplierLookupSelect value={supplier} onChange={setSupplier} />
                            )}
                            {form.allocationType === 'employee_reimbursement' && (
                                <LookupSelect
                                    label="Employee"
                                    value={employee}
                                    onChange={setEmployee}
                                    search={searchEmployees}
                                    placeholder="Search employee..."
                                    required
                                />
                            )}
                        </div>
                        <div className="mt-4">
                            <Textarea
                                label="Description"
                                value={form.description}
                                onChange={(event) => setForm({ ...form, description: event.target.value })}
                            />
                        </div>
                        <div className="mt-4 flex justify-end">
                            <Button
                                type="submit"
                                loading={saving}
                                disabled={!vehicle || !currency || !form.netAmount || !partySelectionValid}
                            >
                                Save expense
                            </Button>
                        </div>
                    </Panel>
                </form>
            )}
            <div className="mb-4 max-w-sm">
                <Select
                    label="Status"
                    value={status}
                    onChange={(event) => setStatus(event.target.value)}
                    options={['draft', 'submitted', 'approved', 'rejected', 'allocated', 'reversed']
                        .map((value) => ({ value, label: value }))}
                />
            </div>
            {result.loading ? (
                <LoadingState />
            ) : (
                <DataTable
                    rows={result.data?.data ?? []}
                    columns={columns}
                    rowKey={(row) => row.id}
                    emptyMessage="No rental expenses found."
                />
            )}
        </RentalPage>
    );
}
