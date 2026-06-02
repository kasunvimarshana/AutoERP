import { FormEvent, useCallback, useEffect, useRef, useState, type ReactNode } from 'react';
import { ApiError } from '../../../services/api/apiErrors';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Checkbox } from '../../../shared/components/ui/Checkbox';
import { ConfirmDialog } from '../../../shared/components/ui/ConfirmDialog';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { customerApi } from '../../customer/services/customerApi';
import { supplierApi } from '../../supplier/services/supplierApi';
import { vehicleApi } from '../services/vehicleApi';
import type {
    VehicleFieldErrors,
    VehicleOwnership,
    VehicleOwnershipFormInput,
    VehicleOwnershipRole,
    VehicleOwnerType,
    VehicleOwnershipType,
} from '../types/vehicle.types';

type VehicleOwnershipPanelProps = {
    currentOwnership: VehicleOwnership | null;
    onChanged: () => Promise<void> | void;
    ownerships: VehicleOwnership[];
    vehicleId: string;
};

const ownershipTypeOptions: Array<{ label: string; value: VehicleOwnershipType }> = [
    { label: 'Company owned', value: 'own' },
    { label: 'Customer owned', value: 'customer' },
    { label: 'Supplier owned', value: 'supplier' },
    { label: 'Provider owned', value: 'provider' },
    { label: 'Leased', value: 'leased' },
    { label: 'Financed', value: 'financed' },
    { label: 'Partner owned', value: 'partner' },
    { label: 'Internal', value: 'internal' },
    { label: 'External', value: 'external' },
    { label: 'Other', value: 'other' },
];

const ownerTypeOptions: Array<{ label: string; value: VehicleOwnerType }> = [
    { label: 'Company', value: 'company' },
    { label: 'Customer', value: 'customer' },
    { label: 'Supplier', value: 'supplier' },
    { label: 'Provider', value: 'provider' },
    { label: 'Employee', value: 'employee' },
    { label: 'Partner', value: 'partner' },
    { label: 'External party', value: 'external_party' },
    { label: 'Party', value: 'party' },
    { label: 'Other', value: 'other' },
];

const ownershipRoleOptions: Array<{ label: string; value: VehicleOwnershipRole }> = [
    { label: 'Legal owner', value: 'legal_owner' },
    { label: 'Registered owner', value: 'registered_owner' },
    { label: 'Operational owner', value: 'operational_owner' },
    { label: 'Provider', value: 'provider' },
    { label: 'Current holder', value: 'current_holder' },
];

