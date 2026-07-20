import { beforeEach, describe, expect, it, vi } from 'vitest';
import type {
    RentalAgreementPayload,
    RentalAssignmentPayload,
    RentalCustodyPayload,
    RentalRateVersionPayload,
    RentalReplacementPayload,
    RentalRunningChartPayload,
} from './vehicleRentalTypes';
import {
    defaultRentalRate,
    normalizeRatesForAgreement,
    rentalRateCodeOptions,
} from './components/RentalRateEditor';

const apiClientMocks = vi.hoisted(() => ({
    delete: vi.fn(),
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
}));

vi.mock('@/shared/api/apiClient', () => ({ apiClient: apiClientMocks }));

import {
    activateRentalAgreement,
    cancelRentalAssignment,
    cancelRentalCalculation,
    closeRentalAgreement,
    createRentalAgreement,
    createRentalAssignment,
    createRentalCalculation,
    createRentalRateVersion,
    createRentalRunningChart,
    deleteRentalAgreement,
    deleteRentalAssignment,
    finalizeRentalRunningChart,
    getRentalAgreement,
    getRentalAgreementFormLookups,
    getRentalAssignment,
    listRentalAgreementLookup,
    listRentalAssignmentLookup,
    recordRentalCustody,
    replaceRentalAssignment,
    reverseRentalRunningChart,
    updateRentalAgreement,
    updateRentalAssignment,
    updateRentalRunningChart,
} from './vehicleRentalApi';

const endpoint = '/api/v1/vehicle-rental';

beforeEach(() => {
    vi.clearAllMocks();
    const response = { data: { data: { id: 1 } } };
    apiClientMocks.delete.mockResolvedValue(response);
    apiClientMocks.get.mockResolvedValue(response);
    apiClientMocks.post.mockResolvedValue(response);
    apiClientMocks.put.mockResolvedValue(response);
});

