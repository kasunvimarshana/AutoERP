import { generateVehicleCode } from './vehicleApi';
import type { VehiclePayload } from './vehicleTypes';

export const DEFAULT_VEHICLE_STATUS = 'active';
export const DEFAULT_ODOMETER_READING = '0.000000';
export const DEFAULT_ODOMETER_UNIT = 'km';

export function defaultVehiclePayload(registrationNumber = ''): VehiclePayload {
    return {
        vehicle_number: null,
        code: '',
        registration_number: registrationNumber,
        chassis_number: '',
        engine_number: '',
        vin_number: '',
        manufacture_year: null,
        registration_date: '',
        color: '',
        fuel_type: '',
        transmission_type: '',
        odometer_reading: DEFAULT_ODOMETER_READING,
        odometer_unit: DEFAULT_ODOMETER_UNIT,
        fuel_level: '',
        status: DEFAULT_VEHICLE_STATUS,
        notes: '',
    };
}

export async function loadVehicleCreationDefaults(signal: AbortSignal): Promise<{ code: string }> {
    return { code: await generateVehicleCode(signal) };
}
