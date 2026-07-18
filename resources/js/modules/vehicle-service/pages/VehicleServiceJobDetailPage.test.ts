import { describe, expect, it } from 'vitest';
import { flattenVehicleServiceLines } from '../api/lines';
import type { VehicleServiceJob, VehicleServiceJobLine } from '../vehicleServiceTypes';
import { withJobLines } from './VehicleServiceJobDetailPage';

describe('Vehicle Service job-line state', () => {
    it('keeps combo children visible when the line endpoint returns nested rows', () => {
        const child = line({ id: 2, parent_line_id: 1, is_billable: false, unit_price: '125.000000' });
        const parent = line({ id: 1, children: [child] });

        expect(flattenVehicleServiceLines([parent]).map((row) => row.id)).toEqual([1, 2]);
        expect(flattenVehicleServiceLines([parent])[1].unit_price).toBe('125.000000');
    });

    it('recalculates local job totals from active billable lines only', () => {
        const billableParent = line({
            id: 1,
            quantity: '2.000000',
            unit_price: '500.000000',
            discount_amount: '50.000000',
            tax_amount: '100.000000',
            charge_amount: '25.000000',
            line_total: '1075.000000',
        });
        const nonBillableChild = line({
            id: 2,
            parent_line_id: 1,
            is_billable: false,
            unit_price: '125.000000',
            line_total: '125.000000',
        });
        const cancelledBillableLine = line({
            id: 3,
            status: 'cancelled',
            unit_price: '900.000000',
            line_total: '900.000000',
        });

        const updated = withJobLines({ id: 9 } as VehicleServiceJob, [
            billableParent,
            nonBillableChild,
            cancelledBillableLine,
        ], 4);

        expect(updated.row_version).toBe(4);
        expect(updated.subtotal).toBe('1000.000000');
        expect(updated.discount_total).toBe('50.000000');
        expect(updated.tax_total).toBe('100.000000');
        expect(updated.charge_total).toBe('25.000000');
        expect(updated.grand_total).toBe('1075.000000');
    });
});

function line(overrides: Partial<VehicleServiceJobLine> = {}): VehicleServiceJobLine {
    return {
        id: 1,
        parent_line_id: null,
        line_number: 1,
        line_source_type: 'combo_parent',
        item_id: 1,
        item: { id: 1, code: 'COMBO', name: 'Service combo' },
        item_variant_id: null,
        item_variant: null,
        uom_id: 1,
        uom: { id: 1, code: 'JOB', name: 'Job' },
        description: 'Service combo',
        quantity: '1.000000',
        unit_cost: '0.000000',
        unit_price: '500.000000',
        discount_calculation_type: 'fixed',
        discount_rate: '0.000000',
        discount_amount: '0.000000',
        tax_calculation_type: 'fixed',
        tax_rate: '0.000000',
        tax_amount: '0.000000',
        charge_calculation_type: 'fixed',
        charge_rate: '0.000000',
        charge_amount: '0.000000',
        line_total: '500.000000',
        is_inventory_tracked: false,
        is_customer_supplied: false,
        is_external: false,
        is_billable: true,
        is_employee_assignable: false,
        inventory_movement_id: null,
        status: 'pending',
        children: [],
        employee_assignments: [],
        ...overrides,
    };
}
