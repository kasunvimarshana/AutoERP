import { describe, expect, it } from 'vitest';
import { flattenVehicleServiceLines } from '../api/lines';
import type { VehicleServiceJob, VehicleServiceJobLine } from '../vehicleServiceTypes';
import { requiresWorkforceBeforeInspect, withJobLines } from './VehicleServiceJobDetailPage';

describe('Vehicle Service job-line state', () => {
    it('keeps combo children visible when the line endpoint returns nested rows', () => {
        const child = line({ id: 2, parent_line_id: 1, is_billable: false, unit_price: '125.000000' });
        const parent = line({ id: 1, children: [child] });

        expect(flattenVehicleServiceLines([parent]).map((row) => row.id)).toEqual([1, 2]);
        expect(flattenVehicleServiceLines([parent])[1].unit_price).toBe('125.000000');
    });

    it('applies authoritative job totals returned by the line mutation', () => {
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
        ], 4, {
            subtotal: '1000.000000',
            line_discount_total: '50.000000',
            job_discount_base: '950.000000',
            job_discount_amount: '0.000000',
            discount_total: '50.000000',
            tax_total: '100.000000',
            charge_total: '25.000000',
            grand_total: '1075.000000',
            commission_cost_total: '0.000000',
            net_after_commission: '1075.000000',
        });

        expect(updated.row_version).toBe(4);
        expect(updated.subtotal).toBe('1000.000000');
        expect(updated.discount_total).toBe('50.000000');
        expect(updated.tax_total).toBe('100.000000');
        expect(updated.charge_total).toBe('25.000000');
        expect(updated.grand_total).toBe('1075.000000');
    });

    it('blocks inspect when labour-assignable lines have no active workforce assignment', () => {
        const labourLine = line({
            id: 4,
            line_source_type: 'labour_item',
            is_employee_assignable: true,
            employee_assignments: [],
        });

        expect(requiresWorkforceBeforeInspect([labourLine])).toBe(true);
    });

    it('allows inspect when at least one active workforce assignment exists', () => {
        const labourLine = line({
            id: 5,
            line_source_type: 'labour_item',
            is_employee_assignable: true,
            employee_assignments: [{
                id: 9,
                vehicle_service_job_line_id: 5,
                employee_id: 3,
                employee: { id: 3, code: 'EMP-003', name: 'Alex' },
                role_type: 'technician',
                assigned_hours: '1.000000',
                rate: '0.000000',
                commission_type: 'none',
                commission_value: '0.000000',
                commission_amount: '0.000000',
                status: 'assigned',
            }],
        });

        expect(requiresWorkforceBeforeInspect([labourLine])).toBe(false);
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
