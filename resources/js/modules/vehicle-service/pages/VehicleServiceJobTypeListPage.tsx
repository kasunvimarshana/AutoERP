import { useState } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { FormDrawer } from '@/shared/components/Drawer';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { notifySuccess } from '@/shared/notifications/appToast';
import {
    createVehicleServiceJobType,
    deleteVehicleServiceJobType,
    listVehicleServiceJobTypes,
    updateVehicleServiceJobType,
} from '../vehicleServiceApi';
import { vehicleServicePermissions } from '../vehicleServicePermissions';
import type {
    VehicleServiceJobTypePayload,
    VehicleServiceJobTypeResource,
} from '../vehicleServiceTypes';

interface JobTypeFormState {
    code: string;
    name: string;
    description: string;
    tracks_mileage: boolean;
    is_active: boolean;
    sort_order: number;
}

const emptyFormState: JobTypeFormState = {
    code: '',
    name: '',
    description: '',
    tracks_mileage: false,
    is_active: true,
    sort_order: 10,
};

export default function VehicleServiceJobTypeListPage() {
    const auth = useAuth();
    const { confirm, confirmDialog } = useConfirmDialog();
    const canManage = hasPermission(auth, vehicleServicePermissions.jobTypesManage);

    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [page, setPage] = useState(1);
    const [actionError, setActionError] = useState<ApiError | null>(null);

    // Drawer state
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editingType, setEditingType] = useState<VehicleServiceJobTypeResource | null>(null);
    const [formData, setFormData] = useState<JobTypeFormState>(emptyFormState);
    const [formError, setFormError] = useState<ApiError | null>(null);
    const [saving, setSaving] = useState(false);

    const debouncedSearch = useDebounce(search);

    const result = useApi((signal) => listVehicleServiceJobTypes({
        search: debouncedSearch || undefined,
        is_active: statusFilter === '' ? undefined : statusFilter === 'true',
        page,
        per_page: 25,
    }, signal), [debouncedSearch, statusFilter, page]);

    const collection = result.data;

    const openCreateDrawer = () => {
        setEditingType(null);
        setFormData({
            ...emptyFormState,
            sort_order: (collection?.data?.length ?? 0) * 10 + 10,
        });
        setFormError(null);
        setDrawerOpen(true);
    };

    const openEditDrawer = (type: VehicleServiceJobTypeResource) => {
        setEditingType(type);
        setFormData({
            code: type.code,
            name: type.name,
            description: type.description ?? '',
            tracks_mileage: type.tracks_mileage,
            is_active: type.is_active,
            sort_order: type.sort_order,
        });
        setFormError(null);
        setDrawerOpen(true);
    };

    const handleFormSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setSaving(true);
        setFormError(null);
        try {
            const payload: VehicleServiceJobTypePayload = {
                code: formData.code.trim(),
                name: formData.name.trim(),
                description: formData.description.trim() || null,
                tracks_mileage: formData.tracks_mileage,
                is_active: formData.is_active,
                sort_order: Number(formData.sort_order) || 0,
            };

            if (editingType) {
                payload.expected_version = editingType.row_version;
                const updated = await updateVehicleServiceJobType(editingType.id, payload);
                notifySuccess(`Service job type "${updated.name}" updated successfully.`);
            } else {
                const created = await createVehicleServiceJobType(payload);
                notifySuccess(`Service job type "${created.name}" created successfully.`);
            }

            setDrawerOpen(false);
            result.reload();
        } catch (error) {
            setFormError(toApiError(error));
        } finally {
            setSaving(false);
        }
    };

    const handleToggleActive = async (row: VehicleServiceJobTypeResource) => {
        setActionError(null);
        try {
            const nextActive = !row.is_active;
            const updated = await updateVehicleServiceJobType(row.id, {
                expected_version: row.row_version,
                code: row.code,
                name: row.name,
                description: row.description ?? null,
                tracks_mileage: row.tracks_mileage,
                is_active: nextActive,
                sort_order: row.sort_order,
            });
            notifySuccess(`Job type "${updated.name}" ${updated.is_active ? 'activated' : 'deactivated'}.`);
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        }
    };

    const handleDelete = async (row: VehicleServiceJobTypeResource) => {
        if (row.is_system) {
            return;
        }

        const confirmed = await confirm({
            title: 'Delete Service Job Type',
            message: `Are you sure you want to delete "${row.name}" (${row.code})? This action cannot be undone.`,
            confirmLabel: 'Delete Job Type',
            danger: true,
        });

        if (!confirmed) return;

        setActionError(null);
        try {
            await deleteVehicleServiceJobType(row.id, row.row_version);
            notifySuccess(`Service job type "${row.name}" deleted successfully.`);
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        }
    };

    const columns: DataColumn<VehicleServiceJobTypeResource>[] = [
        {
            key: 'name',
            header: 'Job Type',
            render: (row) => (
                <div>
                    <span className="font-semibold text-slate-900">{row.name}</span>
                    {row.description && (
                        <p className="mt-0.5 text-xs text-slate-500 line-clamp-1">{row.description}</p>
                    )}
                </div>
            ),
        },
        {
            key: 'code',
            header: 'Code',
            render: (row) => (
                <span className="inline-block rounded border border-slate-200 bg-slate-50 px-2 py-0.5 font-mono text-xs font-medium text-slate-700">
                    {row.code}
                </span>
            ),
        },
        {
            key: 'tracks_mileage',
            header: 'Mileage Tracking',
            render: (row) => (
                row.tracks_mileage ? (
                    <span className="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                        Required
                    </span>
                ) : (
                    <span className="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-xs font-medium text-slate-500">
                        Not Tracked
                    </span>
                )
            ),
        },
        {
            key: 'system',
            header: 'Type Kind',
            render: (row) => (
                row.is_system ? (
                    <span className="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700">
                        System
                    </span>
                ) : (
                    <span className="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                        Custom
                    </span>
                )
            ),
        },
        {
            key: 'sort_order',
            header: 'Order',
            render: (row) => <span className="text-sm text-slate-600">{row.sort_order}</span>,
        },
        {
            key: 'status',
            header: 'Status',
            render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} />,
        },
    ];

    if (canManage) {
        columns.push({
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (row) => (
                <div className="flex items-center justify-end gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        size="sm"
                        onClick={() => openEditDrawer(row)}
                    >
                        Edit
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => void handleToggleActive(row)}
                    >
                        {row.is_active ? 'Deactivate' : 'Activate'}
                    </Button>
                    {!row.is_system && (
                        <Button
                            type="button"
                            variant="danger"
                            size="sm"
                            onClick={() => void handleDelete(row)}
                        >
                            Delete
                        </Button>
                    )}
                </div>
            ),
        });
    }

    const errorFor = (field: string) => fieldError(formError, field);

    return (
        <>
            <ContentHeader
                title="Service Job Types"
                description="Manage service job categories, system classifications, and odometer mileage tracking rules."
                actions={canManage ? (
                    <Button type="button" onClick={openCreateDrawer}>
                        New Job Type
                    </Button>
                ) : undefined}
            />

            <div className="mb-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Input
                    type="search"
                    label="Search"
                    placeholder="Search by code or name"
                    value={search}
                    onChange={(e) => {
                        setSearch(e.target.value);
                        setPage(1);
                    }}
                />
                <Select
                    label="Status"
                    value={statusFilter}
                    onChange={(e) => {
                        setStatusFilter(e.target.value);
                        setPage(1);
                    }}
                    placeholder="All statuses"
                    options={[
                        { value: 'true', label: 'Active' },
                        { value: 'false', label: 'Inactive' },
                    ]}
                />
            </div>

            <ErrorAlert error={actionError ?? result.error} />

            {result.loading && !collection ? (
                <LoadingState />
            ) : (
                <DataTable
                    rows={collection?.data ?? []}
                    columns={columns}
                    rowKey={(row) => row.id}
                    emptyMessage="No service job types found."
                />
            )}

            <Pagination meta={collection?.meta} onPageChange={setPage} />

            {/* Create / Edit Drawer */}
            <FormDrawer
                open={drawerOpen}
                title={editingType ? `Edit ${editingType.name}` : 'New Service Job Type'}
                onClose={() => setDrawerOpen(false)}
                closeDisabled={saving}
            >
                <form className="space-y-4" onSubmit={handleFormSubmit}>
                    <ErrorAlert error={formError} />

                    <Input
                        label="Name"
                        value={formData.name}
                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                        error={errorFor('name')}
                        placeholder="e.g. Express Detailing"
                        required
                    />

                    <Input
                        label="Code"
                        value={formData.code}
                        onChange={(e) => setFormData({ ...formData, code: e.target.value })}
                        error={errorFor('code')}
                        placeholder="e.g. express_detailing"
                        disabled={Boolean(editingType?.is_system)}
                        hint={editingType?.is_system ? 'System job type codes cannot be changed.' : 'Unique code identifier.'}
                        required
                    />

                    <Textarea
                        label="Description"
                        value={formData.description}
                        onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                        error={errorFor('description')}
                        placeholder="Optional description of services included..."
                        rows={3}
                    />

                    <div className="rounded-lg border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                        <label className="flex items-start gap-3 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={formData.tracks_mileage}
                                onChange={(e) => setFormData({ ...formData, tracks_mileage: e.target.checked })}
                                className="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            />
                            <div>
                                <span className="text-sm font-medium text-slate-800">Track Vehicle Mileage</span>
                                <p className="text-xs text-slate-500">
                                    When checked, service jobs using this type will require odometer readings.
                                </p>
                            </div>
                        </label>

                        <label className="flex items-start gap-3 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={formData.is_active}
                                onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                                className="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            />
                            <div>
                                <span className="text-sm font-medium text-slate-800">Active</span>
                                <p className="text-xs text-slate-500">
                                    Active job types are selectable when creating or editing service jobs.
                                </p>
                            </div>
                        </label>
                    </div>

                    <Input
                        label="Sort Order"
                        type="number"
                        value={String(formData.sort_order)}
                        onChange={(e) => setFormData({ ...formData, sort_order: parseInt(e.target.value, 10) || 0 })}
                        error={errorFor('sort_order')}
                        placeholder="0"
                    />

                    <div className="mt-6 flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => setDrawerOpen(false)}
                            disabled={saving}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            disabled={saving}
                        >
                            {saving ? 'Saving...' : editingType ? 'Save Changes' : 'Create Job Type'}
                        </Button>
                    </div>
                </form>
            </FormDrawer>

            {confirmDialog}
        </>
    );
}
