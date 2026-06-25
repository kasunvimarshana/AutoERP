import { useCallback, useState, type FormEvent } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { FormActions } from '@/shared/components/FormActions';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { useUnsavedChanges } from '@/shared/hooks/useUnsavedChanges';
import type { ApiCollection, ListParams } from '@/shared/types/api';
import type { NamedResource } from '@/shared/types/common';
import {
    createVehicleCategory,
    createVehicleMake,
    createVehicleModel,
    createVehicleType,
    listVehicleCategories,
    listVehicleMakes,
    listVehicleModels,
    listVehicleTypes,
    searchVehicleCategories,
    searchVehicleMakes,
    updateVehicleCategory,
    updateVehicleMake,
    updateVehicleModel,
    updateVehicleType,
} from './vehicleApi';
import { VehicleMakeSelect } from './components/VehicleMakeSelect';
import type {
    VehicleCategory,
    VehicleCategoryPayload,
    VehicleMake,
    VehicleMakePayload,
    VehicleModel,
    VehicleModelPayload,
    VehicleType,
    VehicleTypePayload,
} from './vehicleTypes';

export type VehicleMasterKind = 'makes' | 'types' | 'categories' | 'models';

type VehicleMasterPayload = VehicleMakePayload | VehicleTypePayload | VehicleCategoryPayload | VehicleModelPayload;
type VehicleMasterRow = (VehicleMake | VehicleType | VehicleCategory | VehicleModel) & {
    description?: string | null;
    is_active: boolean;
    sort_order?: number;
    make?: NamedResource | null;
    parent?: NamedResource | null;
    year_from?: number | null;
    year_to?: number | null;
};

interface MasterApi {
    list: (params: ListParams, signal?: AbortSignal) => Promise<ApiCollection<VehicleMasterRow>>;
    create: (payload: VehicleMasterPayload) => Promise<VehicleMasterRow>;
    update: (id: number, payload: VehicleMasterPayload) => Promise<VehicleMasterRow>;
}

const masterApis: Record<VehicleMasterKind, MasterApi> = {
    makes: {
        list: listVehicleMakes as MasterApi['list'],
        create: (payload) => createVehicleMake(payload as VehicleMakePayload),
        update: (id, payload) => updateVehicleMake(id, payload as VehicleMakePayload),
    },
    types: {
        list: listVehicleTypes as MasterApi['list'],
        create: (payload) => createVehicleType(payload as VehicleTypePayload),
        update: (id, payload) => updateVehicleType(id, payload as VehicleTypePayload),
    },
    categories: {
        list: listVehicleCategories as MasterApi['list'],
        create: (payload) => createVehicleCategory(payload as VehicleCategoryPayload),
        update: (id, payload) => updateVehicleCategory(id, payload as VehicleCategoryPayload),
    },
    models: {
        list: listVehicleModels as MasterApi['list'],
        create: (payload) => createVehicleModel(payload as VehicleModelPayload),
        update: (id, payload) => updateVehicleModel(id, payload as VehicleModelPayload),
    },
};

const pageCopy: Record<VehicleMasterKind, { title: string; description: string; addLabel: string; empty: string }> = {
    makes: {
        title: 'Vehicle Makes',
        description: 'Manage vehicle brands used by models and vehicle records.',
        addLabel: 'Add Make',
        empty: 'No vehicle makes found.',
    },
    types: {
        title: 'Vehicle Types',
        description: 'Manage physical classifications such as car, van, bus, truck, motorcycle, or equipment.',
        addLabel: 'Add Type',
        empty: 'No vehicle types found.',
    },
    categories: {
        title: 'Vehicle Categories',
        description: 'Manage business and operational groupings for the vehicle master.',
        addLabel: 'Add Category',
        empty: 'No vehicle categories found.',
    },
    models: {
        title: 'Vehicle Models',
        description: 'Manage make-specific vehicle models and supported year ranges.',
        addLabel: 'Add Model',
        empty: 'No vehicle models found.',
    },
};

export default function VehicleMasterDataPage({ kind }: { kind: VehicleMasterKind }) {
    return <VehicleMasterDataContent key={kind} kind={kind} />;
}

