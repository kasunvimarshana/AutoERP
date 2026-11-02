import { useEffect, useState } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Textarea } from '@/shared/components/Textarea';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { changeDriverStatus, deleteDriver, getDriver } from './driverApi';
import type { DriverDetail } from './driverTypes';

export default function DriverDetailPage() {
    const id = Number(useParams().id);
    const navigate = useNavigate();
    const [driver, setDriver] = useState<DriverDetail | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);

    // Status change modal state
    const [statusModalOpen, setStatusModalOpen] = useState(false);
    const [newStatus, setNewStatus] = useState('active');
    const [newAvailability, setNewAvailability] = useState('available');
    const [statusReason, setStatusReason] = useState('');
    const [statusSubmitting, setStatusSubmitting] = useState(false);
    const [statusError, setStatusError] = useState<ApiError | null>(null);

    const { confirm, confirmDialog } = useConfirmDialog();

    const fetchDriver = () => {
        const controller = new AbortController();
        setLoading(true);
        setError(null);

        getDriver(id, controller.signal)
            .then((data) => {
                if (!controller.signal.aborted) {
                    setDriver(data);
                    setNewStatus(data.status);
                    setNewAvailability(data.availability_status);
                }
            })
            .catch((requestError) => {
                if (!controller.signal.aborted) {
                    setError(toApiError(requestError));
                }
            })
            .finally(() => {
                if (!controller.signal.aborted) {
                    setLoading(false);
                }
            });

        return () => controller.abort();
    };

    useEffect(() => {
        return fetchDriver();
    }, [id]);

    const handleStatusSubmit = async () => {
        setStatusSubmitting(true);
        setStatusError(null);

        try {
            const updated = await changeDriverStatus(id, {
                status: newStatus,
                availability_status: newAvailability,
                reason: statusReason || undefined,
            });
            setDriver(updated);
            setStatusModalOpen(false);
            setStatusReason('');
        } catch (err) {
            setStatusError(toApiError(err));
        } finally {
            setStatusSubmitting(false);
        }
    };

    const handleDelete = async () => {
        if (!driver) return;

        const confirmed = await confirm({
            title: `Delete Driver: ${driver.display_name}`,
            message: `Are you sure you want to delete this driver profile? This will permanently remove driver record ${driver.employee_number || driver.code}.`,
            confirmLabel: 'Delete Driver',
            danger: true,
        });

        if (!confirmed) return;

        try {
            await deleteDriver(driver.id);
            navigate('/hr/drivers');
        } catch (deleteError) {
            setError(toApiError(deleteError));
        }
    };

    if (loading) return <LoadingState label="Loading driver profile..." />;
    if (!driver) return <ErrorAlert error={error} />;

    const licenseBadge = () => {
        if (!driver.driving_license_number) {
            return <span className="text-xs text-slate-400">No license on file</span>;
        }

        if (driver.license_status === 'expired') {
            return (
                <span className="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-800">
                    <span className="h-2 w-2 rounded-full bg-rose-500" />
                    EXPIRED {driver.days_until_license_expiry !== null ? `(${Math.abs(driver.days_until_license_expiry)} days ago)` : ''}
                </span>
            );
        }

        if (driver.license_status === 'expiring_soon') {
            return (
                <span className="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">
                    <span className="h-2 w-2 rounded-full bg-amber-500" />
                    EXPIRING SOON ({driver.days_until_license_expiry} days remaining)
                </span>
            );
        }

        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                <span className="h-2 w-2 rounded-full bg-emerald-500" />
                VALID LICENSE {driver.days_until_license_expiry !== null ? `(${driver.days_until_license_expiry} days remaining)` : ''}
            </span>
        );
    };

    return (
        <div className="space-y-6">
            <ContentHeader
                title={driver.display_name}
                description={`Driver Code: ${driver.employee_number || driver.code || `ID #${driver.id}`} • Designation: ${driver.designation?.name ?? 'Driver'}`}
                actions={
                    <div className="flex gap-2">
                        <LinkButton to="/hr/drivers" variant="secondary">
                            Back to Drivers
                        </LinkButton>
                        <Button
                            variant="secondary"
                            onClick={() => {
                                setNewStatus(driver.status);
                                setNewAvailability(driver.availability_status);
                                setStatusModalOpen(true);
                            }}
                        >
                            Change Status
                        </Button>
                        <LinkButton to={`/hr/drivers/${id}/edit`}>
                            Edit Driver
                        </LinkButton>
                        <Button
                            variant="secondary"
                            className="text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                            onClick={() => void handleDelete()}
                        >
                            Delete
                        </Button>
                    </div>
                }
            />

            <ErrorAlert error={error} />

            {/* Quick Status Bar */}
            <div className="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div className="flex flex-wrap items-center gap-4">
                    <div className="flex items-center gap-2">
                        <span className="text-xs font-medium uppercase text-slate-500">Employment:</span>
                        <StatusBadge status={driver.status} />
                    </div>
                    <div className="flex items-center gap-2">
                        <span className="text-xs font-medium uppercase text-slate-500">Availability:</span>
                        <StatusBadge status={driver.availability_status} />
                    </div>
                    <div className="flex items-center gap-2">
                        <span className="text-xs font-medium uppercase text-slate-500">License:</span>
                        {licenseBadge()}
                    </div>
                </div>

                {driver.current_assignment && (
                    <div className="flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-800 border border-blue-200">
                        <span>Allocated to: <strong>{driver.current_assignment.vehicle_number}</strong></span>
                        <span>({driver.current_assignment.role})</span>
                    </div>
                )}
            </div>

            {/* Grid layout */}
            <div className="grid gap-6 lg:grid-cols-3">
                {/* 1. Left 2 columns: Details */}
                <div className="space-y-6 lg:col-span-2">
                    {/* Personal & Contact Details */}
                    <Panel title="Personal & Contact Profile">
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt className="text-xs font-medium uppercase text-slate-500">Full Name</dt>
                                <dd className="mt-1 text-sm font-semibold text-slate-900">
                                    {[driver.first_name, driver.middle_name, driver.last_name].filter(Boolean).join(' ') || driver.display_name}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs font-medium uppercase text-slate-500">NIC / Passport Number</dt>
                                <dd className="mt-1 text-sm font-semibold text-slate-900">{driver.nic_passport || '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs font-medium uppercase text-slate-500">Mobile Phone</dt>
                                <dd className="mt-1 text-sm font-semibold text-slate-900">{driver.mobile || '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs font-medium uppercase text-slate-500">Landline Phone</dt>
                                <dd className="mt-1 text-sm text-slate-700">{driver.phone || '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs font-medium uppercase text-slate-500">Email Address</dt>
                                <dd className="mt-1 text-sm text-slate-700">{driver.email || '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs font-medium uppercase text-slate-500">Date of Birth / Gender</dt>
                                <dd className="mt-1 text-sm text-slate-700">
                                    {driver.date_of_birth || '-'} {driver.gender ? `(${driver.gender})` : ''}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs font-medium uppercase text-slate-500">Blood Group</dt>
                                <dd className="mt-1 text-sm text-slate-700">{driver.blood_group || '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs font-medium uppercase text-slate-500">Residential Address</dt>
                                <dd className="mt-1 text-sm text-slate-700">
                                    {[driver.address, driver.city].filter(Boolean).join(', ') || '-'}
                                </dd>
                            </div>
                        </dl>

                        {/* Emergency Contact */}
                        {(driver.emergency_contact_name || driver.emergency_contact_phone) && (
                            <div className="mt-6 border-t border-slate-100 pt-4">
                                <h4 className="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Emergency Contact</h4>
                                <div className="rounded-lg bg-slate-50 p-3 text-sm">
                                    <p className="font-semibold text-slate-800">{driver.emergency_contact_name || 'Named Contact'}</p>
                                    <p className="text-xs text-slate-600">
                                        Relationship: {driver.emergency_contact_relationship || 'Relative'} • Phone: {driver.emergency_contact_phone || '-'}
                                    </p>
                                </div>
                            </div>
                        )}
                    </Panel>

                    {/* Vehicle Allocation History */}
                    <Panel title="Vehicle Assignment History">
                        <p className="mb-4 text-xs text-slate-500">
                            Past and active vehicle allocations linked to this driver from the Vehicle Rental module.
                        </p>
                        {Array.isArray(driver.recent_assignments) && driver.recent_assignments.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs text-slate-700">
                                    <thead className="border-b border-slate-200 bg-slate-50 text-[11px] font-semibold uppercase text-slate-500">
                                        <tr>
                                            <th className="px-3 py-2">Vehicle</th>
                                            <th className="px-3 py-2">Agreement</th>
                                            <th className="px-3 py-2">Role</th>
                                            <th className="px-3 py-2">From Date</th>
                                            <th className="px-3 py-2">To Date</th>
                                            <th className="px-3 py-2">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {driver.recent_assignments.map((assignment) => (
                                            <tr key={assignment.id} className="hover:bg-slate-50">
                                                <td className="px-3 py-2.5 font-semibold text-slate-900">
                                                    🚗 {assignment.vehicle_number}
                                                </td>
                                                <td className="px-3 py-2.5 text-slate-600">
                                                    {assignment.agreement_number || '-'}
                                                </td>
                                                <td className="px-3 py-2.5 capitalize">{assignment.role}</td>
                                                <td className="px-3 py-2.5">{assignment.assigned_from}</td>
                                                <td className="px-3 py-2.5">{assignment.assigned_to || 'Ongoing'}</td>
                                                <td className="px-3 py-2.5">
                                                    <StatusBadge status={assignment.status} />
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <p className="text-sm text-slate-500 py-4 text-center">
                                No vehicle allocation history recorded yet for this driver.
                            </p>
                        )}
                    </Panel>

                    {/* Status History */}
                    {Array.isArray(driver.status_histories) && driver.status_histories.length > 0 && (
                        <Panel title="Status Audit History">
                            <ul className="divide-y divide-slate-100 text-xs text-slate-600">
                                {driver.status_histories.map((hist) => (
                                    <li key={hist.id} className="py-2.5 flex items-center justify-between">
                                        <div>
                                            <span className="font-semibold text-slate-800 capitalize">
                                                {hist.old_status || 'initial'} → {hist.new_status}
                                            </span>
                                            {hist.reason && <p className="text-slate-500 mt-0.5">{hist.reason}</p>}
                                        </div>
                                        <span className="text-[11px] text-slate-400">{hist.changed_at}</span>
                                    </li>
                                ))}
                            </ul>
                        </Panel>
                    )}
                </div>

                {/* 2. Right 1 column: License & Rates Cards */}
                <div className="space-y-6">
                    {/* Driving License Compliance Card */}
                    <Panel title="Driving License Compliance">
                        <div className="space-y-4">
                            <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div className="text-xs font-medium uppercase text-slate-500">License Number</div>
                                <div className="mt-1 font-mono text-xl font-bold text-slate-900">
                                    {driver.driving_license_number || 'NOT SPECIFIED'}
                                </div>
                                <div className="mt-2">{licenseBadge()}</div>
                            </div>

                            <dl className="grid gap-3 text-xs">
                                <div>
                                    <dt className="font-medium text-slate-500 uppercase">Issue Date</dt>
                                    <dd className="mt-0.5 text-sm font-semibold text-slate-800">
                                        {driver.driving_license_issued || 'Not recorded'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="font-medium text-slate-500 uppercase">Expiry Date</dt>
                                    <dd className="mt-0.5 text-sm font-semibold text-slate-800">
                                        {driver.driving_license_expiry || 'Not recorded'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="font-medium text-slate-500 uppercase">Endorsed Vehicle Classes</dt>
                                    <dd className="mt-1 flex flex-wrap gap-1">
                                        {Array.isArray(driver.driving_license_classes) && driver.driving_license_classes.length > 0 ? (
                                            driver.driving_license_classes.map((cls) => (
                                                <span
                                                    key={cls}
                                                    className="rounded bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800"
                                                >
                                                    Class {cls}
                                                </span>
                                            ))
                                        ) : (
                                            <span className="text-slate-400">Standard Light Vehicle</span>
                                        )}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </Panel>

                    {/* Operational Rates Card */}
                    <Panel title="Employment & Rates">
                        <dl className="grid gap-4 text-xs">
                            <div>
                                <dt className="font-medium text-slate-500 uppercase">Joined Date</dt>
                                <dd className="mt-0.5 text-sm font-semibold text-slate-800">
                                    {driver.joined_date || '-'}
                                </dd>
                            </div>
                            <div>
                                <dt className="font-medium text-slate-500 uppercase">Default Daily Allowance / Rate</dt>
                                <dd className="mt-0.5 text-base font-bold text-emerald-700">
                                    LKR {Number(driver.default_daily_rate).toFixed(2)}
                                </dd>
                            </div>
                            <div>
                                <dt className="font-medium text-slate-500 uppercase">Default Hourly Overtime Rate</dt>
                                <dd className="mt-0.5 text-base font-bold text-slate-800">
                                    LKR {Number(driver.default_hourly_rate).toFixed(2)} / hr
                                </dd>
                            </div>
                            <div className="border-t border-slate-100 pt-3">
                                <dt className="font-medium text-slate-500 uppercase">Regular Duty Schedule</dt>
                                <dd className="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-800">
                                    <span className="rounded bg-blue-50 px-2 py-1 font-semibold text-blue-900 border border-blue-100">
                                        In: {driver.work_start_time || '08:00'}
                                    </span>
                                    <span className="text-slate-400">-</span>
                                    <span className="rounded bg-blue-50 px-2 py-1 font-semibold text-blue-900 border border-blue-100">
                                        Out: {driver.work_end_time || '17:00'}
                                    </span>
                                    <span className="rounded bg-slate-100 px-2 py-1 text-xs text-slate-600">
                                        Shift: {driver.standard_shift_hours ? `${driver.standard_shift_hours} hrs` : '9 hrs'}
                                    </span>
                                </dd>
                            </div>
                            {driver.notes && (
                                <div className="border-t border-slate-100 pt-3">
                                    <dt className="font-medium text-slate-500 uppercase">Operational Remarks</dt>
                                    <dd className="mt-1 text-slate-700 whitespace-pre-wrap">{driver.notes}</dd>
                                </div>
                            )}
                        </dl>
                    </Panel>
                </div>
            </div>

            {/* Change Status Modal */}
            <Modal
                open={statusModalOpen}
                title={`Change Driver Status: ${driver.display_name}`}
                onClose={() => setStatusModalOpen(false)}
                closeDisabled={statusSubmitting}
            >
                <div className="space-y-4">
                    <ErrorAlert error={statusError} />
                    <Select
                        label="Employment Status"
                        value={newStatus}
                        options={[
                            { value: 'active', label: 'Active' },
                            { value: 'on_leave', label: 'On Leave' },
                            { value: 'inactive', label: 'Inactive' },
                            { value: 'suspended', label: 'Suspended' },
                            { value: 'terminated', label: 'Terminated' },
                        ]}
                        onChange={(e) => setNewStatus(e.target.value)}
                    />
                    <Select
                        label="Availability Status"
                        value={newAvailability}
                        options={[
                            { value: 'available', label: 'Available for Allocation' },
                            { value: 'assigned', label: 'Currently Assigned' },
                            { value: 'on_leave', label: 'On Leave' },
                            { value: 'unavailable', label: 'Unavailable' },
                        ]}
                        onChange={(e) => setNewAvailability(e.target.value)}
                    />
                    <Textarea
                        label="Reason for status change"
                        rows={3}
                        value={statusReason}
                        onChange={(e) => setStatusReason(e.target.value)}
                        placeholder="e.g. Annual leave, medical rest, assignment rotation..."
                    />
                    <div className="flex justify-end gap-2 pt-2">
                        <Button
                            variant="secondary"
                            onClick={() => setStatusModalOpen(false)}
                            disabled={statusSubmitting}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="primary"
                            loading={statusSubmitting}
                            onClick={() => void handleStatusSubmit()}
                        >
                            Update Status
                        </Button>
                    </div>
                </div>
            </Modal>

            {confirmDialog}
        </div>
    );
}
