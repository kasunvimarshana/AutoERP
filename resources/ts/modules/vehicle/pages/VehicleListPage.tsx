import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Input } from '../../../shared/components/ui/Input';
import { EmptyState, FilterCard, LoadingState, PageHeader, Pagination, PrimaryLink, StatusBadge, TableCard } from '../../../shared/components/erp/ErpUi';
import { vehicleApi } from '../services/vehicleApi';
import type { VehicleListItem, VehiclePage, VehicleStatus } from '../types/vehicle.types';

export function VehicleListPage() {
    const [pageData, setPageData] = useState<VehiclePage | null>(null);
    const [searchInput, setSearchInput] = useState('');
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState<VehicleStatus | ''>('');
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        const timer = window.setTimeout(() => {
            setPage(1);
            setSearch(searchInput.trim());
        }, 350);

        return () => window.clearTimeout(timer);
    }, [searchInput]);

    useEffect(() => {
        let active = true;
        setLoading(true);
        setError('');

        void vehicleApi
            .list({ page, perPage: 20, search: search || undefined, status: status || undefined })
            .then((response) => {
                if (active) setPageData(response);
            })
            .catch((requestError) => {
                if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load vehicles.');
            })
            .finally(() => {
                if (active) setLoading(false);
            });

        return () => {
            active = false;
        };
    }, [page, search, status]);

    async function remove(vehicle: VehicleListItem) {
        if (!window.confirm(`Delete ${vehicle.registrationNumber}? This action soft-deletes the record.`)) return;

        try {
            await vehicleApi.remove(vehicle.id);
            setPageData((current) =>
                current
                    ? {
                          ...current,
                          items: current.items.filter((candidate) => candidate.id !== vehicle.id),
                          meta: { ...current.meta, total: Math.max(0, current.meta.total - 1) },
                      }
                    : current,
            );
        } catch (requestError) {
            setError(requestError instanceof Error ? requestError.message : 'Unable to delete this vehicle.');
        }
    }

    return (
        <div className="space-y-5">
            <PageHeader actions={<PrimaryLink to="/vehicles/new">Create vehicle</PrimaryLink>} eyebrow="Master data" subtitle="Manage registrations, technical details, ownership, and vehicle availability." title="Vehicles" />

            <FilterCard className="sm:grid-cols-[1fr_180px]">
                <Input placeholder="Search by code, registration, chassis, engine, make, or model" value={searchInput} onChange={(event) => setSearchInput(event.target.value)} />
                <select className="erp-select" value={status} onChange={(event) => { setPage(1); setStatus(event.target.value as VehicleStatus | ''); }}>
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </FilterCard>

            {error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div> : null}

            <TableCard>
                {loading ? (
                    <LoadingState label="Loading vehicles" />
                ) : pageData?.items.length ? (
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[820px] text-left text-sm">
                            <thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                <tr><th className="px-4 py-3">Code</th><th className="px-4 py-3">Registration</th><th className="px-4 py-3">Vehicle</th><th className="px-4 py-3">Type</th><th className="px-4 py-3">Status</th><th className="px-4 py-3 text-right">Actions</th></tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {pageData.items.map((vehicle) => (
                                    <tr className="hover:bg-slate-50/70" key={vehicle.id}>
                                        <td className="px-4 py-4 font-semibold text-slate-900">{vehicle.vehicleCode}</td>
                                        <td className="px-4 py-4"><p className="font-semibold text-slate-900">{vehicle.registrationNumber}</p><p className="text-xs text-slate-500">{vehicle.chassisNumber || 'No chassis number'}</p></td>
                                        <td className="px-4 py-4 text-slate-700"><p>{[vehicle.make, vehicle.model].filter(Boolean).join(' ') || 'Not specified'}</p><p className="text-xs text-slate-500">{vehicle.year || 'Year not set'}{vehicle.color ? ` · ${vehicle.color}` : ''}</p></td>
                                        <td className="px-4 py-4 text-slate-600">{vehicle.vehicleType || 'Not specified'}</td>
                                        <td className="px-4 py-4"><StatusBadge value={vehicle.status} /></td>
                                        <td className="px-4 py-4"><div className="flex justify-end gap-2"><Link className="rounded-md px-2 py-1.5 font-semibold text-blue-700 hover:bg-blue-50" to={`/vehicles/${vehicle.id}`}>View</Link><Link className="rounded-md px-2 py-1.5 font-semibold text-slate-700 hover:bg-slate-100" to={`/vehicles/${vehicle.id}/edit`}>Edit</Link><button className="rounded-md px-2 py-1.5 font-semibold text-red-600 hover:bg-red-50" onClick={() => void remove(vehicle)} type="button">Delete</button></div></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <EmptyState action={<PrimaryLink to="/vehicles/new">Create vehicle</PrimaryLink>} title="No vehicles found" />
                )}
            </TableCard>

            {pageData ? <Pagination current={pageData.meta.currentPage} last={pageData.meta.lastPage} loading={loading} onPage={setPage} total={pageData.meta.total} /> : null}
        </div>
    );
}
