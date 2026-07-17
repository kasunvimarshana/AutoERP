import { describe, expect, it } from 'vitest';
import type { CommissionAwareVehicleServiceJobLine } from '../../commissionTypes';
import {
    applyAssignmentCommissionDefault,
    emptyAssignmentForm,
} from './assignmentForm';

const line = (uomCode: string, withDefault = true): CommissionAwareVehicleServiceJobLine => ({
    id: 15,
    line_number: 1,
    line_source_type: 'labour_item',
    item_id: 25,
    item: { id: 25, code: 'LAB-PAINT', name: 'Painting labour' },
    uom_id: 35,
    uom: { id: 35, code: uomCode, name: uomCode === 'HOUR' ? 'Hour' : 'Job' },
    description: 'Painting labour',
    quantity: '1.000000',
    unit_cost: '0.000000',
    unit_price: '10000.000000',
    discount_rate: '0.000000',
    discount_amount: '0.000000',
    tax_rate: '0.000000',
    tax_amount: '0.000000',
    charge_rate: '0.000000',
    charge_amount: '0.000000',
    line_total: '10000.000000',
    is_inventory_tracked: false,
    is_customer_supplied: false,
    is_external: false,
    is_billable: true,
    is_employee_assignable: true,
    status: 'active',
    commission_default: withDefault ? {
        commission_type: 'percentage',
        commission_value: '10.000000',
    } : null,
});

describe('Vehicle Service assignment commission defaults', () => {
    it.each(['HOUR', 'JOB'])('applies the same labor-item rule independently of %s UOM', (uomCode) => {
        const result = applyAssignmentCommissionDefault(
            emptyAssignmentForm(),
            [line(uomCode)],
            15,
        );

        expect(result.commissionType).toBe('percentage');
        expect(result.commissionValue).toBe('10.000000');
    });

    it('keeps the labor-item default independent of the operational assignment role', () => {
        const helperAssignment = applyAssignmentCommissionDefault(
            { ...emptyAssignmentForm(), role: 'helper' },
            [line('JOB')],
            15,
        );
        const inspectorAssignment = applyAssignmentCommissionDefault(
            { ...helperAssignment, role: 'inspector' },
            [line('JOB')],
            15,
        );

        expect(helperAssignment.role).toBe('helper');
        expect(inspectorAssignment.role).toBe('inspector');
        expect(helperAssignment.commissionType).toBe('percentage');
        expect(inspectorAssignment.commissionValue).toBe('10.000000');
    });

    it('fails closed when the selected labor item has no active default', () => {
        const result = applyAssignmentCommissionDefault(
            { ...emptyAssignmentForm(), commissionType: 'fixed', commissionValue: '500.000000' },
            [line('JOB', false)],
            15,
        );

        expect(result.commissionType).toBe('none');
        expect(result.commissionValue).toBe('0.000000');
    });
});
