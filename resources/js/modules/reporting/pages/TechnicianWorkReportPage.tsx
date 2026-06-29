import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { formatMoney } from '@/shared/utils/formatMoney';
import { formatQuantity } from '@/shared/utils/formatQuantity';
import { ExportActions } from '../components/ExportActions';
import { ServiceAssignmentFilters } from '../components/ServiceAssignmentFilters';
import { ServiceAssignmentTable } from '../components/ServiceAssignmentTable';
import { runTechnicianWorkReport } from '../reportingApi';
import type {
    TechnicianWorkReportParams,
    TechnicianWorkReportResult,
} from '../reportingTypes';

const reportKey = 'vehicle-service/technician-work';
const initialFilters: TechnicianWorkReportParams = {
    page: 1,
    per_page: 25,
    sort: 'job_date',
    direction: 'desc',
};

export default function TechnicianWorkReportPage() {
    const [filters, setFilters] = useState(initialFilters);
    const [draft, setDraft] = useState(initialFilters);
    const [result, setResult] = useState<TechnicianWorkReportResult | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [resetToken, setResetToken] = useState(0);

    useEffect(() => {
        const controller = new AbortController();
        const load = async () => {
            setLoading(true);
            setError(null);
            try {
                const response = await runTechnicianWorkReport(cleanParams(filters), controller.signal);
                if (!controller.signal.aborted) setResult(response);
            } catch (requestError) {
                if (!controller.signal.aborted) setError(toApiError(requestError));
            } finally {
                if (!controller.signal.aborted) setLoading(false);
            }
        };
        void load();
        return () => controller.abort();
    }, [filters]);

    const exportParams = useMemo(() => cleanParams(filters), [filters]);

    const apply = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        setFilters({ ...draft, page: 1 });
    };

    const reset = () => {
        setDraft(initialFilters);
        setFilters(initialFilters);
        setResetToken((value) => value + 1);
    };

    const sort = (column: string) => {
        const direction = filters.sort === column && filters.direction !== 'asc' ? 'asc' : 'desc';
        setFilters((current) => ({ ...current, page: 1, sort: column, direction }));
        setDraft((current) => ({ ...current, sort: column, direction }));
    };

    return (
        <>
            <ContentHeader
                title="Technician Work"
                description="Vehicle service assignments with independent invoice and Payment lifecycle states."
                actions={<LinkButton to="/reports" variant="secondary">All reports</LinkButton>}
            />
            <ErrorAlert error={error} title="Could not load technician work report" />
            <div className="space-y-5">
                <Panel>
                    <ServiceAssignmentFilters
                        value={draft}
                        loading={loading}
                        resetToken={resetToken}
                        onChange={(patch) => setDraft((current) => ({ ...current, ...patch }))}
                        onApply={apply}
                        onReset={reset}
                    />
                </Panel>
                {result && <Summary summary={result.summary} />}
                <div className="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                    <span className="text-sm text-slate-500">
                        {loading ? 'Refreshing...' : `${result?.meta?.total ?? 0} assignments`}
                    </span>
                    <ExportActions reportKey={reportKey} params={exportParams} />
                </div>
                {loading && !result ? (
                    <LoadingState label="Loading technician work..." />
                ) : (
                    <ServiceAssignmentTable
                        rows={result?.data ?? []}
                        sortKey={filters.sort}
                        direction={filters.direction}
                        onSort={sort}
                    />
                )}
                <Pagination
                    meta={result?.meta}
                    onPageChange={(page) => setFilters((current) => ({ ...current, page }))}
                />
            </div>
        </>
    );
}

function Summary({ summary }: { summary: TechnicianWorkReportResult['summary'] }) {
    const cards = [
        ['Assigned hours', formatQuantity(summary.total_assigned_hours)],
        ['Labour amount', formatMoney(summary.total_labour_amount)],
        ['Technician commission', formatMoney(summary.total_technician_commission)],
        ['Supervisor commission', formatMoney(summary.total_supervisor_commission)],
        ['Payable commission', formatMoney(summary.total_payable_commission)],
    ];

    return (
        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            {cards.map(([label, value]) => (
                <div key={label} className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div className="text-xs font-semibold uppercase text-slate-500">{label}</div>
                    <div className="mt-2 text-lg font-bold text-slate-900">{value}</div>
                </div>
            ))}
        </div>
    );
}

function cleanParams(params: TechnicianWorkReportParams): TechnicianWorkReportParams {
    return Object.fromEntries(
        Object.entries(params).filter(([, value]) => value !== '' && value !== null && value !== undefined),
    ) as TechnicianWorkReportParams;
}