function VehicleMasterDataContent({ kind }: { kind: VehicleMasterKind }) {
    const { confirm, confirmDialog } = useConfirmDialog();
    const config = pageCopy[kind];
    const api = masterApis[kind];
    const [search, setSearch] = useState('');
    const [active, setActive] = useState('');
    const [makeFilter, setMakeFilter] = useState<VehicleMake | null>(null);
    const [page, setPage] = useState(1);
    const [refreshKey, setRefreshKey] = useState(0);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [busyId, setBusyId] = useState<number | null>(null);
    const [editing, setEditing] = useState<VehicleMasterRow | null>(null);
    const [formOpen, setFormOpen] = useState(false);
    const [formVersion, setFormVersion] = useState(0);
    const [formDirty, setFormDirty] = useState(false);
    const [formSubmitting, setFormSubmitting] = useState(false);
    const debouncedSearch = useDebounce(search);

    const result = useApi((signal) => api.list({
        search: debouncedSearch || undefined,
        is_active: active === '' ? undefined : active === 'true',
        vehicle_make_id: kind === 'models' ? makeFilter?.id : undefined,
        page,
        per_page: 25,
    }, signal), [active, debouncedSearch, kind, makeFilter?.id, page, refreshKey], true, true);

    const openCreate = () => {
        setFormDirty(false);
        setFormSubmitting(false);
        setActionError(null);
        setEditing(null);
        setFormVersion((value) => value + 1);
        setFormOpen(true);
    };

    const openEdit = (row: VehicleMasterRow) => {
        setFormDirty(false);
        setFormSubmitting(false);
        setActionError(null);
        setEditing(row);
        setFormVersion((value) => value + 1);
        setFormOpen(true);
    };

    const closeForm = useCallback(() => {
        setFormDirty(false);
        setFormSubmitting(false);
        setFormOpen(false);
        setEditing(null);
    }, []);

    const requestFormClose = useCallback(async () => {
        if (formSubmitting) return;
        if (formDirty && !await confirm({
            title: 'Discard unsaved changes?',
            message: 'Leave this form and discard all unsaved changes?',
            confirmLabel: 'Discard changes',
        })) return;
        closeForm();
    }, [closeForm, confirm, formDirty, formSubmitting]);

    const saveForm = useCallback(async (payload: VehicleMasterPayload) => {
        if (editing) {
            await api.update(editing.id, payload);
        } else {
            await api.create(payload);
        }
        setFormOpen(false);
        setEditing(null);
        setRefreshKey((value) => value + 1);
    }, [api, editing]);

    const toggleActive = async (row: VehicleMasterRow) => {
        setBusyId(row.id);
        setActionError(null);
        try {
            await api.update(row.id, payloadFromRow(kind, row, { isActive: !row.is_active }));
            setRefreshKey((value) => value + 1);
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusyId(null);
        }
    };

    const hasFilters = Boolean(search || active || makeFilter);
    const columns = masterColumns(kind, openEdit, toggleActive, busyId);

    return (
        <>
            <ContentHeader title={config.title} description={config.description} actions={<Button onClick={openCreate}>{config.addLabel}</Button>} />
            <div className={`mb-4 grid gap-3 ${kind === 'models' ? 'md:grid-cols-3' : 'md:grid-cols-2'}`}>
                <Input
                    type="search"
                    label="Search"
                    placeholder="Code or name"
                    value={search}
                    onChange={(event) => { setSearch(event.target.value); setPage(1); }}
                />
                <Select
                    label="Status"
                    value={active}
                    options={[{ value: 'true', label: 'Active' }, { value: 'false', label: 'Inactive' }]}
                    placeholder="Any status"
                    onChange={(event) => { setActive(event.target.value); setPage(1); }}
                />
                {kind === 'models' && (
                    <VehicleMakeSelect value={makeFilter} onChange={(value) => { setMakeFilter(value); setPage(1); }} />
                )}
            </div>
            {hasFilters && (
                <div className="mb-4 flex flex-wrap items-center gap-2 text-sm text-slate-600">
                    <span>Filters applied</span>
                    <Button
                        variant="ghost"
                        className="min-h-9 px-3 py-1.5"
                        onClick={() => {
                            setSearch('');
                            setActive('');
                            setMakeFilter(null);
                            setPage(1);
                        }}
                    >
                        Clear filters
                    </Button>
                </div>
            )}
            <ErrorAlert error={actionError ?? result.error} />
            {result.loading ? (
                <LoadingState label={`Loading ${config.title.toLowerCase()}...`} />
            ) : (
                <>
                    <DataTable
                        rows={result.data?.data ?? []}
                        rowKey={(row) => row.id}
                        columns={columns}
                        emptyMessage={config.empty}
                    />
                    <Pagination meta={result.data?.meta} onPageChange={setPage} />
                </>
            )}

            <Modal open={formOpen} title={editing ? `Edit ${singularLabel(kind)}` : config.addLabel} onClose={() => void requestFormClose()}>
                <VehicleMasterForm
                    key={`${kind}-${editing?.id ?? 'new'}-${formVersion}`}
                    kind={kind}
                    initial={editing}
                    onCancel={requestFormClose}
                    onDirtyChange={setFormDirty}
                    onSubmittingChange={setFormSubmitting}
                    onSubmit={saveForm}
                />
            </Modal>
            {confirmDialog}
        </>
    );
}

