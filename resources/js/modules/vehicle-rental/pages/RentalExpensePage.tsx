import { useCallback, useState } from 'react';
import { useParams } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable } from '@/shared/components/DataTable';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { useAuth } from '@/modules/auth/AuthProvider';
import { searchEmployees } from '@/modules/hr/hrApi';
import type { EmployeeSummary } from '@/modules/hr/hrTypes';
import { searchSuppliers } from '@/modules/supplier/supplierApi';
import type { SupplierSummary } from '@/modules/supplier/supplierTypes';
import { RentalStatusBadge } from '../components/RentalStatusBadge';
import { changeRentalExpenseStatus, createRentalExpense, getRentalAgreement, listRentalExpenses } from '../vehicleRentalApi';

const today = () => new Date().toISOString().slice(0, 10);

export default function RentalExpensePage() {
    const agreementId = Number(useParams().id);
    const auth = useAuth();
    const agreement = useApi((signal) => getRentalAgreement(agreementId, signal), [agreementId]);
    const expenses = useApi((signal) => listRentalExpenses(agreementId, signal), [agreementId]);
    const [form, setForm] = useState({
        expense_type: 'fuel',
        expense_date: today(),
        amount: '',
        financial_treatment: 'company_borne',
        receipt_no: '',
        reference_no: '',
        description: '',
    });
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [employee, setEmployee] = useState<EmployeeSummary | null>(null);
    const [supplier, setSupplier] = useState<SupplierSummary | null>(null);
    const searchEmployee = useCallback(
        (query: string, signal: AbortSignal) => searchEmployees(query, signal),
        [],
    );
    const searchSupplier = useCallback(
        (query: string, signal: AbortSignal) => searchSuppliers(query, signal),
        [],
    );
    if (agreement.loading) return <LoadingState />;
    if (!agreement.data) return <ErrorAlert error={agreement.error} />;
    const superAdmin = auth.user?.roles?.includes('Super Admin');
    const canRecord = superAdmin
        || auth.user?.permissions?.includes('vehicle-rental.expenses.record');
    const canApprove = superAdmin
        || auth.user?.permissions?.includes('vehicle-rental.expenses.approve');
    const changeStatus = async (expenseId: number, status: 'submit' | 'approve' | 'reject') => {
        setBusy(true);
        setError(null);
        try {
            await changeRentalExpenseStatus(agreementId, expenseId, status);
            expenses.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    };
    return (
        <>
            <ContentHeader title={`Rental expenses / ${agreement.data.agreement_number}`} description="Fuel, toll, parking, allowance, repairs, and other direct rental costs." />
            <ErrorAlert error={error ?? expenses.error} />
            <div className="space-y-5">
                {canRecord && <Panel title="Add expense">
                    <form className="grid gap-4 md:grid-cols-2 xl:grid-cols-4" onSubmit={async (event) => {
                        event.preventDefault();
                        setBusy(true);
                        setError(null);
                        try {
                            await createRentalExpense(agreementId, {
                                ...form,
                                responsible_party_id: form.financial_treatment === 'employee_reimbursable'
                                    ? employee?.id
                                    : form.financial_treatment === 'supplier_recoverable'
                                        ? supplier?.id
                                        : undefined,
                                receipt_no: form.receipt_no || undefined,
                                reference_no: form.reference_no || undefined,
                                description: form.description || undefined,
                            });
                            setEmployee(null);
                            setSupplier(null);
                            setForm((current) => ({ ...current, amount: '', receipt_no: '', reference_no: '', description: '' }));
                            expenses.reload();
                        } catch (requestError) {
                            setError(toApiError(requestError));
                        } finally {
                            setBusy(false);
                        }
                    }}>
                        <Select label="Expense type" value={form.expense_type} options={['fuel', 'toll', 'parking', 'allowance', 'repair', 'other'].map((value) => ({ value, label: value }))} onChange={(event) => setForm({ ...form, expense_type: event.target.value })} />
                        <Input label="Expense date" type="date" value={form.expense_date} onChange={(event) => setForm({ ...form, expense_date: event.target.value })} />
                        <DecimalInput label="Amount" value={form.amount} error={fieldError(error, 'amount')} onChange={(event) => setForm({ ...form, amount: event.target.value })} />
                        <Select label="Financial treatment" value={form.financial_treatment} options={[
                            { value: 'company_borne', label: 'Company borne' },
                            ...(agreement.data.direction === 'outbound' ? [{ value: 'customer_billable', label: 'Customer billable' }] : []),
                            ...(agreement.data.direction === 'inbound' ? [
                                { value: 'owner_payable', label: 'Owner / supplier payable' },
                                { value: 'supplier_recoverable', label: 'Supplier recoverable' },
                            ] : []),
                            { value: 'employee_reimbursable', label: 'Employee reimbursable' },
                        ]} onChange={(event) => {
                            setEmployee(null);
                            setSupplier(null);
                            setForm({ ...form, financial_treatment: event.target.value });
                        }} />
                        {form.financial_treatment === 'employee_reimbursable' && (
                            <GenericLookupSelect
                                label="Responsible employee"
                                value={employee}
                                onChange={setEmployee}
                                search={searchEmployee}
                                formatLabel={(row) => `${row.employee_number} ${row.display_name}`}
                                error={fieldError(error, 'responsible_party_id')}
                            />
                        )}
                        {form.financial_treatment === 'supplier_recoverable' && (
                            <GenericLookupSelect
                                label="Responsible supplier"
                                value={supplier}
                                onChange={setSupplier}
                                search={searchSupplier}
                                formatLabel={(row) => `${row.supplier_number} ${row.display_name ?? row.name}`}
                                error={fieldError(error, 'responsible_party_id')}
                            />
                        )}
                        <Input label="Receipt no." value={form.receipt_no} onChange={(event) => setForm({ ...form, receipt_no: event.target.value })} />
                        <Input label="Reference no." value={form.reference_no} onChange={(event) => setForm({ ...form, reference_no: event.target.value })} />
                        <div className="md:col-span-2"><Textarea label="Description" value={form.description} onChange={(event) => setForm({ ...form, description: event.target.value })} /></div>
                        <div className="md:col-span-2 xl:col-span-4 flex justify-end"><Button type="submit" loading={busy}>Add expense</Button></div>
                    </form>
                </Panel>}
                {expenses.loading ? <LoadingState /> : <DataTable rows={expenses.data ?? []} rowKey={(row) => row.id} columns={[
                    { key: 'type', header: 'Type', render: (row) => row.expense_type },
                    { key: 'description', header: 'Description', render: (row) => row.description ?? '-' },
                    { key: 'reference', header: 'Receipt / reference', render: (row) => `${row.receipt_no ?? '-'} / ${row.reference_no ?? '-'}` },
                    { key: 'treatment', header: 'Treatment', render: (row) => row.financial_treatment.replaceAll('_', ' ') },
                    { key: 'amount', header: 'Amount', render: (row) => row.amount },
                    { key: 'status', header: 'Status', render: (row) => <RentalStatusBadge status={row.status} /> },
                    { key: 'actions', header: '', render: (row) => <div className="flex gap-2">
                        {row.status === 'draft' && canRecord && <Button type="button" loading={busy} onClick={() => changeStatus(row.id, 'submit')}>Submit</Button>}
                        {row.status === 'submitted' && canApprove && <Button type="button" loading={busy} onClick={() => changeStatus(row.id, 'approve')}>Approve</Button>}
                        {row.status === 'submitted' && canApprove && <Button type="button" variant="danger" loading={busy} onClick={() => changeStatus(row.id, 'reject')}>Reject</Button>}
                    </div> },
                ]} />}
            </div>
        </>
    );
}
