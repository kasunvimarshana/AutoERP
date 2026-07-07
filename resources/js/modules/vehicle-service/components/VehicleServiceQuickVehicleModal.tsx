import { useMemo, useState } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { lookupApi, type VehicleLookupResource } from '@/shared/api/lookupApi';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { Input } from '@/shared/components/Input';
import { Modal } from '@/shared/components/Modal';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { customerTypes, type CustomerPayload } from '@/modules/customer/customerTypes';
import { createCustomer } from '@/modules/customer/customerApi';
import type { NamedResource } from '@/shared/types/common';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { createVehicleWithRelations } from '@/modules/vehicle/vehicleApi';
import { VehicleCategorySelect } from '@/modules/vehicle/components/VehicleCategorySelect';
import { VehicleMakeSelect } from '@/modules/vehicle/components/VehicleMakeSelect';
import { VehicleModelSelect } from '@/modules/vehicle/components/VehicleModelSelect';
import { VehicleTypeSelect } from '@/modules/vehicle/components/VehicleTypeSelect';
import type {
    Vehicle,
    VehicleCategory,
    VehicleMake,
    VehicleModel,
    VehicleOwnershipType,
    VehiclePayload,
    VehicleType,
} from '@/modules/vehicle/vehicleTypes';

type CustomerMode = 'existing' | 'new';

const DEFAULT_CUSTOMER_STATUS = 'active';
const DEFAULT_CUSTOMER_TYPE = 'individual';
const DEFAULT_VEHICLE_STATUS = 'active';
const DEFAULT_DECIMAL_VALUE = '0.000000';
const DEFAULT_ODOMETER_READING = DEFAULT_DECIMAL_VALUE;
const DEFAULT_ODOMETER_UNIT = 'km';
const DEFAULT_CUSTOMER_OWNERSHIP_TYPE: VehicleOwnershipType = 'customer_owned';

