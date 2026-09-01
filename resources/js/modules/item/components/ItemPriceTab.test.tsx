import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import ItemPriceTab from './ItemPriceTab';

const apiMocks = vi.hoisted(() => ({
    createItemPrice: vi.fn(),
    listItemPrices: vi.fn(),
    supersedeItemPrice: vi.fn(),
    createBatchPrice: vi.fn(),
    listBatchPrices: vi.fn(),
    searchInventoryBatches: vi.fn(),
    supersedeBatchPrice: vi.fn(),
}));

vi.mock('../itemApi', () => ({
    createItemPrice: apiMocks.createItemPrice,
    listItemPrices: apiMocks.listItemPrices,
    supersedeItemPrice: apiMocks.supersedeItemPrice,
}));

vi.mock('@/modules/inventory/inventoryApi', () => ({
    createBatchPrice: apiMocks.createBatchPrice,
    listBatchPrices: apiMocks.listBatchPrices,
    searchInventoryBatches: apiMocks.searchInventoryBatches,
    supersedeBatchPrice: apiMocks.supersedeBatchPrice,
}));

vi.mock('./ItemVariantSelect', () => ({
    ItemVariantSelect: () => <div>Variant selector</div>,
}));

vi.mock('./ItemCurrencySelect', () => ({
    ItemCurrencySelect: ({ value }: { value: { code?: string | null } | null }) => <div data-testid="selected-currency">{value?.code ?? 'No currency'}</div>,
}));

vi.mock('./ItemUomSelect', () => ({
    ItemUomSelect: ({ value }: { value: { code?: string | null } | null }) => <div data-testid="selected-uom">{value?.code ?? 'No UOM'}</div>,
}));

const emptyCollection = {
    data: [],
    links: { first: null, last: null, prev: null, next: null },
    meta: { current_page: 1, from: null, last_page: 1, per_page: 20, to: null, total: 0 },
};

describe('ItemPriceTab defaults', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.listItemPrices.mockResolvedValue(emptyCollection);
        apiMocks.listBatchPrices.mockResolvedValue(emptyCollection);
    });

    it('preselects the item base UOM and tenant currency for new item and batch prices', async () => {
        render(<TestRouter><ItemPriceTab
            itemId={10}
            trackingType="batch"
            defaultCurrency={{ id: 1, code: 'LKR', name: 'Sri Lankan Rupee' }}
            defaultUom={{ id: 2, code: 'PCS', name: 'Pieces' }}
            canViewBatchPrices
            canManageBatchPrices
        /></TestRouter>);

        await waitFor(() => expect(apiMocks.listItemPrices).toHaveBeenCalled());
        await waitFor(() => expect(apiMocks.listBatchPrices).toHaveBeenCalled());

        fireEvent.click(screen.getAllByRole('button', { name: 'Add' })[0]);
        expect(await screen.findByTestId('selected-currency')).toHaveTextContent('LKR');
        expect(screen.getByTestId('selected-uom')).toHaveTextContent('PCS');

        fireEvent.click(screen.getByRole('button', { name: 'Cancel' }));
        fireEvent.click(screen.getAllByRole('button', { name: 'Add' })[1]);
        expect(await screen.findByTestId('selected-currency')).toHaveTextContent('LKR');
        expect(screen.getByTestId('selected-uom')).toHaveTextContent('PCS');
    });
});
