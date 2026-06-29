import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { EmployeeCommissionFilters } from '../components/EmployeeCommissionFilters';
import { EmployeeCommissionTable } from '../components/EmployeeCommissionTable';
import { ExportActions } from '../components/ExportActions';
import { runEmployeeCommissionReport } from '../reportingApi';
import type {
    EmployeeCommissionReportParams,
    EmployeeCommissionReportResult,
} from '../reportingTypes';

const reportKey = 'vehicle-service/employee-commissions';
const initialFilters: EmployeeCommissionReportParams = {
    page: 1,
    per_page: 25,
    group_by: 'employee',
    sort: 'job_date',
    direction: 'desc',
};

export default function EmployeeCommissionReportScreen() {
    const [filters, setFilters] = useState(initialFilters);
    const [draft, setDraft] = useState(initialFilters);
    const [result, setResult] = useState<EmployeeCommissionReportResult | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [resetToken, setResetToken] = useState(0);

    useEffect(() => {
        const controller = new AbortController();
        const load = async () => {
            setLoading(true);
            setError(null);
            try {
                const response = await runEmployeeCommissionReport(cleanParams(filters), controller.signal);
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

    const groups = useMemo(
        () => new Map((result?.groups ?? []).map((group) => [group.key, group])),
        [result?.groups],
    );
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

    const sort = (key: string) => {
        const direction = filters.sort === key && filters.direction !== 'asc' ? 'asc' : 'desc';
        setFilters((current) => ({ ...current, page: 1, sort: key, direction }));
        setDraft((current) => ({ ...current, sort: key, direction }));
    };

    return (
        <>
            <ContentHeader
                title="Employee Commission Report"
                description="Commission results with independent Payment lifecycle filters."
                actions={<LinkButton to="/reports" variant="secondary">All reports</LinkButton>}
            />
            <ErrorAlert error={error} title="Could not load employee commission report" />
            <div className="space-y-5">
                <Panel>
                    <EmployeeCommissionFilters
                        value={draft}
                        loading={loading}
                        resetToken={resetToken}
                        onChange={(patch) => setDraft((current) => ({ ...current, ...patch }))}
                        onApply={apply}
                        onReset={reset}
                    />
                </Panel>
                <div className="flex items-center justify-between gap-3">
                    <span className="text-sm text-slate-500">
                        {loading ? 'Refreshing...' : `${result?.meta?.total ?? 0} commission entries`}
                    </span>
                    <ExportActions reportKey={reportKey} params={exportParams} />
                </div>
                {loading && !result ? (
                    <LoadingState label="Loading employee commissions..." />
                ) : (
                    <EmployeeCommissionTable
                        rows={result?.data ?? []}
                        groups={groups}
                        sortKey={filters.sort}
                        direction={filters.direction}
                        onSort={sort}
                        onSelect={() => undefined}
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

function cleanParams(params: EmployeeCommissionReportParams): EmployeeCommissionReportParams {
    return Object.fromEntries(
        Object.entries(params).filter(([, value]) => value !== '' && value !== null && value !== undefined),
    ) as EmployeeCommissionReportParams;
}