describe('Vehicle Rental CRUD workflow contracts', () => {
    it('uses the Vehicle Rental-owned agreement form lookup boundary', async () => {
        await getRentalAgreementFormLookups();
        expect(apiClientMocks.get).toHaveBeenCalledWith(`${endpoint}/lookups/agreement-form`, { signal: undefined });
    });

    it('uses workflow-owned agreement and assignment lookup endpoints', async () => {
        await listRentalAgreementLookup('assignment', { kind: 'customer' });
        await listRentalAgreementLookup('calculation', { search: 'VRA' });
        await listRentalAssignmentLookup('assignment-source', { search: 'CAR' });
        await listRentalAssignmentLookup('running-chart', { page: 1 });

        expect(apiClientMocks.get).toHaveBeenCalledWith(`${endpoint}/lookups/assignment-agreements`, { params: { kind: 'customer' }, signal: undefined });
        expect(apiClientMocks.get).toHaveBeenCalledWith(`${endpoint}/lookups/calculation-agreements`, { params: { search: 'VRA' }, signal: undefined });
        expect(apiClientMocks.get).toHaveBeenCalledWith(`${endpoint}/lookups/assignment-sources`, { params: { search: 'CAR' }, signal: undefined });
        expect(apiClientMocks.get).toHaveBeenCalledWith(`${endpoint}/lookups/running-chart-assignments`, { params: { page: 1 }, signal: undefined });
    });

    it('maps agreement read and lifecycle actions to canonical endpoints with optimistic versions', async () => {
        const agreement = {} as RentalAgreementPayload;
        const rates = {} as RentalRateVersionPayload;

        await getRentalAgreement(11);
        await createRentalAgreement(agreement);
        await updateRentalAgreement(11, agreement);
        await deleteRentalAgreement(11, 5);
        await createRentalRateVersion(11, rates);
        await activateRentalAgreement(11, 3);
        await closeRentalAgreement(11, 4);

        expect(apiClientMocks.get).toHaveBeenCalledWith(`${endpoint}/agreements/11`, { signal: undefined });
        expect(apiClientMocks.post).toHaveBeenCalledWith(`${endpoint}/agreements`, agreement);
        expect(apiClientMocks.put).toHaveBeenCalledWith(`${endpoint}/agreements/11`, agreement);
        expect(apiClientMocks.delete).toHaveBeenCalledWith(`${endpoint}/agreements/11`, { data: { expected_version: 5 } });
        expect(apiClientMocks.post).toHaveBeenCalledWith(`${endpoint}/agreements/11/rate-versions`, rates);
        expect(apiClientMocks.post).toHaveBeenCalledWith(`${endpoint}/agreements/11/activate`, { expected_version: 3 });
        expect(apiClientMocks.post).toHaveBeenCalledWith(`${endpoint}/agreements/11/close`, { expected_version: 4 });
    });

    it('maps assignment read edit delete custody replacement and cancellation to the backend lifecycle', async () => {
        const assignment = {} as RentalAssignmentPayload;
        const custody = {} as RentalCustodyPayload;
        const replacement = {} as RentalReplacementPayload;

        await getRentalAssignment(21);
        await createRentalAssignment(assignment);
        await updateRentalAssignment(21, assignment, 4);
        await deleteRentalAssignment(21, 5);
        await recordRentalCustody(21, custody);
        await replaceRentalAssignment(21, replacement);
        await cancelRentalAssignment(21, 6);

        expect(apiClientMocks.get).toHaveBeenCalledWith(`${endpoint}/assignments/21`, { signal: undefined });
        expect(apiClientMocks.post).toHaveBeenCalledWith(`${endpoint}/assignments`, assignment);
        expect(apiClientMocks.put).toHaveBeenCalledWith(`${endpoint}/assignments/21`, { ...assignment, expected_version: 4 });
        expect(apiClientMocks.delete).toHaveBeenCalledWith(`${endpoint}/assignments/21`, { data: { expected_version: 5 } });
        expect(apiClientMocks.post).toHaveBeenCalledWith(`${endpoint}/assignments/21/custody`, custody);
        expect(apiClientMocks.post).toHaveBeenCalledWith(`${endpoint}/assignments/21/replace`, replacement);
        expect(apiClientMocks.post).toHaveBeenCalledWith(`${endpoint}/assignments/21/cancel`, { expected_version: 6 });
    });

    it('maps running-chart and calculation transitions without inventing delete endpoints', async () => {
        const chart = {
            starts_at: '2026-07-01T08:00:00+05:30',
            ends_at: '2026-07-01T18:00:00+05:30',
        } as RentalRunningChartPayload;

        await createRentalRunningChart(chart);
        await updateRentalRunningChart(31, chart);
        await finalizeRentalRunningChart(31, 2);
        await reverseRentalRunningChart(31, 3, 'Correction required');
        await createRentalCalculation(41, { period_start: '2026-07-01', period_end: '2026-07-31' });
        await cancelRentalCalculation(51, 4, 'Recalculate period');

        expect(apiClientMocks.post).toHaveBeenCalledWith(`${endpoint}/running-charts`, chart);
        expect(apiClientMocks.put).toHaveBeenCalledWith(`${endpoint}/running-charts/31`, chart);
        expect(apiClientMocks.post).toHaveBeenCalledWith(`${endpoint}/running-charts/31/finalize`, { expected_version: 2 });
        expect(apiClientMocks.post).toHaveBeenCalledWith(`${endpoint}/running-charts/31/reverse`, { expected_version: 3, reason: 'Correction required' });
        expect(apiClientMocks.post).toHaveBeenCalledWith(`${endpoint}/agreements/41/calculations`, { period_start: '2026-07-01', period_end: '2026-07-31' });
        expect(apiClientMocks.post).toHaveBeenCalledWith(`${endpoint}/calculations/51/cancel`, { expected_version: 4, reason: 'Recalculate period' });
    });

    it('uses billing basis as the source of truth and preserves an activatable commercial base', () => {
        expect(defaultRentalRate('daily').unit).toBe('day');
        expect(defaultRentalRate('monthly').unit).toBe('month');
        expect(normalizeRatesForAgreement([
            { code: 'base_rental', unit: 'day', rate: '100', is_taxable: false },
        ], 'customer', 'monthly')).toEqual([
            { code: 'base_rental', unit: 'month', rate: '100', is_taxable: false },
        ]);

        const normalized = normalizeRatesForAgreement([
            { code: 'front_ac', unit: 'day', rate: '10', is_taxable: false },
            { code: 'excess_km', unit: 'kilometre', rate: '2', is_taxable: true },
        ], 'owner', 'daily');
        expect(normalized[0]).toEqual(defaultRentalRate('daily'));
        expect(normalized[1]).toEqual({ code: 'excess_km', unit: 'kilometre', rate: '2', is_taxable: true });
    });

    it('allows a single base row to switch to AC pricing while preventing mixed commercial bases', () => {
        const baseOnly = [{ code: 'base_rental' as const, unit: 'day' as const, rate: '100', is_taxable: false }];
        expect(rentalRateCodeOptions(baseOnly, 0, 'customer', 'daily').map((option) => option.value))
            .toEqual(expect.arrayContaining(['base_rental', 'non_ac', 'front_ac', 'dual_ac']));

        const acRates = [
            { code: 'non_ac' as const, unit: 'day' as const, rate: '100', is_taxable: false },
            { code: 'front_ac' as const, unit: 'day' as const, rate: '120', is_taxable: false },
        ];
        expect(rentalRateCodeOptions(acRates, 0, 'customer', 'daily').map((option) => option.value))
            .not.toContain('base_rental');
    });
});