export function VehicleServiceQuickVehicleModal({
    open,
    initialVehicleNumber,
    onClose,
    onCreated,
}: {
    open: boolean;
    initialVehicleNumber: string;
    onClose: () => void;
    onCreated: (vehicle: VehicleLookupResource, customer: NamedResource) => void;
}) {
    const [customerMode, setCustomerMode] = useState<CustomerMode>('existing');
    const [existingCustomer, setExistingCustomer] = useState<NamedResource | null>(null);
    const [customerPayload, setCustomerPayload] = useState<CustomerPayload>(defaultCustomerPayload());
    const [vehiclePayload, setVehiclePayload] = useState<VehiclePayload>(defaultVehiclePayload(initialVehicleNumber));
    const [make, setMake] = useState<VehicleMake | null>(null);
    const [model, setModel] = useState<VehicleModel | null>(null);
    const [type, setType] = useState<VehicleType | null>(null);
    const [category, setCategory] = useState<VehicleCategory | null>(null);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    const customerSearch = useMemo(() => lookupApi.customers, []);

    if (!open) return null;

    return (
        <Modal
            open={open}
            title="Register vehicle for this job"
            onClose={onClose}
            closeDisabled={saving}
            closeOnBackdrop={false}
        >
            <form
                className="space-y-5"
                onSubmit={async (event) => {
                    event.preventDefault();
                    if (saving) return;
                    setSaving(true);
                    setError(null);

                    try {
                        const customer = customerMode === 'existing'
                            ? existingCustomer
                            : customerSummary(await createCustomer({
                                ...customerPayload,
                                status: DEFAULT_CUSTOMER_STATUS,
                            }));

                        if (!customer) {
                            setSaving(false);
                            return;
                        }

                        const vehicle = await createVehicleWithRelations({
                            vehicle: {
                                ...vehiclePayload,
                                vehicle_make_id: make?.id ?? null,
                                vehicle_model_id: model?.id ?? null,
                                vehicle_type_id: type?.id ?? null,
                                vehicle_category_id: category?.id ?? null,
                            },
                            documents: [],
                            attributes: [],
                            ownerships: [{
                                owner_type: 'customer',
                                owner_id: customer.id,
                                ownership_type: DEFAULT_CUSTOMER_OWNERSHIP_TYPE,
                                started_at: businessDateInputValue(),
                                is_current: true,
                            }],
                        });

                        onCreated(vehicleLookupResource(vehicle, customer), customer);
                        onClose();
                    } catch (requestError) {
                        setError(toApiError(requestError));
                    } finally {
                        setSaving(false);
                    }
                }}
            >
                <ErrorAlert error={error} title="Vehicle could not be registered" />

                <Panel title="Vehicle details">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Input
                            label="Vehicle Number"
                            value={vehiclePayload.vehicle_number ?? ''}
                            onChange={(event) => setVehiclePayload((current) => ({ ...current, vehicle_number: event.target.value }))}
                            error={vehicleError(error, 'vehicle_number')}
                            required
                        />
                        <Input
                            label="Registration Number"
                            value={vehiclePayload.registration_number ?? ''}
                            onChange={(event) => setVehiclePayload((current) => ({ ...current, registration_number: event.target.value }))}
                            error={vehicleError(error, 'registration_number')}
                        />
                        <Input
                            label="Code"
                            value={vehiclePayload.code ?? ''}
                            onChange={(event) => setVehiclePayload((current) => ({ ...current, code: event.target.value }))}
                            error={vehicleError(error, 'code')}
                        />
                        <Input
                            label="Odometer"
                            value={vehiclePayload.odometer_reading ?? DEFAULT_ODOMETER_READING}
                            onChange={(event) => setVehiclePayload((current) => ({ ...current, odometer_reading: event.target.value }))}
                            error={vehicleError(error, 'odometer_reading')}
                        />
                        <VehicleMakeSelect
                            value={make}
                            onChange={(next) => {
                                const nextId = next?.id ?? null;
                                if (nextId !== (make?.id ?? null)) setModel(null);
                                setMake(next);
                            }}
                            error={vehicleError(error, 'vehicle_make_id')}
                        />
                        <VehicleModelSelect
                            makeId={make?.id}
                            value={model}
                            onChange={setModel}
                            error={vehicleError(error, 'vehicle_model_id')}
                        />
                        <VehicleTypeSelect value={type} onChange={setType} error={vehicleError(error, 'vehicle_type_id')} />
                        <VehicleCategorySelect value={category} onChange={setCategory} error={vehicleError(error, 'vehicle_category_id')} />
                    </div>
                </Panel>

                <Panel title="Customer details">
                    <div className="mb-4 flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant={customerMode === 'existing' ? 'primary' : 'secondary'}
                            onClick={() => setCustomerMode('existing')}
                        >
                            Existing customer
                        </Button>
                        <Button
                            type="button"
                            variant={customerMode === 'new' ? 'primary' : 'secondary'}
                            onClick={() => setCustomerMode('new')}
                        >
                            New customer
                        </Button>
                    </div>

                    {customerMode === 'existing' ? (
                        <GenericLookupSelect
                            label="Customer"
                            value={existingCustomer}
                            onChange={setExistingCustomer}
                            search={customerSearch}
                            formatLabel={(resource) => `${resource.code ?? ''} ${resource.name}`.trim()}
                            error={customerError(error, 'customer_id', 'owner_id', 'ownerships.0.owner_id')}
                            placeholder="Search customer by code or name"
                            loadOnOpen
                            minSearchLength={0}
                            dropdownPlacement="top"
                            required
                        />
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2">
                            <Input
                                label="Customer Code"
                                value={customerPayload.code}
                                onChange={(event) => setCustomerPayload((current) => ({ ...current, code: event.target.value }))}
                                error={customerError(error, 'code', 'customer.code')}
                                required
                            />
                            <Input
                                label="Customer Name"
                                value={customerPayload.name}
                                onChange={(event) => setCustomerPayload((current) => ({ ...current, name: event.target.value }))}
                                error={customerError(error, 'name', 'customer.name')}
                                required
                            />
                            <Select
                                label="Customer Type"
                                value={customerPayload.customer_type}
                                onChange={(event) => setCustomerPayload((current) => ({ ...current, customer_type: event.target.value }))}
                                options={customerTypes.map((value) => ({ value, label: value.replaceAll('_', ' ') }))}
                                error={customerError(error, 'customer_type', 'customer.customer_type')}
                            />
                            <div className="block text-sm text-slate-700">
                                <span className="mb-1.5 block font-medium">Customer Status</span>
                                <div className="flex min-h-10 items-center rounded-lg border border-slate-200 bg-slate-50 px-3 text-slate-600">
                                    Active
                                </div>
                            </div>
                            <Input
                                label="Mobile"
                                value={customerPayload.mobile ?? ''}
                                onChange={(event) => setCustomerPayload((current) => ({ ...current, mobile: event.target.value || null }))}
                                error={customerError(error, 'mobile', 'customer.mobile')}
                            />
                            <Input
                                label="Phone"
                                value={customerPayload.phone ?? ''}
                                onChange={(event) => setCustomerPayload((current) => ({ ...current, phone: event.target.value || null }))}
                                error={customerError(error, 'phone', 'customer.phone')}
                            />
                            <div className="md:col-span-2">
                                <Input
                                    label="Email"
                                    type="email"
                                    value={customerPayload.email ?? ''}
                                    onChange={(event) => setCustomerPayload((current) => ({ ...current, email: event.target.value || null }))}
                                    error={customerError(error, 'email', 'customer.email')}
                                />
                            </div>
                        </div>
                    )}
                </Panel>

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" onClick={onClose} disabled={saving}>Cancel</Button>
                    <Button type="submit" loading={saving}>Save vehicle and continue</Button>
                </div>
            </form>
        </Modal>
    );
}

