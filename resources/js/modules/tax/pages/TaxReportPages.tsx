import { useMemo, useState } from 'react';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import { getTaxReport } from '../taxApi';

const reports = [
    { value: 'summary', label: 'Tax Summary' },
    { value: 'liability', label: 'Tax Liability' },
    { value: 'receivable', label: 'Tax Receivable' },
    { value: 'vat', label: 'VAT Report' },
    { value: 'wht', label: 'WHT Report' },
    { value: 'reconciliation', label: 'Tax Reconciliation' },
] as const;

type Row = Record<string, string | number | null>;

export default function TaxReportPages() {
    const [report, setReport] = useState('summary');
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
    const [taxType, setTaxType] = useState('');
    const [taxCode, setTaxCode] = useState('');
    const result = useApi((signal) => getTaxReport(report, {
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
        tax_type: taxType || undefined,
        tax_code: taxCode || undefined,
    }, signal), [dateFrom, dateTo, report, taxCode, taxType]);

    const rows = useMemo(() => result.data?.rows ?? [], [result.data?.rows]);
    const columns = useMemo<DataColumn<Row>[]>(() => {
        const keys = Array.from(new Set(rows.flatMap((row) => Object.keys(row))));
        return keys.map((key) => ({
            key,
            header: key.replaceAll('_', ' '),
            render: (row) => row[key] ?? '-',
        }));
    }, [rows]);

    return (
        <>
            <ContentHeader title="Tax reports" description="Tax reporting from posted tax transaction data and immutable document snapshots." />
            <Panel>
                <div className="grid gap-3 lg:grid-cols-5">
                    <Select label="Report" value={report} options={reports.map((item) => ({ value: item.value, label: item.label }))} onChange={(event) => setReport(event.target.value)} />
                    <Input label="Date from" type="date" value={dateFrom} onChange={(event) => setDateFrom(event.target.value)} />
                    <Input label="Date to" type="date" value={dateTo} onChange={(event) => setDateTo(event.target.value)} />
                    <Input label="Tax type" value={taxType} placeholder="VAT, GST, WHT..." onChange={(event) => setTaxType(event.target.value)} />
                    <Input label="Tax code" value={taxCode} onChange={(event) => setTaxCode(event.target.value)} />
                </div>
            </Panel>
            <ErrorAlert error={result.error} />
            <div className="mt-4">
                {result.loading ? <LoadingState /> : <DataTable rows={rows} columns={columns} rowKey={(row) => `${row.tax_code ?? row.source_id ?? ''}-${JSON.stringify(row)}`} />}
            </div>
            {result.data?.totals && (
                <Panel title="Totals" className="mt-4">
                    <dl className="grid gap-3 md:grid-cols-3">
                        {Object.entries(result.data.totals).map(([key, value]) => (
                            <div key={key} className="rounded-lg bg-slate-50 p-3">
                                <dt className="text-xs font-semibold uppercase text-slate-500">{key.replaceAll('_', ' ')}</dt>
                                <dd className="mt-1 font-semibold text-slate-900">{value}</dd>
                            </div>
                        ))}
                    </dl>
                </Panel>
            )}
        </>
    );
}
