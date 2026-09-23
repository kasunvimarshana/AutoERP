import { useEffect, useMemo, useState } from 'react';
import { toApiError } from '@/shared/api/apiError';
import { lookupApi } from '@/shared/api/lookupApi';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { useDebounce } from '@/shared/hooks/useDebounce';
import type { NamedResource } from '@/shared/types/common';
import type { PaginationMeta } from '@/shared/types/pagination';

const SEARCH_DEBOUNCE_MS = 300;
const RESULTS_PER_PAGE = 20;

export function EmployeePickerPanel({ lineLabel, selectedEmployeeIds, excludeIds, onClose, onToggle }: {
    lineLabel: string;
    selectedEmployeeIds: number[];
    excludeIds: number[];
    onClose: () => void;
    onToggle: (employee: NamedResource) => void;
}) {
    const [search, setSearch] = useState('');
    const debouncedSearch = useDebounce(search.trim(), SEARCH_DEBOUNCE_MS);
    const [employees, setEmployees] = useState<NamedResource[]>([]);
    const [meta, setMeta] = useState<PaginationMeta>();
    const [loading, setLoading] = useState(false);
    const [loadingMore, setLoadingMore] = useState(false);
    const [error, setError] = useState('');
    const excluded = useMemo(() => new Set(excludeIds.map(Number)), [excludeIds]);
    const visibleEmployees = employees.filter((employee) => !excluded.has(Number(employee.id)));

    useEffect(() => {
        const controller = new AbortController();
        setLoading(true);
        setError('');
        void lookupApi.availableNonSupervisorEmployees({
            search: debouncedSearch,
            page: 1,
            perPage: RESULTS_PER_PAGE,
            signal: controller.signal,
        }).then((result) => {
            if (controller.signal.aborted) return;
            setEmployees(result.data);
            setMeta(result.meta);
        }).catch((requestError: unknown) => {
            if (!controller.signal.aborted) setError(toApiError(requestError).message);
        }).finally(() => {
            if (!controller.signal.aborted) setLoading(false);
        });

        return () => controller.abort();
    }, [debouncedSearch]);

    const loadMore = async () => {
        if (!meta || loadingMore || meta.current_page >= meta.last_page) return;
        setLoadingMore(true);
        setError('');
        try {
            const result = await lookupApi.availableNonSupervisorEmployees({
                search: debouncedSearch,
                page: meta.current_page + 1,
                perPage: RESULTS_PER_PAGE,
                signal: new AbortController().signal,
            });
            setEmployees((current) => dedupeById([...current, ...result.data]));
            setMeta(result.meta);
        } catch (requestError) {
            setError(toApiError(requestError).message);
        } finally {
            setLoadingMore(false);
        }
    };

    return (
        <aside aria-label="Employee contacts" className="flex max-h-[calc(100vh-8rem)] min-h-[32rem] flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg lg:sticky lg:top-4">
            <header className="flex items-start justify-between gap-3 border-b border-slate-200 px-4 py-4">
                <div className="min-w-0">
                    <h3 className="font-semibold text-slate-900">Employees</h3>
                    <p className="truncate text-xs text-slate-500">Select for {lineLabel}</p>
                </div>
                <button type="button" className="rounded-md px-2 py-1 text-sm font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-900" aria-label="Close employee panel" onClick={onClose}>Close</button>
            </header>

            <div className="border-b border-slate-100 p-3">
                <Input label="Search" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Code or employee name" autoFocus />
            </div>

            <div className="min-h-0 flex-1 overflow-y-auto">
                {loading && <PickerMessage>Loading available employees...</PickerMessage>}
                {!loading && error && <PickerMessage tone="error">{error}</PickerMessage>}
                {!loading && !error && visibleEmployees.length === 0 && <PickerMessage>No matching available employees.</PickerMessage>}
                {!loading && visibleEmployees.map((employee) => {
                    const selected = selectedEmployeeIds.includes(Number(employee.id));
                    return (
                        <button
                            key={employee.id}
                            type="button"
                            className={`flex w-full items-center gap-3 border-b border-slate-100 px-4 py-3 text-left transition ${selected ? 'bg-sky-50' : 'hover:bg-slate-50'}`}
                            aria-pressed={selected}
                            onClick={() => onToggle(employee)}
                        >
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-sky-100 text-xs font-semibold text-sky-800">{initials(employee.name ?? employee.code ?? '')}</span>
                            <span className="min-w-0 flex-1">
                                <strong className="block truncate text-sm font-medium text-slate-900">{employee.name}</strong>
                                <span className="block truncate text-xs text-slate-500">{employee.code}</span>
                            </span>
                            <span className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-base font-semibold text-white ${selected ? 'bg-sky-600' : 'bg-slate-300'}`} aria-hidden="true">{selected ? '✓' : '+'}</span>
                        </button>
                    );
                })}
            </div>

            {meta && meta.current_page < meta.last_page && (
                <div className="border-t border-slate-200 p-3">
                    <Button type="button" variant="secondary" className="w-full" loading={loadingMore} onClick={() => void loadMore()}>Load more employees</Button>
                </div>
            )}
        </aside>
    );
}

function PickerMessage({ children, tone = 'muted' }: { children: string; tone?: 'muted' | 'error' }) {
    return <p className={`px-4 py-6 text-center text-sm ${tone === 'error' ? 'text-rose-600' : 'text-slate-500'}`}>{children}</p>;
}

function initials(value: string): string {
    return value.trim().split(/\s+/).slice(0, 2).map((part) => part[0]?.toUpperCase() ?? '').join('') || 'E';
}

function dedupeById(resources: NamedResource[]): NamedResource[] {
    const seen = new Set<string>();
    return resources.filter((resource) => {
        const id = String(resource.id);
        if (seen.has(id)) return false;
        seen.add(id);
        return true;
    });
}
