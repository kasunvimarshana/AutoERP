import type { VehicleMetadata, VehiclePayload, VehicleRecord } from './types';
import type { VehicleFormValues } from './schemas';

export function humanizeVehicleStatus(value: string | null | undefined) {
    if (!value) {
        return '-';
    }

    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

export function vehicleTitle(vehicle: Pick<VehicleRecord, 'make' | 'model' | 'registration_number' | 'vin'>) {
    return [vehicle.make, vehicle.model].filter(Boolean).join(' ') || vehicle.registration_number || vehicle.vin || 'Vehicle';
}

export function readVehicleMetadata(vehicle: VehicleRecord | null | undefined): VehicleMetadata {
    return vehicle?.metadata ?? {};
}

export function toVehiclePayload(tenantId: number, values: VehicleFormValues): VehiclePayload {
    const metadata: VehicleMetadata = {};

    if (values.color) {
        metadata.color = values.color;
    }

    if (values.engine_number) {
        metadata.engine_number = values.engine_number;
    }

    if (values.notes) {
        metadata.notes = values.notes;
    }

    return {
        tenant_id: tenantId,
        org_unit_id: values.org_unit_id ?? null,
        customer_id: values.customer_id ?? null,
        supplier_id: values.supplier_id ?? null,
        ownership_type: values.ownership_type,
        asset_code: values.asset_code ?? null,
        make: values.make,
        model: values.model,
        year: values.year ?? null,
        vin: values.vin ?? null,
        registration_number: values.registration_number ?? null,
        chassis_number: values.chassis_number ?? null,
        fuel_type: values.fuel_type,
        transmission: values.transmission,
        odometer: values.odometer ?? null,
        rental_status: values.rental_status,
        service_status: values.service_status,
        next_maintenance_due_at: values.next_maintenance_due_at ?? null,
        primary_image_path: values.primary_image_path ?? null,
        metadata: Object.keys(metadata).length ? metadata : null,
        is_active: values.is_active,
    };
}
