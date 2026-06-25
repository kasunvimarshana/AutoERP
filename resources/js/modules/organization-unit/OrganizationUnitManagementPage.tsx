import { useMemo, useState } from 'react';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { TabPanel, Tabs, type TabItem } from '@/shared/components/Tabs';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { OrganizationUnitDetailDrawer } from './components/OrganizationUnitDetailDrawer';
import { OrganizationUnitEditorModal } from './components/OrganizationUnitEditorModal';
import { OrganizationUnitTypePanel } from './components/OrganizationUnitTypePanel';
import { organizationUnitApi, type OrganizationUnitSummary } from './organizationUnitApi';
import { organizationUnitPermissions } from './organizationUnitPermissions';

type PageTab = 'units' | 'types';

export default function OrganizationUnitManagementPage() {
    const auth = useAuth();
    const { confirm, confirmDialog } = useConfirmDialog();
    const [activeTab, setActiveTab] = useState<PageTab>('units');
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('current');
    const [page, setPage] = useState(1);
    const [editing, setEditing] = useState<OrganizationUnitSummary | 'new' | null>(null);
    const [selected, setSelected] = useState<OrganizationUnitSummary | null>(null);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [busyId, setBusyId] = useState<number | null>(null);
    const debouncedSearch = useDebounce(search);

    const canCreate = hasPermission(auth, organizationUnitPermissions.create);
    const canUpdate = hasPermission(auth, organizationUnitPermissions.update);
    const canActivate = hasPermission(auth, organizationUnitPermissions.activate);
    const canDeactivate = hasPermission(auth, organizationUnitPermissions.deactivate);
    const canRetire = hasPermission(auth, organizationUnitPermissions.retire);
    const canViewTypes = hasPermission(auth, organizationUnitPermissions.typesView);
    const needsTypes = canViewTypes || canCreate || canUpdate;

    const units = useApi((signal) => organizationUnitApi.list({
        search: debouncedSearch || undefined,
        is_active: status === 'active' ? true : status === 'inactive' ? false : undefined,
        include_retired: status === 'retired' ? true : undefined,
        page,
        per_page: 20,
    }, signal), [debouncedSearch, page, status]);
    const types = useApi((signal) => organizationUnitApi.listTypes(signal), [], needsTypes);

    const visibleUnits = useMemo(() => {
        const rows = units.data?.data ?? [];
        return status === 'retired' ? rows.filter((unit) => unit.lifecycle_status === 'retired') : rows;
    }, [status, units.data?.data]);
    const tabs = useMemo<TabItem<PageTab>[]>(() => [
        { id: 'units', label: 'Organization units' },
        ...(canViewTypes ? [{ id: 'types' as const, label: 'Hierarchy types' }] : []),
    ], [canViewTypes]);

    const lifecycleAction = async (unit: OrganizationUnitSummary, action: 'activate' | 'deactivate' | 'retire') => {
        const options = lifecycleConfirmation(unit, action);
        if (!await confirm(options)) return;

        setBusyId(unit.id);
        setActionError(null);
        try {
            const updated = action === 'activate'
                ? await organizationUnitApi.activate(unit)
                : action === 'deactivate'
                    ? await organizationUnitApi.deactivate(unit)
                    : await organizationUnitApi.retire(unit);
            if (selected?.id === updated.id) setSelected(updated);
            units.reload();
        } catch (caught: unknown) {
            setActionError(toApiError(caught));
        } finally {
            setBusyId(null);
        }
    };

    const actions = (unit: OrganizationUnitSummary) => (
        <div className="flex flex-wrap justify-end gap-2">
            <Button variant="secondary" className="min-h-8 px-3 py-1 text-xs" onClick={() => setSelected(unit)}>View</Button>
            {canUpdate && unit.lifecycle_status !== 'retired' && (
                <Button variant="secondary" className="min-h-8 px-3 py-1 text-xs" onClick={() => setEditing(unit)}>Edit</Button>
            )}
            {canDeactivate && unit.lifecycle_status === 'active' && !unit.is_root && (
                <Button
                    variant="secondary"
                    className="min-h-8 px-3 py-1 text-xs"
                    loading={busyId === unit.id}
                    onClick={() => void lifecycleAction(unit, 'deactivate')}
                >
                    Deactivate
                </Button>
            )}
            {canActivate && unit.lifecycle_status === 'inactive' && (
                <Button
                    variant="secondary"
                    className="min-h-8 px-3 py-1 text-xs"
                    loading={busyId === unit.id}
                    onClick={() => void lifecycleAction(unit, 'activate')}
                >
                    Activate
                </Button>
            )}
            {canRetire && unit.lifecycle_status === 'inactive' && !unit.is_root && (
                <Button
                    variant="danger"
                    className="min-h-8 px-3 py-1 text-xs"
                    loading={busyId === unit.id}
                    onClick={() => void lifecycleAction(unit, 'retire')}
                >
                    Retire
                </Button>
            )}
        </div>
    );

    const columns: DataColumn<OrganizationUnitSummary>[] = [
        {
            key: 'unit',
            header: 'Organization unit',
            render: (unit) => (
                <button type="button" className="text-left" onClick={() => setSelected(unit)}>
                    <span className="block font-semibold text-sky-700 hover:underline">{unit.name}</span>
                    <span className="block text-xs text-slate-500">{unit.code} · {unit.path}</span>
                </button>
            ),
        },
        { key: 'type', header: 'Type', render: (unit) => unit.type?.name ?? 'Not assigned' },
        { key: 'parent', header: 'Parent', render: (unit) => unit.parent?.name ?? 'Tenant root' },
        { key: 'level', header: 'Level', render: (unit) => unit.depth },
        { key: 'status', header: 'Status', render: (unit) => <StatusBadge status={unit.lifecycle_status} /> },
        { key: 'actions', header: 'Actions', className: 'text-right', mobile: false, render: actions },
    ];

    const saveUnit = (saved: OrganizationUnitSummary) => {
        setEditing(null);
        if (selected?.id === saved.id) setSelected(saved);
        units.reload();
    };

    return (
        <>
            <ContentHeader
                title="Organization units"
                description="Manage the tenant hierarchy, branch lifecycle, private branding, and organization-unit documents. Operational work is always bound to an active assigned unit."
                actions={canCreate ? <Button onClick={() => setEditing('new')}>Create organization unit</Button> : undefined}
            />
            <Tabs id="organization-unit-management" tabs={tabs} active={activeTab} onChange={setActiveTab} />
            <TabPanel tabsId="organization-unit-management" tabId="units" active={activeTab}>
                <div className="space-y-5 pt-5">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input
                            type="search"
                            label="Search hierarchy"
                            placeholder="Name, code, or hierarchy path"
                            value={search}
                            onChange={(event) => { setSearch(event.target.value); setPage(1); }}
                        />
                        <Select
                            label="Lifecycle status"
                            value={status}
                            options={[
                                { value: 'current', label: 'Active and inactive' },
                                { value: 'active', label: 'Active only' },
                                { value: 'inactive', label: 'Inactive only' },
                                { value: 'retired', label: 'Retired only' },
                            ]}
                            onChange={(event) => { setStatus(event.target.value); setPage(1); }}
                        />
                    </div>
                    <ErrorAlert error={actionError ?? units.error ?? types.error} />
                    {units.loading ? <LoadingState label="Loading organization hierarchy…" /> : (
                        <DataTable
                            rows={visibleUnits}
                            columns={columns}
                            rowKey={(unit) => unit.id}
                            emptyMessage="No organization units match the selected filters."
                            mobileSummary={(unit) => <button type="button" className="text-left text-sky-700" onClick={() => setSelected(unit)}>{unit.name}</button>}
                            mobileDetails={(unit) => <div className="space-y-1"><p>{unit.code} · {unit.path}</p><p>{unit.type?.name ?? 'No type'} · level {unit.depth}</p></div>}
                            mobileActions={actions}
                            rowBadge={(unit) => <StatusBadge status={unit.lifecycle_status} />}
                        />
                    )}
                    <Pagination meta={units.data?.meta} onPageChange={setPage} />
                </div>
            </TabPanel>
            {canViewTypes && (
                <TabPanel tabsId="organization-unit-management" tabId="types" active={activeTab}>
                    <OrganizationUnitTypePanel
                        types={types.data ?? []}
                        loading={types.loading}
                        error={types.error}
                        onReload={types.reload}
                    />
                </TabPanel>
            )}
            {editing && (
                <OrganizationUnitEditorModal
                    key={editing === 'new' ? 'new' : editing.id}
                    open
                    unit={editing === 'new' ? null : editing}
                    types={types.data ?? []}
                    onClose={() => setEditing(null)}
                    onSaved={saveUnit}
                />
            )}
            <OrganizationUnitDetailDrawer
                unit={selected}
                onClose={() => setSelected(null)}
                onUpdated={(updated) => {
                    setSelected(updated);
                    units.reload();
                }}
            />
            {confirmDialog}
        </>
    );
}

function lifecycleConfirmation(unit: OrganizationUnitSummary, action: 'activate' | 'deactivate' | 'retire') {
    if (action === 'activate') {
        return {
            title: 'Activate organization unit',
            message: `Activate “${unit.name}”? Users with an active assignment can select it for operational work.`,
            confirmLabel: 'Activate',
            danger: false,
        };
    }
    if (action === 'deactivate') {
        return {
            title: 'Deactivate organization unit',
            message: `Deactivate “${unit.name}”? Active child units, user assignments, sessions, and operational references must be resolved first.`,
            confirmLabel: 'Deactivate',
            danger: true,
        };
    }
    return {
        title: 'Retire organization unit',
        message: `Retire “${unit.name}”? This permanently removes it from operational use while retaining historical references. The action is allowed only after every lifecycle blocker is cleared.`,
        confirmLabel: 'Retire permanently',
        danger: true,
    };
}
