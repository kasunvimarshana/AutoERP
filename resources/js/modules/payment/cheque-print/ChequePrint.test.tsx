/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ChequePreviewCanvas } from './ChequePreviewCanvas';
import ChequePrintPreviewPage from './ChequePrintPreviewPage';
import ChequeTemplateListPage from './ChequeTemplateListPage';
import type { ChequePrintPayment, ChequeTemplate } from './chequePrintTypes';

const api = vi.hoisted(() => ({
    listChequeTemplates: vi.fn(),
    previewCheque: vi.fn(),
    updateChequeTemplate: vi.fn(),
    markChequePrinted: vi.fn(),
    deleteChequeTemplate: vi.fn(),
    getChequeTemplate: vi.fn(),
    createChequeTemplate: vi.fn(),
}));

vi.mock('./chequePrintApi', async (importOriginal) => {
    const actual = await importOriginal<typeof import('./chequePrintApi')>();
    return {
        ...actual,
        listChequeTemplates: api.listChequeTemplates,
        previewCheque: api.previewCheque,
        updateChequeTemplate: api.updateChequeTemplate,
        markChequePrinted: api.markChequePrinted,
        deleteChequeTemplate: api.deleteChequeTemplate,
        getChequeTemplate: api.getChequeTemplate,
        createChequeTemplate: api.createChequeTemplate,
        getChequeExportUrl: vi.fn((paymentId, lineId, format) => `/api/v1/payments/${paymentId}/lines/${lineId}/cheque-print/export/${format}`),
    };
});

function mockTemplate(): ChequeTemplate {
    return {
        id: 10,
        row_version: 3,
        tenant_id: 1,
        template_name: 'Commercial Bank Voucher',
        bank_name: 'Commercial Bank',
        page_width_mm: '210',
        page_height_mm: '99',
        date_x_mm: '155',
        date_y_mm: '12',
        payee_x_mm: '25',
        payee_y_mm: '34',
        amount_x_mm: '160',
        amount_y_mm: '45',
        amount_words_x_mm: '25',
        amount_words_y_mm: '55',
        cheque_number_x_mm: '15',
        cheque_number_y_mm: '12',
        font_size: '12',
        font_family: 'Arial',
        is_default: true,
        is_active: true,
        can_delete: false,
        metadata: {
            date_format: 'd/m/Y',
            date_letter_spacing: 2,
            crossing_type: 'ac_payee',
            crossing_x_mm: 15,
            crossing_y_mm: 10,
            bearer_strike_through: true,
            bearer_x_mm: 155,
            bearer_y_mm: 36,
            amount_prefix: '**',
            amount_suffix: '**',
            currency_code: 'LKR',
            memo_x_mm: 25,
            memo_y_mm: 75,
        },
    };
}

function mockPayment(): ChequePrintPayment {
    return {
        id: 50,
        payment_number: 'PAY-5050',
        payment_method: 'cheque',
        payee_name: 'Ceylon Auto Supplies',
        amount: '45000.00',
        formatted_amount: '**45,000.00**',
        amount_in_words: 'Forty Five Thousand Rupees Only',
        amount_words_line_1: 'Forty Five Thousand',
        amount_words_line_2: 'Rupees Only',
        cheque_number: '009821',
        cheque_date: '2026-11-27',
        formatted_cheque_date: '27/11/2026',
        date_digits: ['2', '7', '1', '1', '2', '0', '2', '6'],
        currency_code: 'LKR',
        crossing_type: 'ac_payee',
        crossing_text: 'A/C PAYEE ONLY',
        show_bearer_strike: true,
        memo_text: 'Payment for invoice INV-1002',
        status: 'approved',
    };
}

