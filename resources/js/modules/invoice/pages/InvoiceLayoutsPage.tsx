/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { useState } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { invoicePermissions } from '../invoicePermissions';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import {
    deleteInvoiceLayout,
    listInvoiceLayouts,
    setDefaultInvoiceLayout,
} from '../invoiceLayoutApi';
import type { InvoiceLayout } from '../invoiceLayoutTypes';
import { InvoiceLayoutPreviewModal } from '../components/InvoiceLayoutPreviewModal';
import { InvoiceLayoutEditorDrawer } from '../components/InvoiceLayoutEditorDrawer';
import { InvoicePrinterAssignmentsModal } from '../components/InvoicePrinterAssignmentsModal';

export default function InvoiceLayoutsPage() {
    const auth = useAuth();
    const canManage = hasPermission(auth, invoicePermissions.layoutsManage);

    const [search, setSearch] = useState('');
    const [paperSize, setPaperSize] = useState<string>('all');
    const [statusFilter, setStatusFilter] = useState<string>('all');
    const debouncedSearch = useDebounce(search);

    const [previewLayout, setPreviewLayout] = useState<InvoiceLayout | null>(null);
    const [editingLayout, setEditingLayout] = useState<InvoiceLayout | null>(null);
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [printerModalOpen, setPrinterModalOpen] = useState(false);
    const [deletingLayout, setDeletingLayout] = useState<InvoiceLayout | null>(null);
    const [deleteLoading, setDeleteLoading] = useState(false);
    const [actionSuccess, setActionSuccess] = useState<string | null>(null);
    const [actionError, setActionError] = useState<ApiError | null>(null);

    const {
        data: layouts,
        loading,
        error,
        reload,
        setData: setLayouts,
    } = useApi(
        (signal) =>
            listInvoiceLayouts(
                {
                    search: debouncedSearch || undefined,
                    paper_size: paperSize !== 'all' ? paperSize : undefined,
                    is_active: statusFilter === 'all' ? undefined : statusFilter === 'active',
                },
                signal,
            ),
        [debouncedSearch, paperSize, statusFilter],
    );

    const handleCreateNew = () => {
        setEditingLayout(null);
        setDrawerOpen(true);
    };

    const handleEdit = (layout: InvoiceLayout) => {
        setEditingLayout(layout);
        setDrawerOpen(true);
    };

    const handleDuplicate = (layout: InvoiceLayout) => {
        setEditingLayout({
            ...layout,
            id: 0,
            code: `${layout.code}_copy`,
            name: `${layout.name} (Copy)`,
            is_system: false,
            row_version: 1,
        });
        setDrawerOpen(true);
    };

    const handleSetDefault = async (layout: InvoiceLayout, scope: 'tenant' | 'organization_unit') => {
        setActionError(null);
        setActionSuccess(null);
        try {
            await setDefaultInvoiceLayout({
                code: layout.code,
                organization_unit_id: scope === 'organization_unit' && auth.organizationUnit?.id != null
                    ? Number(auth.organizationUnit.id)
                    : null,
                scope,
            });
            const scopeLabel = scope === 'organization_unit'
                ? `branch (${auth.organizationUnit?.name ?? 'current organization unit'})`
                : 'organization';
            setActionSuccess(`Layout "${layout.name}" set as default for ${scopeLabel}.`);
        } catch (caught: unknown) {
            setActionError(toApiError(caught));
        }
    };

    const handleDeleteConfirm = async () => {
        if (!deletingLayout) return;
        setDeleteLoading(true);
        setActionError(null);
        setActionSuccess(null);
        try {
            await deleteInvoiceLayout(deletingLayout.id);
            setActionSuccess(`Layout "${deletingLayout.name}" deleted.`);
            setDeletingLayout(null);
            reload();
        } catch (caught: unknown) {
            setActionError(toApiError(caught));
        } finally {
            setDeleteLoading(false);
        }
    };

    const handleSaved = (saved: InvoiceLayout) => {
        setActionSuccess(`Layout "${saved.name}" successfully saved.`);
        if (layouts) {
            const exists = layouts.some((item) => item.id === saved.id);
            if (exists) {
                setLayouts(layouts.map((item) => (item.id === saved.id ? saved : item)));
            } else {
                setLayouts([saved, ...layouts]);
            }
        } else {
            reload();
        }
    };

    return (
        <div className="space-y-6">
            <ContentHeader
                title="Invoice Layout Templates"
                description="Manage and customize printable invoice formats, templates, and default layouts for your organization and branches."
                actions={
                    canManage ? (
                        <div className="flex items-center gap-2">
                            <Button variant="secondary" onClick={() => setPrinterModalOpen(true)}>
                                Printer Defaults
                            </Button>
                            <Button onClick={handleCreateNew}>
                                + New Custom Layout
                            </Button>
                        </div>
                    ) : undefined
                }
            />

            {actionSuccess && (
                <SuccessAlert message={actionSuccess} onDismiss={() => setActionSuccess(null)} />
            )}
            <ErrorAlert error={actionError ?? error} />

            {/* Filter Bar */}
            <div className="flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-xs sm:flex-row sm:items-center sm:justify-between">
                <div className="w-full sm:max-w-xs">
                    <Input
                        placeholder="Search layouts by name or code..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>
                <div className="flex flex-wrap items-center gap-3">
                    <Select
                        value={paperSize}
                        onChange={(e) => setPaperSize(e.target.value)}
                        options={[
                            { value: 'all', label: 'All Paper Sizes' },
                            { value: 'a4', label: 'A4' },
                            { value: 'a5', label: 'A5' },
                            { value: 'thermal_80mm', label: '80mm Thermal POS' },
                            { value: 'continuous_8_5_by_5_5', label: 'Continuous 8.5 × 5.5' },
                            { value: 'letter', label: 'Letter' },
                        ]}
                    />
                    <Select
                        value={statusFilter}
                        onChange={(e) => setStatusFilter(e.target.value)}
                        options={[
                            { value: 'all', label: 'All Statuses' },
                            { value: 'active', label: 'Active Only' },
                            { value: 'inactive', label: 'Inactive Only' },
                        ]}
                    />
                </div>
            </div>

            {/* Layout Cards Grid */}
            {loading && !layouts ? (
                <div className="flex min-h-[300px] items-center justify-center">
                    <LoadingState label="Loading invoice layouts…" />
                </div>
            ) : layouts && layouts.length === 0 ? (
                <div className="rounded-xl border border-dashed border-slate-300 p-12 text-center">
                    <p className="text-sm font-medium text-slate-700">No invoice layouts found matching the criteria.</p>
                    <p className="mt-1 text-xs text-slate-500">Try adjusting your search terms or filters.</p>
                </div>
            ) : (
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {layouts?.map((layout) => (
                        <div
                            key={layout.code}
                            className={`flex flex-col justify-between rounded-xl border transition-all duration-150 hover:shadow-md ${
                                layout.is_active ? 'border-slate-200 bg-white' : 'border-slate-200 bg-slate-50/70 opacity-80'
                            }`}
                        >
                            <div className="p-5">
                                <div className="flex items-start justify-between gap-2">
                                    <div>
                                        <h3 className="font-semibold text-slate-900">{layout.name}</h3>
                                        <span className="font-mono text-xs text-slate-500">{layout.code}</span>
                                    </div>
                                    <div className="flex items-center gap-1.5">
                                        <StatusBadge
                                            status={layout.is_active ? 'active' : 'inactive'}
                                            label={layout.is_active ? 'Active' : 'Inactive'}
                                        />
                                        {layout.is_system && (
                                            <span className="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                                System
                                            </span>
                                        )}
                                    </div>
                                </div>

                                <p className="mt-2 line-clamp-2 text-xs text-slate-600">
                                    {layout.description || 'Standard invoice format configuration.'}
                                </p>

                                <div className="mt-4 flex flex-wrap gap-2 text-xs">
                                    <span className="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 font-medium text-indigo-700">
                                        {layout.paper_size_label}
                                    </span>
                                    <span className="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 font-medium text-slate-700">
                                        {layout.orientation}
                                    </span>
                                </div>
                            </div>

                            {/* Actions Footer */}
                            <div className="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 bg-slate-50/50 p-3">
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    onClick={() => setPreviewLayout(layout)}
                                >
                                    Preview
                                </Button>

                                <div className="flex items-center gap-1.5">
                                    {canManage && (
                                        <>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => void handleSetDefault(layout, 'tenant')}
                                                title="Set as Tenant Default"
                                            >
                                                Tenant Default
                                            </Button>

                                            {auth.organizationUnit && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => void handleSetDefault(layout, 'organization_unit')}
                                                    title="Set as Branch Default"
                                                >
                                                    Branch Default
                                                </Button>
                                            )}

                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => handleEdit(layout)}
                                            >
                                                Edit
                                            </Button>

                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => handleDuplicate(layout)}
                                                title="Duplicate Layout"
                                            >
                                                Clone
                                            </Button>

                                            {!layout.is_system && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-red-600 hover:text-red-700"
                                                    onClick={() => setDeletingLayout(layout)}
                                                >
                                                    Delete
                                                </Button>
                                            )}
                                        </>
                                    )}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Preview Modal */}
            <InvoiceLayoutPreviewModal
                open={previewLayout !== null}
                layout={previewLayout}
                onClose={() => setPreviewLayout(null)}
            />

            {/* Editor Drawer */}
            <InvoiceLayoutEditorDrawer
                open={drawerOpen}
                layout={editingLayout}
                onClose={() => {
                    setDrawerOpen(false);
                    setEditingLayout(null);
                }}
                onSaved={handleSaved}
            />

            {/* Delete Confirmation Dialog */}
            <ConfirmDialog
                open={deletingLayout !== null}
                title="Delete Invoice Layout"
                message={`Are you sure you want to delete the layout "${deletingLayout?.name}"? Invoices previously created will not be altered, but this layout will no longer be available.`}
                confirmLabel="Delete Layout"
                danger
                loading={deleteLoading}
                onConfirm={() => void handleDeleteConfirm()}
                onCancel={() => setDeletingLayout(null)}
            />

            {/* Printer Assignments Modal */}
            <InvoicePrinterAssignmentsModal
                open={printerModalOpen}
                layouts={layouts ?? []}
                organizationUnitId={auth.organizationUnit?.id != null ? Number(auth.organizationUnit.id) : null}
                organizationUnitName={auth.organizationUnit?.name}
                onClose={() => setPrinterModalOpen(false)}
            />
        </div>
    );
}
