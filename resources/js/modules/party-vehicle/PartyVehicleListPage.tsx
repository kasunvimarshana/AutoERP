import { useState, type ComponentType } from 'react';
import { Link } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import type { ApiCollection } from '@/shared/types/api';
import type { PartyVehicleRelationship, VehicleOwnerType } from '@/shared/types/partyVehicle';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import type { VehicleOwnershipListParams } from '@/modules/vehicle/vehicleOwnershipApi';

interface LookupProps<T> { value: T | null; onChange: (value: T | null) => void }

export function PartyVehicleListPage<P extends { id: number }, V extends { id: number }>({
    ownerType, title, createPath, supersedePath, permissions, PartyLookup, VehicleLookup,
    list, setCurrent, clearCurrent, end,
}: {
    ownerType: Exclude<VehicleOwnerType, 'company'>;
    title: string;
    createPath: string;
    supersedePath: (id: number) => string;
    permissions: { view: string; manage: string };
    PartyLookup: ComponentType<LookupProps<P>>;
    VehicleLookup: ComponentType<LookupProps<V>>;
    list: (params: VehicleOwnershipListParams, signal?: AbortSignal) => Promise<ApiCollection<PartyVehicleRelationship>>;
    setCurrent: (id: number, expectedVersion: number) => Promise<PartyVehicleRelationship>;
    clearCurrent: (id: number, expectedVersion: number) => Promise<PartyVehicleRelationship>;
    end: (id: number, expectedVersion: number, endedAt: string) => Promise<PartyVehicleRelationship>;
}) {
    const auth = useAuth();
    const canManage = hasPermission(auth, permissions.manage);
    const [search, setSearch] = useState('');
    const [party, setParty] = useState<P | null>(null);
    const [vehicle, setVehicle] = useState<V | null>(null);
    const [current, setCurrentFilter] = useState('');
    const [status, setStatus] = useState('');
    const [sort, setSort] = useState<'started_at' | 'ended_at' | 'created_at'>('started_at');
    const [direction, setDirection] = useState<'asc' | 'desc'>('desc');
    const [page, setPage] = useState(1);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [busy, setBusy] = useState<number | null>(null);
    const debounced = useDebounce(search);
    const { confirm, confirmDialog } = useConfirmDialog();
    const result = useApi((signal) => list({
        search: debounced,
        owner_type: ownerType,
        owner_id: party?.id,
        vehicle_id: vehicle?.id,
        is_current: current === '' ? undefined : current === 'true',
        status: status === 'active' || status === 'ended' ? status : undefined,
        sort,
        direction,
        page,
        per_page: 25,
    }, signal), [debounced, ownerType, party?.id, vehicle?.id, current, status, sort, direction, page]);

    const mutate = async (row: PartyVehicleRelationship, action: 'set' | 'clear' | 'end') => {
        const effectiveDate = businessDateInputValue();
        const message = action === 'end'
            ? `End this relationship effective ${effectiveDate}? Historical facts remain retained.`
            : action === 'set'
                ? 'Set this relationship as the current relationship? Any other current relationship of the same owner type will be cleared.'
                : 'Clear the current marker while retaining the active relationship?';
        if (!await confirm({ title: 'Confirm ownership change', message, confirmLabel: action === 'end' ? 'End relationship' : 'Confirm', danger: action !== 'set' })) return;
        setBusy(row.id);
        setActionError(null);
        try {
            if (action === 'set') await setCurrent(row.id, row.row_version);
            else if (action === 'clear') await clearCurrent(row.id, row.row_version);
            else await end(row.id, row.row_version, effectiveDate);
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusy(null);
        }
    };

    const columns: DataColumn<PartyVehicleRelationship>[] = [
        { key: 'party', header: ownerType === 'customer' ? 'Customer' : 'Supplier', render: (row) => <><div className="font-semibold text-slate-900">{row.owner.name}</div><div className="text-xs text-slate-500">{row.owner.code}</div></> },
        { key: 'vehicle', header: 'Vehicle', render: (row) => <><div className="font-semibold text-slate-900">{row.vehicle.registration_number ?? row.vehicle.number}</div><div className="text-xs text-slate-500">{[row.vehicle.make, row.vehicle.model, row.vehicle.chassis_number].filter(Boolean).join(' · ')}</div></> },
        { key: 'type', header: 'Ownership', render: (row) => row.ownership_type.replaceAll('_', ' ') },
        { key: 'current', header: 'Status', render: (row) => <StatusBadge status={row.is_current ? 'current' : row.ended_at ? 'ended' : 'active'} /> },
        { key: 'dates', header: 'Validity', render: (row) => <span>{row.started_at.slice(0, 10)} — {row.ended_at?.slice(0, 10) ?? 'Open'}</span> },
        { key: 'actions', header: '', className: 'text-right', render: (row) => <div className="flex justify-end gap-2">{canManage && <Link className="font-semibold text-sky-700" to={supersedePath(row.id)}>Supersede</Link>}{!row.is_current && canManage && !row.ended_at && <Button variant="ghost" disabled={busy === row.id} onClick={() => void mutate(row, 'set')}>Set Current</Button>}{row.is_current && canManage && <Button variant="ghost" disabled={busy === row.id} onClick={() => void mutate(row, 'clear')}>Clear</Button>}{!row.ended_at && canManage && <Button variant="ghost" disabled={busy === row.id} onClick={() => void mutate(row, 'end')}>End</Button>}</div> },
    ];

    return <>
        <ContentHeader title={title} description={`View current and historical ${ownerType} vehicle ownership. Corrections create immutable replacement revisions.`} actions={canManage ? <LinkButton to={createPath}>Create relationship</LinkButton> : undefined} />
        <div className="mb-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4"><Input type="search" label="Search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} placeholder="Owner, registration, chassis" /><PartyLookup value={party} onChange={(value) => { setParty(value); setPage(1); }} /><VehicleLookup value={vehicle} onChange={(value) => { setVehicle(value); setPage(1); }} /><Select label="Current status" value={current} onChange={(event) => { setCurrentFilter(event.target.value); setPage(1); }} options={[{ value: 'true', label: 'Current' }, { value: 'false', label: 'Not current' }]} /><Select label="Relationship status" value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }} options={[{ value: 'active', label: 'Active' }, { value: 'ended', label: 'Ended' }]} /><Select label="Sort" value={sort} onChange={(event) => setSort(event.target.value as typeof sort)} options={[{ value: 'started_at', label: 'Start date' }, { value: 'ended_at', label: 'End date' }, { value: 'created_at', label: 'Created' }]} /><Select label="Direction" value={direction} onChange={(event) => setDirection(event.target.value as typeof direction)} options={[{ value: 'desc', label: 'Newest first' }, { value: 'asc', label: 'Oldest first' }]} /></div>
        <ErrorAlert error={actionError ?? result.error} />
        {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} emptyMessage={`No ${ownerType} vehicle relationships found.`} />}
        <Pagination meta={result.data?.meta} onPageChange={setPage} />
        {confirmDialog}
    </>;
}
