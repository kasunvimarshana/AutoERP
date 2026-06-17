import { render, screen } from '@testing-library/react';
import type { ReactElement } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import ItemDetailPage from './ItemDetailPage';
import { itemPermissions } from './itemPermissions';
import type { Item } from './itemTypes';

const apiMocks = vi.hoisted(() => ({
    getItem: vi.fn(),
}));

const authState = vi.hoisted(() => ({
    permissions: [] as string[],
}));

vi.mock('./itemApi', () => ({
    getItem: apiMocks.getItem,
}));

vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({ permissions: authState.permissions }),
}));

vi.mock('./components/ItemUnitTab', () => ({
    default: ({ readOnly }: { readOnly?: boolean }) => (
        <div>
            <span>{readOnly ? 'Units read-only' : 'Units editable'}</span>
            {!readOnly && <button type="button">Add Unit</button>}
        </div>
    ),
}));
vi.mock('./components/ItemVariantTab', () => ({ default: ({ readOnly }: { readOnly?: boolean }) => <div>{readOnly ? 'Variants read-only' : 'Variants editable'}</div> }));
vi.mock('./components/ItemBundleTab', () => ({ default: ({ readOnly }: { readOnly?: boolean }) => <div>{readOnly ? 'Bundle read-only' : 'Bundle editable'}</div> }));
vi.mock('./components/ItemPriceTab', () => ({ default: ({ readOnly }: { readOnly?: boolean }) => <div>{readOnly ? 'Prices read-only' : 'Prices editable'}</div> }));
vi.mock('./components/ItemCodeTab', () => ({ default: ({ readOnly }: { readOnly?: boolean }) => <div>{readOnly ? 'Codes read-only' : 'Codes editable'}</div> }));
vi.mock('./components/ItemUsageRuleTab', () => ({ default: ({ readOnly }: { readOnly?: boolean }) => <div>{readOnly ? 'Usage read-only' : 'Usage editable'}</div> }));
vi.mock('./components/BaseUomRevisionHistoryTab', () => ({ default: () => <div>Base UOM revisions</div> }));

describe('Item detail page', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        authState.permissions = [itemPermissions.view];
        apiMocks.getItem.mockResolvedValue(item());
    });

    it('renders relation tabs read-only and hides edit without update permission', async () => {
        renderPage(<RoutePage />, ['/items/10?tab=units']);

        expect(await screen.findByRole('heading', { name: 'ITM-10 - Brake Pad' })).toBeInTheDocument();
        expect(await screen.findByText('Units read-only')).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Add Unit' })).not.toBeInTheDocument();
        expect(screen.queryByRole('link', { name: 'Edit' })).not.toBeInTheDocument();
    });

    it('falls back to Summary for invalid tab links and shows permission-aware Edit navigation', async () => {
        authState.permissions = [itemPermissions.view, itemPermissions.update];

        renderPage(<RoutePage />, ['/items/10?tab=not-real']);

        expect(await screen.findByRole('heading', { name: 'ITM-10 - Brake Pad' })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Summary' })).toHaveAttribute('aria-selected', 'true');
        expect(screen.getByText('Brake pad set')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'Edit' })).toHaveAttribute('href', '/items/10/edit');
    });
});

function RoutePage() {
    return (
        <Routes>
            <Route path="/items/:id" element={<ItemDetailPage />} />
        </Routes>
    );
}

function renderPage(page: ReactElement, initialEntries: string[]) {
    return render(
        <MemoryRouter initialEntries={initialEntries}>
            {page}
        </MemoryRouter>,
    );
}

function item(): Item {
    return {
        id: 10,
        code: 'ITM-10',
        name: 'Brake Pad',
        item_type: 'stock',
        tracking_type: 'none',
        costing_method: 'fifo',
        category: { id: 1, code: 'PARTS', name: 'Parts' },
        brand: { id: 2, code: 'GEN', name: 'Generic' },
        base_uom: { id: 3, code: 'PCS', name: 'Pieces' },
        sku: 'SKU-10',
        barcode: null,
        description: 'Brake pad set',
        is_stockable: true,
        is_combo: false,
        is_active: true,
    };
}
