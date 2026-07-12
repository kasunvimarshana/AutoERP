import { readFileSync } from 'node:fs';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { RentalInvoiceLookupSelect } from './RentalLookups';

const invoiceApiMocks = vi.hoisted(() => ({
    listInvoices: vi.fn(),
}));

vi.mock('@/modules/invoice/invoiceApi', () => invoiceApiMocks);
vi.mock('@/modules/payment/paymentApi', () => ({ listPaymentMethods: vi.fn() }));
vi.mock('@/modules/tax/taxApi', () => ({ listTaxGroups: vi.fn() }));
vi.mock('@/shared/api/referenceApi', () => ({ searchCurrencies: vi.fn() }));
vi.mock('../vehicleRentalApi', () => ({
    listAvailableRentalVehicles: vi.fn(),
    listRentalAgreements: vi.fn(),
    listRentalAllocations: vi.fn(),
    listVehicleFinanceAgreements: vi.fn(),
}));
vi.mock('@/shared/components/LookupSelect', () => ({
    LookupSelect: ({ search }: { search: (params: LookupLoadParams) => Promise<unknown> }) => (
        <button
            type="button"
            onClick={() => void search({
                search: '',
                page: 1,
                perPage: 25,
                signal: new AbortController().signal,
            })}
        >
            Load invoices
        </button>
    ),
}));

describe('RentalInvoiceLookupSelect', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        invoiceApiMocks.listInvoices.mockResolvedValue({
            data: [],
            links: {},
            meta: {
                current_page: 1,
                from: null,
                last_page: 1,
                per_page: 25,
                to: null,
                total: 0,
            },
        });
    });

    it('requests only same-currency settlement-eligible rental invoices for a deposit', async () => {
        render(
            <RentalInvoiceLookupSelect
                value={null}
                onChange={() => undefined}
                invoiceType="rental"
                direction="outbound"
                partyId={91}
                currencyId={5}
                settlementEligible
            />,
        );

        await userEvent.click(screen.getByRole('button', { name: 'Load invoices' }));

        await waitFor(() => expect(invoiceApiMocks.listInvoices).toHaveBeenCalledWith(
            expect.objectContaining({
                invoice_type: 'rental',
                direction: 'outbound',
                party_id: 91,
                currency_id: 5,
                settlement_eligible: true,
            }),
            expect.any(AbortSignal),
        ));
    });

    it('deposit workflow binds the selected customer and currency to the eligible-invoice lookup', () => {
        const source = readFileSync(
            new URL('../pages/RentalDepositPage.tsx', import.meta.url),
            'utf8',
        );

        expect(source).toContain('partyId={selected.customer?.id ?? null}');
        expect(source).toContain('currencyId={selected.currency?.id ?? null}');
        expect(source).toContain('settlementEligible');
        expect(source).toContain('disabled={!selected.customer?.id || !selected.currency?.id}');
    });

});
