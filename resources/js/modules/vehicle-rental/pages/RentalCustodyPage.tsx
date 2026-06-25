import { useState, type FormEvent } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import type { NamedResource } from '@/shared/types/common';
import { businessDateTimeInputValue } from '@/shared/utils/businessDate';
import { readableRelation } from '@/shared/utils/object';
import { RentalAllocationLookupSelect } from '../components/RentalLookups';
import { RentalPage } from '../components/RentalPage';
import {
    confirmRentalCustodyEvent,
    createRentalCustodyEvent,
    listRentalCustodyEvents,
} from '../vehicleRentalApi';
import { vehicleRentalPermissions } from '../vehicleRentalPermissions';
import type { RentalCustodyEvent } from '../vehicleRentalTypes';

const eventOptions = [
    'owner_to_company',
    'company_to_customer',
    'customer_to_company',
    'company_to_owner',
    'replacement_out',
    'replacement_in',
    'internal_transfer',
];

const emptyForm = () => ({
    event_type: 'company_to_customer',
    occurred_at: businessDateTimeInputValue(),
    odometer: '0',
    fuel_level_percent: '',
    location: '',
    from_role: 'company',
    to_role: 'customer',
    condition_summary: '',
    damage_summary: '',
});

export default function RentalCustodyPage() {
    const auth = useAuth();
    const canManage = hasPermission(auth, vehicleRentalPermissions.custodyManage);
    const [allocation, setAllocation] = useState<NamedResource | null>(null);
    const [refresh, setRefresh] = useState(0);
    const [error, setError] = useState<ApiError | null>(null);
    const [saving, setSaving] = useState(false);
    const [form, setForm] = useState(emptyForm);

    const result = useApi(
        (signal) => listRentalCustodyEvents(
            { vehicle_allocation_id: allocation?.id, per_page: 50 },
            signal,
        ),
        [allocation?.id, refresh],
    );

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (!allocation) return;

        setSaving(true);
        setError(null);
        try {
            await createRentalCustodyEvent(allocation.id, {
                ...form,
                fuel_level_percent: form.fuel_level_percent || null,
                location: form.location || null,
                condition_summary: form.condition_summary || null,
                damage_summary: form.damage_summary || null,
            });
            setForm(emptyForm());
            setRefresh((value) => value + 1);
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    };

    const confirm = async (id: number) => {
        setError(null);
        try {
            await confirmRentalCustodyEvent(id);
            setRefresh((value) => value + 1);
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
        }
    };

    const columns: DataColumn<RentalCustodyEvent>[] = [
        { key: 'event', header: 'Event', render: (row) => row.event_number },
        { key: 'vehicle', header: 'Vehicle', render: (row) => readableRelation(row.vehicle) },
        { key: 'type', header: 'Type', render: (row) => row.event_type.replaceAll('_', ' ') },
        { key: 'roles', header: 'Custody', render: (row) => `${row.from_role} → ${row.to_role}` },
        { key: 'odometer', header: 'Odometer', render: (row) => row.odometer },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        {
            key: 'actions',
            header: '',
            render: (row) => canManage && row.status === 'draft' ? (
                <Button variant="secondary" onClick={() => void confirm(row.id)}>
                    Confirm
                </Button>
            ) : null,
        },
    ];

    return (
        <RentalPage>
            <ContentHeader
                title="Vehicle custody"
                description="Record owner, company and customer handovers against a selected allocation with condition, fuel and odometer evidence."
            />
            <ErrorAlert error={error ?? result.error} />
            <Panel title="Context">
                <RentalAllocationLookupSelect
                    value={allocation}
                    onChange={setAllocation}
                    required={canManage}
                />
            </Panel>
            {canManage && (
                <Panel title="Record custody event" className="mt-5">
                    <form onSubmit={submit}>
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <Select
                                label="Event type"
                                value={form.event_type}
                                onChange={(event) => setForm({ ...form, event_type: event.target.value })}
                                options={eventOptions.map((value) => ({
                                    value,
                                    label: value.replaceAll('_', ' '),
                                }))}
                            />
                            <Input
                                label="Occurred at"
                                type="datetime-local"
                                required
                                value={form.occurred_at}
                                onChange={(event) => setForm({ ...form, occurred_at: event.target.value })}
                            />
                            <Input
                                label="Odometer"
                                type="number"
                                min="0"
                                step="0.000001"
                                required
                                value={form.odometer}
                                onChange={(event) => setForm({ ...form, odometer: event.target.value })}
                            />
                            <Input
                                label="Fuel level %"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                value={form.fuel_level_percent}
                                onChange={(event) => setForm({ ...form, fuel_level_percent: event.target.value })}
                            />
                            <Select
                                label="From"
                                value={form.from_role}
                                onChange={(event) => setForm({ ...form, from_role: event.target.value })}
                                options={['owner', 'company', 'customer'].map((value) => ({ value, label: value }))}
                            />
                            <Select
                                label="To"
                                value={form.to_role}
                                onChange={(event) => setForm({ ...form, to_role: event.target.value })}
                                options={['owner', 'company', 'customer'].map((value) => ({ value, label: value }))}
                            />
                            <Input
                                label="Location"
                                value={form.location}
                                onChange={(event) => setForm({ ...form, location: event.target.value })}
                            />
                        </div>
                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            <Textarea
                                label="Condition"
                                value={form.condition_summary}
                                onChange={(event) => setForm({ ...form, condition_summary: event.target.value })}
                            />
                            <Textarea
                                label="Damage"
                                value={form.damage_summary}
                                onChange={(event) => setForm({ ...form, damage_summary: event.target.value })}
                            />
                        </div>
                        <div className="mt-4 flex justify-end">
                            <Button type="submit" loading={saving} disabled={!allocation || !form.occurred_at}>
                                Save custody event
                            </Button>
                        </div>
                    </form>
                </Panel>
            )}
            <div className="mt-5">
                {result.loading ? (
                    <LoadingState />
                ) : (
                    <DataTable
                        rows={result.data?.data ?? []}
                        columns={columns}
                        rowKey={(row) => row.id}
                        emptyMessage={allocation ? 'No custody events recorded for this allocation.' : 'Select an allocation to review custody events.'}
                    />
                )}
            </div>
        </RentalPage>
    );
}