function humanize(value: string) {
    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

function Field({ children, label }: { children: ReactNode; label: string }) {
    return (
        <div className="space-y-2">
            <label className="text-xs font-bold uppercase tracking-wide text-slate-500">{label}</label>
            {children}
        </div>
    );
}

function firstError(errors: VehicleFieldErrors, ...keys: string[]): string | undefined {
    for (const key of keys) {
        const message = errors[key]?.[0];
        if (message) {
            return message;
        }
    }

    return undefined;
}

function formString(formData: FormData, key: string): string {
    return String(formData.get(key) ?? '').trim();
}

function buildOwnershipInput(formData: FormData): VehicleOwnershipFormInput {
    return {
        endDate: formString(formData, 'end_date'),
        isCurrent: formData.get('is_current') === 'true',
        notes: formString(formData, 'notes'),
        ownerId: formString(formData, 'owner_id'),
        ownerName: formString(formData, 'owner_name'),
        ownerType: (formString(formData, 'owner_type') || 'company') as VehicleOwnerType,
        ownershipRole: (formString(formData, 'ownership_role') || 'legal_owner') as VehicleOwnershipRole,
        ownershipType: (formString(formData, 'ownership_type') || 'own') as VehicleOwnershipType,
        partyId: formString(formData, 'party_id'),
        startDate: formString(formData, 'start_date'),
    };
}

export function VehicleOwnershipPanel({ currentOwnership, onChanged, ownerships, vehicleId }: VehicleOwnershipPanelProps) {
    const [confirmEnd, setConfirmEnd] = useState<VehicleOwnership | null>(null);
    const [errors, setErrors] = useState<VehicleFieldErrors>({});
    const [formError, setFormError] = useState('');
    const [isSaving, setIsSaving] = useState(false);
    const [operation, setOperation] = useState('');
    const [ownerType, setOwnerType] = useState<VehicleOwnerType>('company');
    const [ownerOptions, setOwnerOptions] = useState<Array<{ label: string; value: string }>>([]);
    const [ownerSearch, setOwnerSearch] = useState('');
    const [ownerLookupKey, setOwnerLookupKey] = useState('');
    const [isLoadingOwners, setIsLoadingOwners] = useState(false);
    const ownerLookupRequest = useRef(0);
    const canUseOwnerLookup = ownerType === 'customer' || ownerType === 'supplier' || ownerType === 'provider';

    async function handleCreate(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setErrors({});
        setFormError('');
        setIsSaving(true);

        try {
            await vehicleApi.createOwnership(vehicleId, buildOwnershipInput(new FormData(event.currentTarget)));
            event.currentTarget.reset();
            await onChanged();
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
                setFormError(error.message);
            } else {
                setFormError('Unable to save vehicle ownership.');
            }
        } finally {
            setIsSaving(false);
        }
    }

    const loadOwners = useCallback(async (nextOwnerType = ownerType, search = ownerSearch) => {
        if (nextOwnerType !== 'customer' && nextOwnerType !== 'supplier' && nextOwnerType !== 'provider') {
            setOwnerOptions([]);
            setOwnerLookupKey('');
            return;
        }

        const lookupKey = `${nextOwnerType}:${search.trim().toLowerCase()}`;
        if (ownerLookupKey === lookupKey && ownerOptions.length > 0) {
            return;
        }

        const requestId = ownerLookupRequest.current + 1;
        ownerLookupRequest.current = requestId;
        setIsLoadingOwners(true);
        setFormError('');

        try {
            const response = nextOwnerType === 'customer'
                ? await customerApi.lookupCustomers({ perPage: 25, search: search.trim() || undefined })
                : await supplierApi.lookupSuppliers({ perPage: 25, search: search.trim() || undefined });
            if (ownerLookupRequest.current !== requestId) {
                return;
            }
            setOwnerOptions(response.data.map((option) => ({
                label: option.secondary ? `${option.label} - ${option.secondary}` : option.label,
                value: option.id,
            })));
            setOwnerLookupKey(lookupKey);
        } catch (error) {
            if (ownerLookupRequest.current !== requestId) {
                return;
            }
            setFormError(error instanceof Error ? error.message : 'Unable to load owner options.');
        } finally {
            if (ownerLookupRequest.current === requestId) {
                setIsLoadingOwners(false);
            }
        }
    }, [ownerLookupKey, ownerOptions.length, ownerSearch, ownerType]);

    useEffect(() => {
        if (!ownerLookupKey || !canUseOwnerLookup) {
            return undefined;
        }

        const timeout = window.setTimeout(() => {
            void loadOwners(ownerType, ownerSearch);
        }, 250);

        return () => window.clearTimeout(timeout);
    }, [canUseOwnerLookup, loadOwners, ownerLookupKey, ownerSearch, ownerType]);

    async function setCurrent(ownership: VehicleOwnership) {
        setOperation(ownership.id);
        setFormError('');

        try {
            await vehicleApi.setCurrentOwnership(vehicleId, ownership.id);
            await onChanged();
        } catch (error) {
            setFormError(error instanceof Error ? error.message : 'Unable to set current ownership.');
        } finally {
            setOperation('');
        }
    }

    async function endOwnership(ownership: VehicleOwnership) {
        setOperation(ownership.id);
        setFormError('');

        try {
            await vehicleApi.endOwnership(vehicleId, ownership.id, new Date().toISOString().slice(0, 10));
            await onChanged();
        } catch (error) {
            setFormError(error instanceof Error ? error.message : 'Unable to end ownership.');
        } finally {
            setOperation('');
            setConfirmEnd(null);
        }
    }

    return (
        <div className="space-y-5">
            <PreviewPanel
                rows={[
                    { label: 'Current owner', value: currentOwnership?.ownerDisplayName ?? 'No current legal owner returned' },
                    { label: 'Owner type', value: currentOwnership ? humanize(currentOwnership.ownerType) : 'Pending' },
                    { label: 'Ownership type', value: currentOwnership ? humanize(currentOwnership.ownershipType) : 'Pending' },
                    { label: 'Role', value: currentOwnership ? humanize(currentOwnership.ownershipRole) : 'Pending' },
                    { label: 'Started', value: currentOwnership?.startDate ?? 'Pending' },
                ]}
                status={currentOwnership ? 'Current' : 'Missing'}
                subtitle="Ownership is history-aware context. Billing customer, service customer, payer, and rental provider can still be different business parties."
                title="Current Ownership"
            />

            <FormSection description="Create a vehicle ownership or provider context record. System owner types require an owner id; external owners require owner name." title="Add Ownership Record">
                {formError ? <div className="mb-4 rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{formError}</div> : null}
                <form className="grid gap-4 md:grid-cols-2 xl:grid-cols-4" onSubmit={handleCreate}>
                    <Field label="Ownership type">
                        <Select defaultValue="own" name="ownership_type" options={ownershipTypeOptions} />
                        <FieldError message={firstError(errors, 'ownership_type')} />
                    </Field>
                    <Field label="Owner type">
                        <Select
                            name="owner_type"
                            onChange={(event) => {
                                const nextOwnerType = event.target.value as VehicleOwnerType;
                                setOwnerType(nextOwnerType);
                                setOwnerOptions([]);
                                setOwnerSearch('');
                                setOwnerLookupKey('');
                                void loadOwners(nextOwnerType, '');
                            }}
                            options={ownerTypeOptions}
                            value={ownerType}
                        />
                        <FieldError message={firstError(errors, 'owner_type')} />
                    </Field>
                    <Field label="Owner record">
                        <Input
                            disabled={!canUseOwnerLookup}
                            onChange={(event) => setOwnerSearch(event.target.value)}
                            onFocus={() => void loadOwners()}
                            placeholder={canUseOwnerLookup ? 'Search owner by code, name, or contact' : 'Owner lookup not required'}
                            value={ownerSearch}
                        />
                        <Select
                            disabled={!canUseOwnerLookup}
                            name="owner_id"
                            onFocus={() => void loadOwners()}
                            onMouseDown={() => void loadOwners()}
                            options={ownerOptions}
                            placeholder={
                                ownerType === 'customer' || ownerType === 'supplier' || ownerType === 'provider'
                                    ? isLoadingOwners ? 'Loading owners...' : 'Select owner'
                                    : 'Not required'
                            }
                        />
                        <FieldError message={firstError(errors, 'owner_id')} />
                    </Field>
                    <Field label="External owner name">
                        <Input name="owner_name" placeholder="External owner or company" />
                        <FieldError message={firstError(errors, 'owner_name')} />
                    </Field>
                    <Field label="Party id">
                        <Input inputMode="numeric" name="party_id" placeholder="Optional party id" />
                        <FieldError message={firstError(errors, 'party_id')} />
                    </Field>
                    <Field label="Ownership role">
                        <Select defaultValue="legal_owner" name="ownership_role" options={ownershipRoleOptions} />
                        <FieldError message={firstError(errors, 'ownership_role')} />
                    </Field>
                    <Field label="Start date">
                        <Input name="start_date" type="date" />
                        <FieldError message={firstError(errors, 'start_date')} />
                    </Field>
                    <Field label="End date">
                        <Input name="end_date" type="date" />
                        <FieldError message={firstError(errors, 'end_date')} />
                    </Field>
                    <label className="flex h-11 items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800">
                        <Checkbox defaultChecked name="is_current" value="true" />
                        Current for role
                    </label>
                    <div className="md:col-span-2 xl:col-span-3">
                        <Field label="Notes">
                            <Textarea className="min-h-20" name="notes" placeholder="Ownership/provider context notes." />
                            <FieldError message={firstError(errors, 'notes')} />
                        </Field>
                    </div>
                    <div className="md:col-span-2 xl:col-span-4">
                        <Button disabled={isSaving} type="submit" variant="blue">{isSaving ? 'Saving ownership...' : 'Add ownership'}</Button>
                    </div>
                </form>
            </FormSection>

            {ownerships.length === 0 ? (
                <EmptyState description="No ownership history was returned for this vehicle." title="No ownership records" />
            ) : (
                <DataTable
                    columns={[
                        { header: 'Owner', key: 'ownerDisplayName' },
                        { header: 'Owner Type', key: 'ownerType', render: (row) => humanize(row.ownerType) },
                        { header: 'Ownership', key: 'ownershipType', render: (row) => humanize(row.ownershipType) },
                        { header: 'Role', key: 'ownershipRole', render: (row) => humanize(row.ownershipRole) },
                        { header: 'Window', key: 'window', render: (row) => `${row.startDate || 'Pending'} to ${row.endDate || 'Current'}` },
                        { header: 'Status', key: 'isCurrent', render: (row) => <StatusBadge status={row.isCurrent ? 'active' : 'inactive'} /> },
                        {
                            header: 'Actions',
                            key: 'actions',
                            render: (row) => (
                                <div className="flex flex-wrap gap-2">
                                    {!row.isCurrent ? (
                                        <Button disabled={operation === row.id} onClick={() => void setCurrent(row)} variant="secondary">Set current</Button>
                                    ) : null}
                                    {row.isCurrent ? (
                                        <Button disabled={operation === row.id} onClick={() => setConfirmEnd(row)} variant="ghost">End</Button>
                                    ) : null}
                                </div>
                            ),
                        },
                    ]}
                    getRowKey={(row) => row.id}
                    rows={ownerships}
                />
            )}

            <ConfirmDialog
                message={`End ownership record for ${confirmEnd?.ownerDisplayName ?? 'this owner'} as of today?`}
                onCancel={() => setConfirmEnd(null)}
                onConfirm={() => {
                    if (confirmEnd) {
                        void endOwnership(confirmEnd);
                    }
                }}
                open={confirmEnd !== null}
                title="End ownership"
            />
        </div>
    );
}
