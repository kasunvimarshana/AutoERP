import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { formatBusinessDateTime } from '@/shared/utils/businessDate';
import type { PaginationMeta } from '@/shared/types/pagination';
import {
    listReservations,
    transitionReservation,
    convertReservation,
    ReservationStatus,
    RESERVATION_PERMISSION,
    type RentalReservation,
} from './reservationApi';
import { ReservationEditor } from './ReservationEditor';

export default function ReservationsPage() {
    const auth = useAuth();
    const canManage = hasPermission(auth, RESERVATION_PERMISSION.manage);

    const initialVehicleId = (() => {
        if (typeof window === 'undefined') return undefined;
        const id = new URLSearchParams(window.location.search).get('vehicle_id');
        return id ? Number(id) : undefined;
    })();

    const [rows, setRows] = useState<RentalReservation[]>([]);
    const [meta, setMeta] = useState<PaginationMeta>();
    const [page, setPage] = useState(1);
    const [revision, setRevision] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);

    const [editing, setEditing] = useState<RentalReservation | 'new' | null>(initialVehicleId ? 'new' : null);
    const [selected, setSelected] = useState<RentalReservation | null>(null);
    const [actionLoading, setActionLoading] = useState(false);
    const [search, setSearch] = useState('');
    const [query, setQuery] = useState('');

    useEffect(() => {
        const controller = new AbortController();
        const filters = query ? { search: query } : undefined;
        listReservations(page, controller.signal, filters)
            .then(result => {
                if (!controller.signal.aborted) {
                    setRows(result.data);
                    setMeta(result.meta);
                    setError(null);
                }
            })
            .catch(failure => {
                if (!controller.signal.aborted) {
                    setRows([]);
                    setMeta(undefined);
                    setError(toApiError(failure));
                }
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });
        return () => controller.abort();
    }, [page, revision, query]);

    function handleCloseEditor() {
        setEditing(null);
        if (typeof window !== 'undefined' && window.location.search.includes('vehicle_id')) {
            const url = new URL(window.location.href);
            url.searchParams.delete('vehicle_id');
            window.history.replaceState({}, '', url.toString());
        }
    }

    function reload() {
        setSelected(null);
        handleCloseEditor();
        setLoading(true);
        setRevision(v => v + 1);
    }

    async function handleTransition(reservation: RentalReservation, next: ReservationStatus) {
        setActionLoading(true);
        setError(null);
        try {
            await transitionReservation(reservation.id, reservation.row_version, next);
            reload();
        } catch (failure) {
            setError(toApiError(failure));
        } finally {
            setActionLoading(false);
        }
    }

    async function handleConvert(reservation: RentalReservation) {
        setActionLoading(true);
        setError(null);
        try {
            await convertReservation(reservation.id, reservation.row_version, {});
            reload();
        } catch (failure) {
            setError(toApiError(failure));
        } finally {
            setActionLoading(false);
        }
    }

    return (
        <div>
            <ContentHeader
                title="Rental Reservations"
                description="Manage customer advance bookings, vehicle holds, and convert confirmed reservations to rental agreements."
                actions={
                    <>
                        <Button variant="secondary" onClick={reload} disabled={loading || actionLoading || editing !== null}>
                            Reload
                        </Button>
                        {canManage && (
                            <Button onClick={() => { setEditing('new'); setSelected(null); }} disabled={loading || actionLoading || editing !== null}>
                                New Reservation
                            </Button>
                        )}
                    </>
                }
            />

            <ErrorAlert error={error} inline />

            {editing !== null && (
                <ReservationEditor
                    key={editing === 'new' ? 'new' : editing.id}
                    reservation={editing === 'new' ? undefined : editing}
                    initialVehicleId={editing === 'new' ? initialVehicleId : undefined}
                    onSaved={reload}
                    onCancel={handleCloseEditor}
                />
            )}

            <form
                onSubmit={event => {
                    event.preventDefault();
                    setPage(1);
                    setQuery(search.trim());
                }}
                className="mt-4 flex flex-wrap items-center gap-2"
            >
                <div className="w-80">
                    <Input
                        name="search"
                        placeholder="Search by reservation, customer, or vehicle…"
                        value={search}
                        onChange={event => setSearch(event.target.value)}
                    />
                </div>
                <Button type="submit" variant="secondary" disabled={loading}>
                    Search
                </Button>
                {query && (
                    <Button
                        type="button"
                        variant="ghost"
                        onClick={() => {
                            setSearch('');
                            setQuery('');
                            setPage(1);
                        }}
                    >
                        Clear
                    </Button>
                )}
            </form>

            {loading ? (
                <LoadingState label="Loading reservations…" />
            ) : error ? null : (
                <div className="mt-5 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table className="w-full text-left text-sm">
                        <caption className="sr-only">Rental reservations list</caption>
                        <thead>
                            <tr className="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                {['Reservation Number', 'Customer', 'Vehicle / Category', 'Rental Period', 'Estimated Amount', 'Status', 'Actions'].map(label => (
                                    <th className="px-4 py-3" key={label}>{label}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {rows.map(row => (
                                <tr key={row.id} className="transition-colors hover:bg-blue-50/40">
                                    <td className="px-4 py-3 font-medium text-slate-900">{row.reservation_number}</td>
                                    <td className="px-4 py-3 text-slate-700">{row.customer.name}</td>
                                    <td className="px-4 py-3 text-slate-600">
                                        {row.requested_vehicle?.label ?? row.requested_vehicle_category?.name ?? 'Any vehicle'}
                                    </td>
                                    <td className="px-4 py-3 text-slate-600">
                                        {formatBusinessDateTime(row.requested_start_at)} — {formatBusinessDateTime(row.requested_end_at)}
                                    </td>
                                    <td className="px-4 py-3 text-slate-900 font-medium">
                                        <MoneyDisplay value={row.estimated_amount} currency={row.currency.code} />
                                    </td>
                                    <td className="px-4 py-3">
                                        <StatusBadge status={row.status} />
                                    </td>
                                    <td className="px-4 py-3">
                                        <Button
                                            variant="secondary"
                                            disabled={actionLoading || editing !== null}
                                            onClick={() => setSelected(selected?.id === row.id ? null : row)}
                                        >
                                            {selected?.id === row.id ? 'Close' : 'Details'}
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {rows.length === 0 && <p className="p-5 text-slate-500">No reservations recorded.</p>}
                </div>
            )}

            <Pagination meta={meta} disabled={loading || actionLoading} onPageChange={value => { setLoading(true); setPage(value); setSelected(null); }} />

            {selected && (
                <section className="mt-5 space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm" aria-label="Reservation details">
                    <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
                        <div>
                            <h2 className="text-xl font-semibold text-slate-900">{selected.reservation_number} · {selected.customer.name}</h2>
                            <p className="text-xs text-slate-500">Mode: {selected.rental_mode === 'self_drive' ? 'Self Drive' : 'With Driver'} · Cycle: {selected.billing_cycle === 'daily' ? 'Daily' : 'Monthly'}</p>
                        </div>
                        <div className="flex items-center gap-2">
                            <StatusBadge status={selected.status} />
                            <Button variant="secondary" onClick={() => setSelected(null)}>Close</Button>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3 text-sm">
                        <div className="rounded-lg bg-slate-50 p-3">
                            <span className="block text-xs font-semibold uppercase text-slate-500">Vehicle / Type</span>
                            <span className="font-medium text-slate-900">{selected.requested_vehicle?.label ?? selected.requested_vehicle_category?.name ?? 'Not specified'}</span>
                        </div>
                        <div className="rounded-lg bg-slate-50 p-3">
                            <span className="block text-xs font-semibold uppercase text-slate-500">Estimated Total</span>
                            <span className="font-medium text-slate-900"><MoneyDisplay value={selected.estimated_amount} currency={selected.currency.code} /></span>
                        </div>
                        <div className="rounded-lg bg-slate-50 p-3">
                            <span className="block text-xs font-semibold uppercase text-slate-500">Estimated Deposit</span>
                            <span className="font-medium text-slate-900"><MoneyDisplay value={selected.estimated_deposit_amount} currency={selected.currency.code} /></span>
                        </div>
                    </div>

                    {selected.remarks && (
                        <p className="text-sm text-slate-700"><span className="font-semibold text-slate-900">Remarks:</span> {selected.remarks}</p>
                    )}

                    {selected.converted_agreement && (
                        <p className="text-sm font-medium">
                            <Link
                                to={`/vehicle-rental/customer/agreements?search=${encodeURIComponent(selected.converted_agreement.reference)}`}
                                className="text-blue-600 hover:text-blue-800 hover:underline"
                            >
                                Converted to Customer Agreement: {selected.converted_agreement.reference}
                            </Link>
                        </p>
                    )}

                    {canManage && (
                        <div className="flex flex-wrap gap-2 border-t border-slate-200 pt-4">
                            {selected.status === ReservationStatus.Draft && (
                                <>
                                    <Button variant="secondary" onClick={() => { setEditing(selected); setSelected(null); }}>Edit Draft</Button>
                                    <Button onClick={() => handleTransition(selected, ReservationStatus.Pending)} loading={actionLoading}>Submit for Approval</Button>
                                    <Button variant="primary" onClick={() => handleTransition(selected, ReservationStatus.Confirmed)} loading={actionLoading}>Confirm Booking</Button>
                                    <Button variant="danger" onClick={() => handleTransition(selected, ReservationStatus.Cancelled)} loading={actionLoading}>Cancel</Button>
                                </>
                            )}
                            {selected.status === ReservationStatus.Pending && (
                                <>
                                    <Button variant="primary" onClick={() => handleTransition(selected, ReservationStatus.Confirmed)} loading={actionLoading}>Confirm Booking</Button>
                                    <Button variant="danger" onClick={() => handleTransition(selected, ReservationStatus.Cancelled)} loading={actionLoading}>Cancel</Button>
                                </>
                            )}
                            {selected.status === ReservationStatus.Confirmed && (
                                <>
                                    <Button variant="primary" onClick={() => handleConvert(selected)} loading={actionLoading}>Convert to Agreement</Button>
                                    <Button variant="danger" onClick={() => handleTransition(selected, ReservationStatus.Cancelled)} loading={actionLoading}>Cancel Booking</Button>
                                </>
                            )}
                        </div>
                    )}
                </section>
            )}
        </div>
    );
}
