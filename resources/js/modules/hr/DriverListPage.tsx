import { useEffect, useMemo, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { useDebounce } from '@/shared/hooks/useDebounce';
import type { PaginationMeta } from '@/shared/types/pagination';
import { deleteDriver, listDrivers } from './driverApi';
import type { DriverSummary } from './driverTypes';

export default function DriverListPage() {
    const [rows, setRows] = useState<DriverSummary[]>([]);
    const [meta, setMeta] = useState<PaginationMeta>();
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [availability, setAvailability] = useState('');
    const [licenseStatus, setLicenseStatus] = useState('');
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);
    const [successMessage, setSuccessMessage] = useState<string | null>(null);

    const debounced = useDebounce(search);
    const { confirm, confirmDialog } = useConfirmDialog();

    const fetchDrivers = () => {
        const controller = new AbortController();
        setLoading(true);
        setError(null);

        listDrivers(
            {
                search: debounced || undefined,
                status: status || undefined,
                availability_status: availability || undefined,
                license_status: licenseStatus || undefined,
                page,
                per_page: 20,
            },
            controller.signal,
        )
            .then((response) => {
                if (controller.signal.aborted) return;
                setRows(response.data);
                setMeta(response.meta);
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
        return fetchDrivers();
    }, [availability, debounced, licenseStatus, page, status]);

    const handleDelete = async (driver: DriverSummary) => {
        const confirmed = await confirm({
            title: `Delete Driver: ${driver.display_name}`,
            message: `Are you sure you want to delete driver record ${driver.employee_number || driver.code}? This action cannot be undone.`,
            confirmLabel: 'Delete Driver',
            danger: true,
        });

        if (!confirmed) return;

        try {
            await deleteDriver(driver.id);
            setSuccessMessage(`Driver ${driver.display_name} was successfully removed.`);
            setTimeout(() => setSuccessMessage(null), 5000);
            fetchDrivers();
        } catch (deleteError) {
            setError(toApiError(deleteError));
        }
    };

    // Metrics summary
    const metrics = useMemo(() => {
        const total = meta?.total ?? rows.length;
        const availableCount = rows.filter((r) => r.availability_status === 'available').length;
        const assignedCount = rows.filter((r) => r.current_assignment !== null).length;
        const expiredOrExpiring = rows.filter((r) => r.license_status === 'expired' || r.license_status === 'expiring_soon').length;

        return { total, availableCount, assignedCount, expiredOrExpiring };
    }, [meta?.total, rows]);

    const renderLicenseBadge = (driver: DriverSummary) => {
        if (!driver.driving_license_number) {
            return <span className="text-xs text-slate-400">No license on file</span>;
        }

        if (driver.license_status === 'expired') {
            return (
                <span className="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-800">
                    <span className="h-1.5 w-1.5 rounded-full bg-rose-500" />
                    Expired {driver.days_until_license_expiry !== null ? `(${Math.abs(driver.days_until_license_expiry)}d ago)` : ''}
                </span>
            );
        }

        if (driver.license_status === 'expiring_soon') {
            return (
                <span className="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">
                    <span className="h-1.5 w-1.5 rounded-full bg-amber-500" />
                    Expiring soon ({driver.days_until_license_expiry}d)
                </span>
            );
        }

        return (
            <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">
                <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                Valid
            </span>
        );
    };

    return (
        <div className="space-y-6">
            <ContentHeader
                title="Drivers (HR Module)"
                description="Manage vehicle chauffeur and driver personnel records, driving licenses, validity compliance, and vehicle allocations."
                actions={
                    <div className="flex gap-2">
                        <LinkButton to="/hr/employees" variant="secondary">
                            All HR Employees
                        </LinkButton>
                        <LinkButton to="/hr/drivers/create">
                            Register Driver
                        </LinkButton>
                    </div>
                }
            />

            {/* Metrics cards */}
            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p className="text-xs font-medium uppercase tracking-wider text-slate-500">Total Drivers</p>
                    <p className="mt-1 text-2xl font-bold text-slate-900">{metrics.total}</p>
                </div>
                <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p className="text-xs font-medium uppercase tracking-wider text-emerald-600">Available</p>
                    <p className="mt-1 text-2xl font-bold text-emerald-700">{metrics.availableCount}</p>
                </div>
                <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p className="text-xs font-medium uppercase tracking-wider text-blue-600">Assigned to Vehicles</p>
                    <p className="mt-1 text-2xl font-bold text-blue-700">{metrics.assignedCount}</p>
                </div>
                <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p className="text-xs font-medium uppercase tracking-wider text-amber-600">License Attention</p>
                    <p className="mt-1 text-2xl font-bold text-amber-700">{metrics.expiredOrExpiring}</p>
                </div>
            </div>

            {/* Filter bar */}
            <div className="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-4">
                <Input
                    label="Search Drivers"
                    value={search}
                    onChange={(e) => {
                        setSearch(e.target.value);
                        setPage(1);
                    }}
                    placeholder="Search name, code, license, NIC, phone..."
                />
                <Select
                    label="Employment Status"
                    value={status}
                    options={[
                        { value: '', label: 'All Statuses' },
                        { value: 'active', label: 'Active' },
                        { value: 'on_leave', label: 'On Leave' },
                        { value: 'inactive', label: 'Inactive' },
                        { value: 'suspended', label: 'Suspended' },
                        { value: 'pending_approval', label: 'Pending Approval' },
                    ]}
                    onChange={(e) => {
                        setStatus(e.target.value);
                        setPage(1);
                    }}
                />
                <Select
                    label="Availability"
                    value={availability}
                    options={[
                        { value: '', label: 'All Availabilities' },
                        { value: 'available', label: 'Available' },
                        { value: 'assigned', label: 'Assigned' },
                        { value: 'on_leave', label: 'On Leave' },
                        { value: 'unavailable', label: 'Unavailable' },
                    ]}
                    onChange={(e) => {
                        setAvailability(e.target.value);
                        setPage(1);
                    }}
                />
                <Select
                    label="License Validity"
                    value={licenseStatus}
                    options={[
                        { value: '', label: 'All Licenses' },
                        { value: 'valid', label: 'Valid' },
                        { value: 'expiring_soon', label: 'Expiring Soon (<=30 days)' },
                        { value: 'expired', label: 'Expired' },
                    ]}
                    onChange={(e) => {
                        setLicenseStatus(e.target.value);
                        setPage(1);
                    }}
                />
            </div>

            {successMessage && (
                <div className="rounded-md bg-emerald-50 p-4 text-sm font-medium text-emerald-800 border border-emerald-200">
                    {successMessage}
                </div>
            )}

            <ErrorAlert error={error} />

            {loading ? (
                <LoadingState label="Loading drivers..." />
            ) : (
                <>
                    <DataTable
                        rows={rows}
                        rowKey={(row) => row.id}
                        columns={[
                            {
                                key: 'driver',
                                header: 'Driver',
                                render: (row) => (
                                    <div>
                                        <p className="font-semibold text-slate-900">{row.display_name}</p>
                                        <p className="text-xs text-slate-500">
                                            {row.employee_number || row.code}
                                            {row.nic_passport ? ` • NIC: ${row.nic_passport}` : ''}
                                        </p>
                                    </div>
                                ),
                            },
                            {
                                key: 'contact',
                                header: 'Contact',
                                render: (row) => (
                                    <div className="text-sm">
                                        <p className="font-medium text-slate-800">{row.mobile || row.phone || '-'}</p>
                                        {row.email && <p className="text-xs text-slate-500">{row.email}</p>}
                                    </div>
                                ),
                            },
                            {
                                key: 'license',
                                header: 'Driving License',
                                render: (row) => (
                                    <div className="space-y-1">
                                        <div className="flex items-center gap-2">
                                            <span className="font-mono text-sm font-semibold text-slate-800">
                                                {row.driving_license_number || 'N/A'}
                                            </span>
                                            {renderLicenseBadge(row)}
                                        </div>
                                        {row.driving_license_expiry && (
                                            <p className="text-xs text-slate-500">
                                                Expires: {row.driving_license_expiry}
                                            </p>
                                        )}
                                        {Array.isArray(row.driving_license_classes) && row.driving_license_classes.length > 0 && (
                                            <div className="flex flex-wrap gap-1">
                                                {row.driving_license_classes.map((cls) => (
                                                    <span
                                                        key={cls}
                                                        className="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600"
                                                    >
                                                        {cls}
                                                    </span>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                ),
                            },
                            {
                                key: 'assignment',
                                header: 'Current Assignment',
                                render: (row) => {
                                    if (row.current_assignment) {
                                        return (
                                            <div className="space-y-0.5">
                                                <span className="inline-flex items-center gap-1 rounded bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 border border-blue-200">
                                                    🚗 {row.current_assignment.vehicle_number}
                                                </span>
                                                <p className="text-[11px] text-slate-500">
                                                    Role: {row.current_assignment.role}
                                                </p>
                                            </div>
                                        );
                                    }

                                    return (
                                        <span className="text-xs font-medium text-slate-400">
                                            No active allocation
                                        </span>
                                    );
                                },
                            },
                            {
                                key: 'availability',
                                header: 'Availability',
                                render: (row) => <StatusBadge status={row.availability_status} />,
                            },
                            {
                                key: 'status',
                                header: 'Status',
                                render: (row) => <StatusBadge status={row.status} />,
                            },
                            {
                                key: 'actions',
                                header: '',
                                render: (row) => (
                                    <div className="flex justify-end gap-1">
                                        <LinkButton to={`/hr/drivers/${row.id}`} variant="ghost">
                                            View
                                        </LinkButton>
                                        <LinkButton to={`/hr/drivers/${row.id}/edit`} variant="ghost">
                                            Edit
                                        </LinkButton>
                                        <Button
                                            variant="secondary"
                                            className="text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                                            onClick={() => void handleDelete(row)}
                                        >
                                            Delete
                                        </Button>
                                    </div>
                                ),
                            },
                        ]}
                    />
                    <Pagination meta={meta} onPageChange={setPage} />
                </>
            )}

            {confirmDialog}
        </div>
    );
}