function defaultCustomerPayload(): CustomerPayload {
    return {
        customer_number: null,
        code: '',
        name: '',
        customer_type: DEFAULT_CUSTOMER_TYPE,
        status: DEFAULT_CUSTOMER_STATUS,
        default_currency_id: null,
        credit_limit: DEFAULT_DECIMAL_VALUE,
        opening_balance: DEFAULT_DECIMAL_VALUE,
        is_credit_allowed: true,
        is_advance_allowed: true,
        is_tax_exempt: false,
        marketing_consent: false,
        preferred_communication_channel: null,
    };
}

function defaultVehiclePayload(vehicleNumber: string): VehiclePayload {
    return {
        vehicle_number: vehicleNumber,
        code: '',
        registration_number: '',
        odometer_reading: DEFAULT_ODOMETER_READING,
        odometer_unit: DEFAULT_ODOMETER_UNIT,
        status: DEFAULT_VEHICLE_STATUS,
    };
}

function customerSummary(customer: { id: number; code: string; name: string }): NamedResource {
    return {
        id: customer.id,
        code: customer.code,
        name: customer.name,
    };
}

function vehicleLookupResource(vehicle: Vehicle, customer: NamedResource): VehicleLookupResource {
    return {
        id: vehicle.id,
        code: vehicle.vehicle_number || vehicle.code || vehicle.registration_number || `VEH-${vehicle.id}`,
        name: vehicle.registration_number || vehicle.vehicle_number || vehicle.code || `Vehicle ${vehicle.id}`,
        registration_number: vehicle.registration_number ?? null,
        make: vehicle.make ?? null,
        model: vehicle.model ?? null,
        current_ownerships: vehicle.current_ownerships?.map((ownership) => ({
            owner_type: ownership.owner_type,
            owner_id: ownership.owner_id ?? null,
            owner: ownership.owner ?? null,
        })),
        current_customer: vehicle.current_customer
            ? {
                id: vehicle.current_customer.id,
                code: vehicle.current_customer.code,
                name: vehicle.current_customer.name,
            }
            : {
                id: customer.id,
                code: customer.code ?? '',
                name: customer.name,
            },
        odometer_reading: vehicle.odometer_reading ?? null,
        odometer_unit: vehicle.odometer_unit ?? null,
    };
}

function vehicleError(error: ApiError | null, key: string): string | undefined {
    return fieldError(error, `vehicle.${key}`) ?? fieldError(error, key) ?? undefined;
}

function customerError(error: ApiError | null, ...keys: string[]): string | undefined {
    for (const key of keys) {
        const message = fieldError(error, key);
        if (message) return message;
    }

    return undefined;
}