describe('ChequePrint Component Suite', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('ChequePreviewCanvas', () => {
        it('renders accurate layout elements, formatted figures, payee and crossing', () => {
            const template = mockTemplate();
            const payment = mockPayment();
            const onSelect = vi.fn();

            render(
                <ChequePreviewCanvas
                    template={template}
                    payment={payment}
                    showGrid={true}
                    activeField="payee"
                    onSelectField={onSelect}
                />,
            );

            expect(screen.getByText('Ceylon Auto Supplies')).toBeInTheDocument();
            expect(screen.getByText('**45,000.00**')).toBeInTheDocument();
            expect(screen.getByText('Forty Five Thousand')).toBeInTheDocument();
            expect(screen.getByText('Rupees Only')).toBeInTheDocument();
            expect(screen.getByText('A/C PAYEE ONLY')).toBeInTheDocument();
            expect(screen.getByText('009821')).toBeInTheDocument();
            expect(screen.getByText('Payment for invoice INV-1002')).toBeInTheDocument();

            // Date digits boxed
            expect(screen.getAllByText('2').length).toBeGreaterThan(0);

            // Clicking payee calls onSelectField
            fireEvent.click(screen.getByText('Ceylon Auto Supplies'));
            expect(onSelect).toHaveBeenCalledWith('payee');
        });
    });

    describe('ChequeTemplateListPage', () => {
        it('displays templates and shows locked status for default templates', async () => {
            api.listChequeTemplates.mockResolvedValue({
                data: [
                    mockTemplate(),
                    {
                        ...mockTemplate(),
                        id: 11,
                        template_name: 'Hatton National Bank Cheque',
                        bank_name: 'Hatton National Bank',
                        is_default: false,
                        can_delete: true,
                    },
                ],
            });

            render(
                <MemoryRouter>
                    <ChequeTemplateListPage />
                </MemoryRouter>,
            );

            await waitFor(() => {
                expect(screen.getAllByText('Commercial Bank Voucher').length).toBeGreaterThan(0);
                expect(screen.getAllByText('Hatton National Bank Cheque').length).toBeGreaterThan(0);
            });

            expect(screen.getAllByText('Default').length).toBeGreaterThan(0);
            expect(screen.getAllByText('Locked').length).toBeGreaterThan(0);
            expect(screen.getAllByText('Delete').length).toBeGreaterThan(0);
        });
    });

    describe('ChequePrintPreviewPage', () => {
        it('marks cheque as printed even when coordinates were not adjusted (testing bug fix)', async () => {
            const user = userEvent.setup();
            const template = mockTemplate();
            const payment = mockPayment();

            api.listChequeTemplates.mockResolvedValue({ data: [template] });
            api.previewCheque.mockResolvedValue({
                payment,
                template,
            });
            api.markChequePrinted.mockResolvedValue({
                id: 1,
                payment_id: 50,
                cheque_template_id: 10,
                printed_at: '2026-11-27T08:00:00Z',
                print_status: 'printed',
            });

            render(
                <MemoryRouter initialEntries={['/payments/50/lines/1/cheque-print']}>
                    <Routes>
                        <Route
                            path="/payments/:paymentId/lines/:lineId/cheque-print"
                            element={<ChequePrintPreviewPage />}
                        />
                    </Routes>
                </MemoryRouter>,
            );

            await waitFor(() => {
                expect(screen.getByText('Ceylon Auto Supplies')).toBeInTheDocument();
            });

            const markBtn = screen.getByRole('button', { name: /mark printed/i });
            expect(markBtn).toBeEnabled();

            await user.click(markBtn);

            await waitFor(() => {
                expect(api.markChequePrinted).toHaveBeenCalledWith(50, 1, 10, '');
                expect(screen.getByText(/Cheque successfully recorded as printed/i)).toBeInTheDocument();
            });
        });

        it('saves alignment with expected_version concurrency check', async () => {
            const user = userEvent.setup();
            const template = mockTemplate();
            const payment = mockPayment();

            api.listChequeTemplates.mockResolvedValue({ data: [template] });
            api.previewCheque.mockResolvedValue({
                payment,
                template,
            });
            api.updateChequeTemplate.mockResolvedValue({
                ...template,
                row_version: 4,
            });

            render(
                <MemoryRouter initialEntries={['/payments/50/lines/1/cheque-print']}>
                    <Routes>
                        <Route
                            path="/payments/:paymentId/lines/:lineId/cheque-print"
                            element={<ChequePrintPreviewPage />}
                        />
                    </Routes>
                </MemoryRouter>,
            );

            await waitFor(() => {
                expect(screen.getByText('Ceylon Auto Supplies')).toBeInTheDocument();
            });

            // Date X input is the first 'X (mm)' input in the calibration panel
            const xInputs = screen.getAllByLabelText('X (mm)');
            const dateXInput = xInputs[0];
            await user.clear(dateXInput);
            await user.type(dateXInput, '170');

            const saveBtn = screen.getByRole('button', { name: /save alignment to template/i });
            expect(saveBtn).toBeEnabled();

            await user.click(saveBtn);

            await waitFor(() => {
                expect(api.updateChequeTemplate).toHaveBeenCalledWith(10, expect.objectContaining({
                    date_x_mm: '170',
                    expected_version: 3,
                }));
            });
        });
    });
});
