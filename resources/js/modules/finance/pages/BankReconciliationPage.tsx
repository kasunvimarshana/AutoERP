import { useState } from 'react';
import { toApiError, type ApiError, fieldError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { formatDate } from '@/shared/utils/formatDate';
import { completeBankReconciliation, createBankReconciliation, getBankReconciliation, getFinanceLookups, listBankReconciliations, listLedgerEntries, matchBankStatementLine, unmatchBankStatementLine, type BankReconciliation, type BankReconciliationPayload, type LedgerEntry } from '../financeApi';

const initial: BankReconciliationPayload = {
    bank_account_id: null,
    statement_reference: '',
    statement_date: new Date().toISOString().slice(0, 10),
    opening_balance: '0.000000',
    closing_balance: '0.000000',
    statement_lines: [{ debit: '0.000000', credit: '0.000000' }],
};

export default function BankReconciliationPage() {
    const [form, setForm] = useState(initial);
    const [saving, setSaving] = useState(false);
    const [selected, setSelected] = useState<BankReconciliation | null>(null);
    const [ledgerRows, setLedgerRows] = useState<LedgerEntry[]>([]);
    const [lineMatches, setLineMatches] = useState<Record<number, number | ''>>({});
    const [error, setError] = useState<ApiError | null>(null);
    const lookups = useApi((signal) => getFinanceLookups(signal), []);
    const recs = useApi((signal) => listBankReconciliations({ per_page: 25 }, signal), []);
    const columns: DataColumn<BankReconciliation>[] = [
        { key: 'statement', header: 'Statement', render: (row) => <button type="button" className="font-semibold text-sky-700 hover:underline" onClick={() => void loadReconciliation(row.id)}>{row.statement_reference}</button> },
        { key: 'account', header: 'Bank account', render: (row) => row.bank_account ? `${row.bank_account.code} - ${row.bank_account.name}` : '-' },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.statement_date) },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        { key: 'matched', header: 'Matched', render: (row) => `${row.matched_count ?? 0} / ${row.unmatched_count ?? 0}` },
    ];

    async function save() {
        setSaving(true);
        setError(null);
        try {
            await createBankReconciliation(form);
            setForm(initial);
            recs.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    async function loadReconciliation(id: number) {
        setError(null);
        try {
            const reconciliation = await getBankReconciliation(id);
            setSelected(reconciliation);
            const bankAccount = reconciliation.bank_account as { id?: number } | null | undefined;
            if (bankAccount?.id) {
                const ledger = await listLedgerEntries({ account_id: bankAccount.id, per_page: 100 });
                setLedgerRows(ledger.data);
            } else {
                setLedgerRows([]);
            }
        } catch (requestError) {
            setError(toApiError(requestError));
        }
    }

    async function matchLine(lineId: number) {
        const ledgerEntryId = lineMatches[lineId];
        if (!selected || !ledgerEntryId) return;
        setSaving(true);
        setError(null);
        try {
            await matchBankStatementLine(selected.id, lineId, Number(ledgerEntryId));
            await loadReconciliation(selected.id);
            recs.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    async function unmatchLine(lineId: number) {
        if (!selected) return;
        setSaving(true);
        setError(null);
        try {
            await unmatchBankStatementLine(selected.id, lineId);
            await loadReconciliation(selected.id);
            recs.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    async function completeSelected() {
        if (!selected) return;
        setSaving(true);
        setError(null);
        try {
            await completeBankReconciliation(selected.id);
            await loadReconciliation(selected.id);
            recs.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    return <>
        <ContentHeader title="Bank reconciliation" description="Bank statement history and ledger matching." />
        <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(28rem,34rem)]">
            <Panel title="Reconciliations">
                <ErrorAlert error={recs.error} />
                {recs.loading ? <LoadingState /> : <DataTable rows={recs.data?.data ?? []} rowKey={(row) => row.id} columns={columns} />}
            </Panel>
            {selected && <Panel title="Statement matching">
                <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div className="text-sm font-semibold text-slate-700">{selected.statement_reference} / <StatusBadge status={selected.status} /></div>
                    <Button type="button" variant="secondary" loading={saving} disabled={String(selected.status).startsWith('completed')} onClick={() => void completeSelected()}>Complete</Button>
                </div>
                <DataTable rows={(selected.lines ?? []) as Array<Record<string, unknown>>} rowKey={(row) => Number(row.id)} columns={[
                    { key: 'date', header: 'Date', render: (row) => formatDate(String(row.statement_date ?? '')) },
                    { key: 'amount', header: 'Amount', render: (row) => `${row.debit ?? '0.000000'} / ${row.credit ?? '0.000000'}` },
                    { key: 'status', header: 'Status', render: (row) => <StatusBadge status={String(row.status ?? 'unmatched')} /> },
                    { key: 'match', header: 'Ledger match', render: (row) => row.matched_ledger_entry_id
                        ? <Button type="button" variant="secondary" loading={saving} onClick={() => void unmatchLine(Number(row.id))}>Unmatch</Button>
                        : <div className="flex min-w-72 gap-2">
                            <Select value={lineMatches[Number(row.id)] ?? ''} onChange={(event) => setLineMatches({ ...lineMatches, [Number(row.id)]: event.target.value ? Number(event.target.value) : '' })} options={ledgerRows.map((ledger) => ({ value: String(ledger.id), label: `${formatDate(ledger.entry_date)} / ${ledger.account?.code ?? ''} ${ledger.account?.name ?? ''} / Dr ${ledger.debit} Cr ${ledger.credit}` }))} />
                            <Button type="button" loading={saving} disabled={!lineMatches[Number(row.id)]} onClick={() => void matchLine(Number(row.id))}>Match</Button>
                        </div> },
                ]} />
            </Panel>}
            <Panel title="New statement">
                <ErrorAlert error={error} />
                <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); void save(); }}>
                    <Select label="Bank account" value={form.bank_account_id ?? ''} onChange={(event) => setForm({ ...form, bank_account_id: event.target.value ? Number(event.target.value) : null })} options={(lookups.data?.bankAccounts ?? []).map((account) => ({ value: String(account.id), label: `${account.code} - ${account.name}` }))} error={fieldError(error, 'bank_account_id')} required />
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input label="Statement reference" value={form.statement_reference} onChange={(event) => setForm({ ...form, statement_reference: event.target.value })} error={fieldError(error, 'statement_reference')} required />
                        <Input label="Statement date" type="date" value={form.statement_date} onChange={(event) => setForm({ ...form, statement_date: event.target.value })} error={fieldError(error, 'statement_date')} required />
                        <DecimalInput label="Opening balance" value={form.opening_balance} onChange={(event) => setForm({ ...form, opening_balance: event.target.value })} />
                        <DecimalInput label="Closing balance" value={form.closing_balance} onChange={(event) => setForm({ ...form, closing_balance: event.target.value })} />
                    </div>
                    <Panel title="Statement lines">
                        <div className="space-y-3">
                            {form.statement_lines.map((line, index) => <div key={index} className="grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
                                <DecimalInput label="Debit" value={line.debit} onChange={(event) => updateLine(index, { debit: event.target.value })} />
                                <DecimalInput label="Credit" value={line.credit} onChange={(event) => updateLine(index, { credit: event.target.value })} />
                                <div className="flex items-end"><Button type="button" variant="danger" disabled={form.statement_lines.length <= 1} onClick={() => removeLine(index)}>Remove</Button></div>
                            </div>)}
                        </div>
                        <div className="mt-3"><Button type="button" variant="secondary" onClick={() => setForm({ ...form, statement_lines: [...form.statement_lines, { debit: '0.000000', credit: '0.000000' }] })}>Add line</Button></div>
                    </Panel>
                    <div className="flex justify-end"><Button type="submit" loading={saving}>Create reconciliation</Button></div>
                </form>
            </Panel>
        </div>
    </>;

    function updateLine(index: number, patch: Partial<BankReconciliationPayload['statement_lines'][number]>) {
        setForm({ ...form, statement_lines: form.statement_lines.map((line, lineIndex) => lineIndex === index ? { ...line, ...patch } : line) });
    }

    function removeLine(index: number) {
        setForm({ ...form, statement_lines: form.statement_lines.filter((_, lineIndex) => lineIndex !== index) });
    }
}
