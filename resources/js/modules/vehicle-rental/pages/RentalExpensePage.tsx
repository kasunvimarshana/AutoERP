import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable } from '@/shared/components/DataTable';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { RentalStatusBadge } from '../components/RentalStatusBadge';
import { changeRentalExpenseStatus, createRentalExpense, getRentalAgreement, listRentalExpenses } from '../vehicleRentalApi';

const today = () => new Date().toISOString().slice(0, 10);

export default function RentalExpensePage() {
    const agreementId = Number(useParams().id);
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
    if (agreement.loading) return <LoadingState />;
    if (!agreement.data) return <ErrorAlert error={agreement.error} />;
    const changeStatus = async (expenseId: number, status: 'approve' | 'reject') => {
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
                <Panel title="Add expense">
                    <form className="grid gap-4 md:grid-cols-2 xl:grid-cols-4" onSubmit={async (event) => {
                        event.preventDefault();
                        setBusy(true);
                        setError(null);
                        try {
                            await createRentalExpense(agreementId, {
                                ...form,
                                receipt_no: form.receipt_no || undefined,
                                reference_no: form.reference_no || undefined,
                                description: form.description || undefined,
                            });
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
                        ]} onChange={(event) => setForm({ ...form, financial_treatment: event.target.value })} />
                        <Input label="Receipt no." value={form.receipt_no} onChange={(event) => setForm({ ...form, receipt_no: event.target.value })} />
                        <Input label="Reference no." value={form.reference_no} onChange={(event) => setForm({ ...form, reference_no: event.target.value })} />
                        <div className="md:col-span-2"><Textarea label="Description" value={form.description} onChange={(event) => setForm({ ...form, description: event.target.value })} /></div>
                        <div className="md:col-span-2 xl:col-span-4 flex justify-end"><Button type="submit" loading={busy}>Add expense</Button></div>
                    </form>
                </Panel>
                {expenses.loading ? <LoadingState /> : <DataTable rows={expenses.data ?? []} rowKey={(row) => row.id} columns={[
                    { key: 'type', header: 'Type', render: (row) => row.expense_type },
                    { key: 'description', header: 'Description', render: (row) => row.description ?? '-' },
                    { key: 'reference', header: 'Receipt / reference', render: (row) => `${row.receipt_no ?? '-'} / ${row.reference_no ?? '-'}` },
                    { key: 'treatment', header: 'Treatment', render: (row) => row.financial_treatment.replaceAll('_', ' ') },
                    { key: 'amount', header: 'Amount', render: (row) => row.amount },
                    { key: 'status', header: 'Status', render: (row) => <RentalStatusBadge status={row.status} /> },
                    { key: 'actions', header: '', render: (row) => row.status === 'draft' ? <div className="flex gap-2"><Button type="button" loading={busy} onClick={() => changeStatus(row.id, 'approve')}>Approve</Button><Button type="button" variant="danger" loading={busy} onClick={() => changeStatus(row.id, 'reject')}>Reject</Button></div> : null },
                ]} />}
            </div>
        </>
    );
}
