import type { Vehicle, VehicleOwnership } from '../types/vehicle.types';

export const vehicleRecords: Vehicle[] = [
    {
        category: 'Van',
        code: 'VEH-DEMO-001',
        color: 'White',
        currentOdometer: '38450',
        fuelType: 'Diesel',
        id: 'veh-demo-001',
        insuranceExpiry: '2026-12-31',
        make: 'Toyota',
        model: 'HiAce',
        registrationExpiry: '2026-12-31',
        registrationNumber: 'WP DEMO-001',
        rentalEnabled: true,
        seatingCapacity: '12',
        serviceEnabled: true,
        status: 'active',
        transmission: 'Automatic',
        usageProfile: 'dual',
        vin: 'DEMO-VIN-001',
        year: '2022',
    },
];

export const vehicleOwnerships: VehicleOwnership[] = [
    {
        id: 'own-demo-001',
        isCurrent: true,
        notes: 'Company fleet legal ownership.',
        ownerDisplayName: 'Internal Company',
        ownerName: 'Internal Company',
        ownerType: 'company',
        ownershipRole: 'legal_owner',
        ownershipType: 'own',
        startDate: '2026-01-01',
        vehicleId: 'veh-demo-001',
    },
];
