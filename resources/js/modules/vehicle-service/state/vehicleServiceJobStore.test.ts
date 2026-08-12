import { describe, expect, it } from 'vitest';
import type { VehicleServiceJobLine } from '../vehicleServiceTypes';
import { createVehicleServiceJobStore } from './vehicleServiceJobStore';

const line: VehicleServiceJobLine = {
    id: 7,
    line_number: 1,
    line_source_type: 'labour_item',
    description: 'Technician',
    quantity: '1.000000',
    unit_cost: '100.000000',
    unit_price: '0.000000',
    discount_rate: '0.000000',
    discount_amount: '0.000000',
    tax_rate: '0.000000',
    tax_amount: '0.000000',
    charge_rate: '0.000000',
    charge_amount: '0.000000',
    line_total: '0.000000',
    is_inventory_tracked: false,
    is_customer_supplied: false,
    is_external: false,
    is_billable: false,
    is_employee_assignable: true,
    status: 'planned',
};

describe('vehicle service job store', () => {
    it('replaces workforce lines from a line mutation while preserving supervisor context', () => {
        const store = createVehicleServiceJobStore(7);
        store.getState().replaceWorkforce({
            lines: [],
            rowVersion: 4,
            supervisor: { id: 2, code: 'SUP-2', name: 'Supervisor' },
        });

        store.getState().replaceWorkforceLines({ lines: [line], rowVersion: 5 });

        expect(store.getState().workforce).toEqual({
            lines: [line],
            rowVersion: 5,
            supervisor: { id: 2, code: 'SUP-2', name: 'Supervisor' },
        });
    });
});
