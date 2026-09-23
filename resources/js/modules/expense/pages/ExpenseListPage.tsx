import { useState } from 'react';
import { useLocation } from 'react-router-dom';
import { useAuth } from '@/modules/auth/AuthProvider';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Pagination } from '@/shared/components/Pagination';
import { ReversalDialog, type ReversalFacts } from '@/shared/components/ReversalDialog';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import {
    listExpenses,
    listExpenseTypes,
    reverseExpense,
    type Expense,
} from '../expenseApi';
import { expensePermissions, hasExpensePermission } from '../expensePermissions';

export default function ExpenseListPage() {
    const auth = useAuth();
    const location = useLocation();
    const [search, setSearch] = useState('');
    const [expenseTypeId, setExpenseTypeId] = useState('');
    const [status, setStatus] = useState('');
    const [page, setPage] = useState(1);
    const [reverseTarget, setReverseTarget] = useState<Expense | null>(null);
    const [reversing, setReversing] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const debounced = useDebounce(search);
    const types = useApi((signal) => listExpenseTypes({ per_page: 100 }, signal), []);
    const result = useApi(
        (signal) => listExpenses({
            search: debounced || undefined,
            expense_type_id: expenseTypeId ? Number(expenseTypeId) : undefined,
            status: status || undefined,
            page,
            per_page: 25,
        }, signal),
        [debounced, expenseTypeId, status, page],
    );
    const canCreate = hasExpensePermission(auth, expensePermissions.create);
    const canReverse = hasExpensePermission(auth, expensePermissions.reverse);

    async function reverse(facts: ReversalFacts) {
        if (!reverseTarget) return;
        setReversing(true);
        setActionError(null);
        try {
            await reverseExpense(reverseTarget.id, reverseTarget.row_version, facts.reversal_date, facts.reason);
            setReverseTarget(null);
            result.reload();
        } catch (requestError) {
            setActionError(toApiError(requestError));
        } finally {
            setReversing(false);
        }
    }

    const columns: DataColumn<Expense>[] = [
        { key: 'number', header: 'Expense', render: (row) => <span className="font-semibold text-slate-900">{row.expense_number}</span> },
        { key: 'date', header: 'Date', render: (row) => row.expense_date },
        { key: 'type', header: 'Type', render: (row) => row.expense_type.name },
        { key: 'payment', header: 'Paid through', render: (row) => row.payment_method.name },
        { key: 'amount', header: 'Amount', render: (row) => <div className="text-right"><MoneyDisplay value={row.amount} currency={row.currency?.code} /></div> },
        { key: 'reference', header: 'Reference', render: (row) => row.reference_number || '-' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        {
            key: 'actions',
            header: '',
            render: (row) => row.status === 'posted' && canReverse
                ? <Button variant="danger" onClick={() => { setActionError(null); setReverseTarget(row); }}>Reverse</Button>
                : null,
        },
    ];

    return (
        <>
            <ContentHeader
                title="Expenses"
                description="Posted branch expenses flow to the Cash/Bank ledger and reduce net profit automatically."
                actions={canCreate ? <LinkButton to="/expenses/create">Add expense</LinkButton> : undefined}
            />
            <SuccessAlert message={(location.state as { message?: string } | null)?.message ?? null} />
            <div className="mb-4 grid gap-3 md:grid-cols-3">
                <Input type="search" placeholder="Search expense, type, or reference" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                <Select
                    value={expenseTypeId}
                    onChange={(event) => { setExpenseTypeId(event.target.value); setPage(1); }}
                    options={(types.data?.data ?? []).map((row) => ({ value: row.id, label: row.name }))}
                    placeholder="All expense types"
                />
                <Select
                    value={status}
                    onChange={(event) => { setStatus(event.target.value); setPage(1); }}
                    options={[{ value: 'posted', label: 'Posted' }, { value: 'reversed', label: 'Reversed' }]}
                    placeholder="All statuses"
                />
            </div>
            <ErrorAlert error={actionError ?? result.error ?? types.error} />
            {result.loading
                ? <LoadingState />
                : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} emptyMessage="No expenses found for this branch." />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
            <ReversalDialog
                open={reverseTarget !== null}
                title={reverseTarget ? `Reverse ${reverseTarget.expense_number}` : 'Reverse expense'}
                defaultDate={businessDateInputValue()}
                loading={reversing}
                onCancel={() => !reversing && setReverseTarget(null)}
                onConfirm={reverse}
            />
        </>
    );
}
