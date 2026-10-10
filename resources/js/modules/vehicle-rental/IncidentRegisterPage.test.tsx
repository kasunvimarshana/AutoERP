import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import IncidentRegisterPage from './IncidentRegisterPage';
import { IncidentStatus, IncidentType, IncidentReviewAction, type RentalIncident } from './incidentEvidence';
import { listIncidentVehicleUseOptions, listRentalIncidents, recordRentalIncident, reviewRentalIncident } from './incidentEvidenceApi';

vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({
        roles: [],
        permissions: [
            'vehicle-rental.incidents.view',
            'vehicle-rental.incidents.record',
            'vehicle-rental.incidents.review',
        ],
        permissionsLoaded: true,
    }),
}));
vi.mock('./incidentEvidenceApi', () => ({
    listIncidentVehicleUseOptions: vi.fn(),
    listRentalIncidents: vi.fn(),
    recordRentalIncident: vi.fn(),
    reviewRentalIncident: vi.fn(),
}));

const assignment = {
    id: 27, row_version: 4, vehicle_label: 'CAR-101',
    customer_agreement_reference: 'CUSTOMER-101',
    customer_party_name: 'Example Customer',
};
const incident: RentalIncident = {
    id: 12, reference: 'RIN-TEST-12', row_version: 1,
    status: IncidentStatus.Recorded, incident_type: IncidentType.Fuel,
    occurred_on: '2026-09-07', vehicle_use_id: 27,
    running_chart_id: null, running_chart: null,
    evidence_reference: 'RECEIPT-27',
    description: 'Receipt supplied by driver',
    vehicle: { label: 'CAR-101', customer_agreement: 'CUSTOMER-101', owner_agreement: null },
    reviewed_at: null,
};

beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(listIncidentVehicleUseOptions).mockResolvedValue({ data: [assignment] });
    vi.mocked(listRentalIncidents).mockResolvedValue({ data: [incident] });
    vi.mocked(recordRentalIncident).mockResolvedValue(incident);
    vi.mocked(reviewRentalIncident).mockResolvedValue({
        ...incident, row_version: 2, status: IncidentStatus.Confirmed,
    });
});

describe('Rental incident evidence register', () => {
    it('records factual evidence against the selected assignment revision', async () => {
        render(<IncidentRegisterPage />);
        expect(await screen.findByText('CAR-101 · Fuel')).toBeInTheDocument();
        expect(await screen.findByRole('option', {
            name: 'CAR-101 — CUSTOMER-101 — Example Customer',
        })).toBeInTheDocument();

        fireEvent.change(screen.getByLabelText('Vehicle assignment'), { target: { value: String(assignment.id) } });
        fireEvent.change(screen.getByLabelText('Incident category'), { target: { value: IncidentType.Fuel } });
        fireEvent.change(screen.getByLabelText('Business date of incident'), { target: { value: '2026-09-07' } });
        fireEvent.change(screen.getByLabelText('Evidence / document reference'), { target: { value: ' RECEIPT-27 ' } });
        fireEvent.change(screen.getByLabelText('What happened? Record the factual evidence.'), { target: { value: ' Receipt supplied by driver ' } });
        fireEvent.click(screen.getByRole('button', { name: 'Record evidence' }));

        await waitFor(() => expect(recordRentalIncident).toHaveBeenCalledWith({
            vehicle_use_id: 27,
            expected_use_version: 4,
            incident_type: IncidentType.Fuel,
            occurred_on: '2026-09-07',
            evidence_reference: 'RECEIPT-27',
            description: 'Receipt supplied by driver',
        }));
        expect(screen.getByText(/Confirmation verifies the record only/)).toBeInTheDocument();
    });

    it('confirms reviewed evidence without issuing a monetary adjustment', async () => {
        render(<IncidentRegisterPage />);
        await screen.findByText('CAR-101 · Fuel');
        fireEvent.click(screen.getByRole('button', { name: 'Review evidence' }));
        fireEvent.change(screen.getByLabelText('Review reason (required)'), {
            target: { value: 'Checked receipt and journey evidence' },
        });
        fireEvent.click(screen.getByRole('button', { name: 'Confirm evidence' }));
        await waitFor(() => expect(reviewRentalIncident).toHaveBeenCalledWith(
            incident, IncidentReviewAction.Confirm, 'Checked receipt and journey evidence',
        ));
        expect(recordRentalIncident).not.toHaveBeenCalled();
    });
});
