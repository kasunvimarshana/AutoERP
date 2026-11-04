import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { formatBusinessDateTime } from '@/shared/utils/businessDate';
import { QuantityDisplay } from '@/shared/components/QuantityDisplay';
import { fetchRentalDashboard, type RentalDashboardSummary } from './rentalDashboardApi';
import { RESERVATION_REGISTER_PATH } from './reservationApi';
import { FINANCE_REGISTER_PATH } from './vehicleFinanceApi';
import { EXPENSE_REGISTER_PATH } from './rentalExpenseApi';
import { USE_REGISTER_PATH } from './vehicleUse';
import { CHART_REGISTER_PATH } from './runningCharts';
import { INCIDENT_REGISTER_PATH } from './incidentEvidence';
import { AgreementKind, agreementPath } from './agreements';

export default function RentalDashboardPage() {
    const [data, setData] = useState<RentalDashboardSummary | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        const controller = new AbortController();
        fetchRentalDashboard(controller.signal)
            .then(res => {
                if (!controller.signal.aborted) {
                    setData(res);
                    setError(null);
                }
            })
            .catch(failure => {
                if (!controller.signal.aborted) {
                    setData(null);
                    setError(toApiError(failure));
                }
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });
        return () => controller.abort();
    }, []);

    function reload() {
        setLoading(true);
        fetchRentalDashboard()
            .then(res => { setData(res); setError(null); })
            .catch(failure => { setData(null); setError(toApiError(failure)); })
            .finally(() => setLoading(false));
    }

    const m = data?.metrics;

    return (
        <div className="space-y-6">
            <ContentHeader
                title="Vehicle Rental Operations Dashboard"
                description="Live operational overview of rental agreements, customer reservations, active fleet allocations, running charts, and vehicle finances."
                actions={<Button variant="secondary" onClick={reload} disabled={loading}>Reload</Button>}
            />

            <ErrorAlert error={error} inline />

            {loading ? (
                <LoadingState label="Loading rental dashboard…" />
            ) : m ? (
                <>
                    {/* KPI Summary Cards */}
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Customer Rentals</span>
                            <div className="mt-2 flex items-baseline justify-between">
                                <span className="text-2xl font-bold text-slate-900">{m.active_customer_agreements}</span>
                                <Link to={agreementPath(AgreementKind.Customer)} className="text-xs font-medium text-blue-600 hover:underline">View</Link>
                            </div>
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500">Owner Supply Agreements</span>
                            <div className="mt-2 flex items-baseline justify-between">
                                <span className="text-2xl font-bold text-slate-900">{m.active_owner_agreements}</span>
                                <Link to={agreementPath(AgreementKind.Owner)} className="text-xs font-medium text-blue-600 hover:underline">View</Link>
                            </div>
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500">Vehicles In Custody</span>
                            <div className="mt-2 flex items-baseline justify-between">
                                <span className="text-2xl font-bold text-emerald-600">{m.vehicles_in_custody}</span>
                                <Link to={USE_REGISTER_PATH} className="text-xs font-medium text-blue-600 hover:underline">Register</Link>
                            </div>
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500">Planned Deployments</span>
                            <div className="mt-2 flex items-baseline justify-between">
                                <span className="text-2xl font-bold text-amber-600">{m.planned_vehicle_uses}</span>
                                <Link to={USE_REGISTER_PATH} className="text-xs font-medium text-blue-600 hover:underline">View</Link>
                            </div>
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500">Pending Reservations</span>
                            <div className="mt-2 flex items-baseline justify-between">
                                <span className="text-2xl font-bold text-blue-600">{m.pending_reservations}</span>
                                <Link to={RESERVATION_REGISTER_PATH} className="text-xs font-medium text-blue-600 hover:underline">Bookings</Link>
                            </div>
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500">Due Finance Installments</span>
                            <div className="mt-2 flex items-baseline justify-between">
                                <span className="text-2xl font-bold text-rose-600">{m.due_finance_installments}</span>
                                <Link to={FINANCE_REGISTER_PATH} className="text-xs font-medium text-blue-600 hover:underline">Finance</Link>
                            </div>
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500">Pending Expenses</span>
                            <div className="mt-2 flex items-baseline justify-between">
                                <span className="text-2xl font-bold text-amber-600">{m.pending_expenses}</span>
                                <Link to={EXPENSE_REGISTER_PATH} className="text-xs font-medium text-blue-600 hover:underline">Expenses</Link>
                            </div>
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Incidents</span>
                            <div className="mt-2 flex items-baseline justify-between">
                                <span className="text-2xl font-bold text-rose-600">{m.active_vehicle_incidents}</span>
                                <Link to={INCIDENT_REGISTER_PATH} className="text-xs font-medium text-blue-600 hover:underline">Incidents</Link>
                            </div>
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                            <span className="text-xs font-semibold uppercase tracking-wider text-slate-500">Fleet Availability</span>
                            <div className="mt-2 flex items-baseline justify-between">
                                <span className="text-2xl font-bold text-slate-900">{m.available_fleet} <span className="text-sm font-normal text-slate-500">/ {m.total_fleet}</span></span>
                                <span className="text-xs text-slate-500">Vehicles ready</span>
                            </div>
                        </div>
                    </div>

                    {/* Operational Navigation Shortcuts */}
                    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-500">Operations & Workflow Hub</h3>
                        <div className="mt-3 flex flex-wrap gap-2">
                            <LinkButton to="/vehicle-rental/vehicles/available" variant="secondary">Check Availability</LinkButton>
                            <LinkButton to={RESERVATION_REGISTER_PATH} variant="secondary">Advance Reservations</LinkButton>
                            <LinkButton to={agreementPath(AgreementKind.Customer)} variant="secondary">Customer Agreements</LinkButton>
                            <LinkButton to={agreementPath(AgreementKind.Owner)} variant="secondary">Owner Agreements</LinkButton>
                            <LinkButton to={USE_REGISTER_PATH} variant="secondary">Vehicle Use & Custody</LinkButton>
                            <LinkButton to={CHART_REGISTER_PATH} variant="secondary">Running Charts</LinkButton>
                            <LinkButton to="/vehicle-rental/running-chart/report" variant="secondary">Print Running Chart</LinkButton>
                            <LinkButton to={EXPENSE_REGISTER_PATH} variant="secondary">Fleet Expenses</LinkButton>
                            <LinkButton to={FINANCE_REGISTER_PATH} variant="secondary">Vehicle Finance</LinkButton>
                            <LinkButton to={INCIDENT_REGISTER_PATH} variant="secondary">Damage Incidents</LinkButton>
                            <LinkButton to="/vehicle-rental/reports" variant="secondary">Reports & Intelligence</LinkButton>
                        </div>
                    </div>

                    {/* Recent Operational Running Charts */}
                    {data.recent_running_charts && data.recent_running_charts.length > 0 && (
                        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div className="flex items-center justify-between border-b border-slate-200 pb-3">
                                <h3 className="font-semibold text-slate-900">Recent Running Charts</h3>
                                <Link to={CHART_REGISTER_PATH} className="text-xs font-medium text-blue-600 hover:underline">View All</Link>
                            </div>
                            <div className="mt-3 overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead>
                                        <tr className="border-b border-slate-200 bg-slate-50 font-semibold uppercase tracking-wider text-slate-500">
                                            {['Chart Reference', 'Vehicle', 'Customer Ref', 'Recorded Window', 'Distance', 'Status'].map(h => (
                                                <th className="px-3 py-2" key={h}>{h}</th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {data.recent_running_charts.map(chart => (
                                            <tr key={chart.id} className="transition-colors hover:bg-blue-50/40">
                                                <td className="px-3 py-2 font-medium text-slate-900">{chart.reference}</td>
                                                <td className="px-3 py-2 text-slate-700">{chart.vehicle_label}</td>
                                                <td className="px-3 py-2 text-slate-600">{chart.customer_reference ?? '—'}</td>
                                                <td className="px-3 py-2 text-slate-600">
                                                    {formatBusinessDateTime(chart.starts_at)} — {formatBusinessDateTime(chart.ends_at)}
                                                </td>
                                                <td className="px-3 py-2 font-semibold text-slate-900">
                                                    {chart.total_km ? <><QuantityDisplay value={chart.total_km} /> km</> : '—'}
                                                </td>
                                                <td className="px-3 py-2"><StatusBadge status={chart.status} /></td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}
                </>
            ) : null}
        </div>
    );
}