function masterColumns(
    kind: VehicleMasterKind,
    onEdit: (row: VehicleMasterRow) => void,
    onToggleActive: (row: VehicleMasterRow) => void,
    busyId: number | null,
): DataColumn<VehicleMasterRow>[] {
    const columns: DataColumn<VehicleMasterRow>[] = [
        { key: 'code', header: 'Code', render: (row) => <span className="font-semibold text-slate-900">{row.code}</span> },
        { key: 'name', header: 'Name', render: (row) => <div><p className="font-medium text-slate-900">{row.name}</p><p className="text-xs text-slate-500">{row.description || '-'}</p></div> },
    ];

    if (kind === 'models') {
        columns.push(
            { key: 'make', header: 'Make', render: (row) => row.make?.name ?? '-' },
            { key: 'years', header: 'Years', render: (row) => yearRange(row) },
        );
    }

    if (kind === 'categories') {
        columns.push({ key: 'parent', header: 'Parent', render: (row) => row.parent?.name ?? '-' });
    }

    if (kind === 'types' || kind === 'categories') {
        columns.push({ key: 'sort', header: 'Sort', render: (row) => row.sort_order ?? 0 });
    }

    columns.push(
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (row) => (
                <div className="flex justify-end gap-2">
                    <Button variant="ghost" onClick={() => onEdit(row)} disabled={busyId === row.id}>Edit</Button>
                    <Button variant="secondary" loading={busyId === row.id} onClick={() => onToggleActive(row)}>
                        {row.is_active ? 'Deactivate' : 'Activate'}
                    </Button>
                </div>
            ),
        },
    );

    return columns;
}

interface VehicleMasterFormProps {
    kind: VehicleMasterKind;
    initial: VehicleMasterRow | null;
    onCancel: () => void;
    onDirtyChange: (dirty: boolean) => void;
    onSubmittingChange: (submitting: boolean) => void;
    onSubmit: (payload: VehicleMasterPayload) => Promise<void>;
}

function VehicleMasterForm({
    kind,
    initial,
    onCancel,
    onDirtyChange,
    onSubmittingChange,
    onSubmit,
}: VehicleMasterFormProps) {
    const [form, setForm] = useState(() => formFromRow(initial));
    const [make, setMake] = useState<VehicleMake | null>(() => namedToMake(initial?.make));
    const [parent, setParent] = useState<VehicleCategory | null>(() => namedToCategory(initial?.parent));
    const [error, setError] = useState<ApiError | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [dirty, setDirty] = useState(false);
    useUnsavedChanges(dirty && !submitting);

    const markDirty = useCallback(() => {
        onDirtyChange(true);
        setDirty(true);
    }, [onDirtyChange]);

    const update = (key: keyof MasterFormState, value: string | boolean) => {
        markDirty();
        setForm((current) => ({ ...current, [key]: value }));
    };

    const submit = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (submitting) return;
        onSubmittingChange(true);
        setSubmitting(true);
        setError(null);
        try {
            await onSubmit(payloadFromForm(kind, form, make, parent));
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            onSubmittingChange(false);
            setSubmitting(false);
        }
    };

    return (
        <form className="space-y-5" onSubmit={submit} onChangeCapture={markDirty}>
            <ErrorAlert error={error} />
            <div className="grid gap-4 md:grid-cols-2">
                {kind === 'models' && (
                    <GenericLookupSelect<VehicleMake>
                        label="Make *"
                        value={make}
                        onChange={(value) => { markDirty(); setMake(value); }}
                        search={searchVehicleMakes}
                        formatLabel={formatNamedResource}
                        error={fieldError(error, 'vehicle_make_id')}
                        required
                        loadOnOpen
                        minSearchLength={0}
                    />
                )}
                {kind === 'categories' && (
                    <GenericLookupSelect<VehicleCategory>
                        label="Parent Category"
                        value={parent}
                        onChange={(value) => { markDirty(); setParent(value); }}
                        search={searchVehicleCategories}
                        formatLabel={formatNamedResource}
                        error={fieldError(error, 'parent_id')}
                        excludeId={initial?.id ?? null}
                        loadOnOpen
                        minSearchLength={0}
                    />
                )}
                <Input
                    label="Code *"
                    value={form.code}
                    onChange={(event) => update('code', event.target.value)}
                    error={fieldError(error, 'code')}
                    required
                />
                <Input
                    label="Name *"
                    value={form.name}
                    onChange={(event) => update('name', event.target.value)}
                    error={fieldError(error, 'name')}
                    required
                />
                {(kind === 'types' || kind === 'categories') && (
                    <Input
                        label="Sort Order"
                        type="number"
                        min={0}
                        value={form.sort_order}
                        onChange={(event) => update('sort_order', event.target.value)}
                        error={fieldError(error, 'sort_order')}
                    />
                )}
                {kind === 'models' && (
                    <>
                        <Input
                            label="Year From"
                            type="number"
                            min={1886}
                            value={form.year_from}
                            onChange={(event) => update('year_from', event.target.value)}
                            error={fieldError(error, 'year_from')}
                        />
                        <Input
                            label="Year To"
                            type="number"
                            min={1886}
                            value={form.year_to}
                            onChange={(event) => update('year_to', event.target.value)}
                            error={fieldError(error, 'year_to')}
                        />
                    </>
                )}
                <label className="flex min-h-10 items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-medium text-slate-700">
                    <input
                        type="checkbox"
                        className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        checked={form.is_active}
                        onChange={(event) => update('is_active', event.target.checked)}
                    />
                    Active
                </label>
                <div className="md:col-span-2">
                    <Textarea
                        label="Description"
                        value={form.description}
                        onChange={(event) => update('description', event.target.value)}
                        error={fieldError(error, 'description')}
                    />
                </div>
            </div>
            <FormActions>
                <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
                <Button type="submit" loading={submitting}>{initial ? `Save ${singularLabel(kind)}` : `Create ${singularLabel(kind)}`}</Button>
            </FormActions>
        </form>
    );
}

interface MasterFormState {
    code: string;
    name: string;
    description: string;
    is_active: boolean;
    sort_order: string;
    year_from: string;
    year_to: string;
}

function formFromRow(row: VehicleMasterRow | null): MasterFormState {
    return {
        code: row?.code ?? '',
        name: row?.name ?? '',
        description: row?.description ?? '',
        is_active: row?.is_active ?? true,
        sort_order: String(row?.sort_order ?? 0),
        year_from: row?.year_from == null ? '' : String(row.year_from),
        year_to: row?.year_to == null ? '' : String(row.year_to),
    };
}

function payloadFromForm(
    kind: VehicleMasterKind,
    form: MasterFormState,
    make: VehicleMake | null,
    parent: VehicleCategory | null,
): VehicleMasterPayload {
    const base = {
        code: form.code.trim(),
        name: form.name.trim(),
        description: nullableText(form.description),
        is_active: form.is_active,
    };

    if (kind === 'types') {
        return { ...base, sort_order: parsePositiveInteger(form.sort_order) };
    }

    if (kind === 'categories') {
        return { ...base, parent_id: parent?.id ?? null, sort_order: parsePositiveInteger(form.sort_order) };
    }

    if (kind === 'models') {
        return {
            ...base,
            vehicle_make_id: make?.id ?? null,
            year_from: parseNullableInteger(form.year_from),
            year_to: parseNullableInteger(form.year_to),
        };
    }

    return base;
}

function payloadFromRow(
    kind: VehicleMasterKind,
    row: VehicleMasterRow,
    overrides: { isActive?: boolean } = {},
): VehicleMasterPayload {
    return payloadFromForm(kind, {
        code: row.code ?? '',
        name: row.name,
        description: row.description ?? '',
        is_active: overrides.isActive ?? row.is_active,
        sort_order: String(row.sort_order ?? 0),
        year_from: row.year_from == null ? '' : String(row.year_from),
        year_to: row.year_to == null ? '' : String(row.year_to),
    }, namedToMake(row.make), namedToCategory(row.parent));
}

function namedToMake(resource?: NamedResource | null): VehicleMake | null {
    return resource ? { id: resource.id, code: resource.code, name: resource.name, is_active: true } : null;
}

function namedToCategory(resource?: NamedResource | null): VehicleCategory | null {
    return resource ? { id: resource.id, code: resource.code, name: resource.name, is_active: true, sort_order: 0 } : null;
}

function nullableText(value: string): string | null {
    const trimmed = value.trim();
    return trimmed === '' ? null : trimmed;
}

function parsePositiveInteger(value: string): number {
    const parsed = Number.parseInt(value, 10);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
}

function parseNullableInteger(value: string): number | null {
    if (value.trim() === '') return null;
    const parsed = Number.parseInt(value, 10);
    return Number.isFinite(parsed) ? parsed : null;
}

function formatNamedResource(resource: NamedResource): string {
    return `${resource.code ?? ''} ${resource.name}`.trim();
}

function singularLabel(kind: VehicleMasterKind): string {
    return ({ makes: 'Make', types: 'Type', categories: 'Category', models: 'Model' } as const)[kind];
}

function yearRange(row: VehicleMasterRow): string {
    if (row.year_from && row.year_to) return `${row.year_from} - ${row.year_to}`;
    if (row.year_from) return `${row.year_from}+`;
    if (row.year_to) return `Up to ${row.year_to}`;
    return '-';
}
