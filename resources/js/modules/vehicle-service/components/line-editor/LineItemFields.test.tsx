import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { lookupApi, type ItemLookupResource } from '@/shared/api/lookupApi';
import {
    LineItemFields,
    isInventoryLineItem,
    lineSourceTypeForItem,
    lineValueWithItem,
    searchVehicleServiceLineItems,
} from './LineItemFields';
import { emptyLineForm } from './lineForm';

describe('LineItemFields', () => {
    afterEach(() => vi.restoreAllMocks());

    it.each([
        [{ id: 1, name: 'Oil', item_type: 'stock', is_stockable: true }, 'inventory_item'],
        [{ id: 2, name: 'Inspection', item_type: 'service', is_stockable: false }, 'service_item'],
        [{ id: 3, name: 'Technician', item_type: 'labour', is_stockable: false }, 'labour_item'],
        [{ id: 4, name: 'Full service', item_type: 'combo', is_stockable: false }, 'combo_parent'],
        [{ id: 5, name: 'Care package', item_type: 'package', is_stockable: false }, 'combo_parent'],
    ] satisfies Array<[ItemLookupResource, string]>)('derives the job-line source from %s', (item, source) => {
        expect(lineSourceTypeForItem(item)).toBe(source);
    });

    it('allows stock issue only for inventory items, never combo or package items', () => {
        expect(isInventoryLineItem({
            id: 1,
            name: 'Oil',
            item_type: 'stock',
            is_stockable: true,
        })).toBe(true);
        expect(isInventoryLineItem({
            id: 2,
            name: 'Stockable combo',
            item_type: 'combo',
            is_stockable: true,
            is_combo: true,
        })).toBe(false);
        expect(isInventoryLineItem({
            id: 3,
            name: 'Stockable package',
            item_type: 'package',
            is_stockable: true,
        })).toBe(false);
    });

    it('applies the selected item and clears inventory issue fields for non-inventory items', () => {
        const value = {
            ...emptyLineForm(),
            issueWarehouse: { id: 10, name: 'Main' },
            issueLocation: { id: 20, name: 'Receiving' },
        };
        const item: ItemLookupResource = {
            id: 2,
            code: 'SVC-1',
            name: 'Inspection',
            item_type: 'service',
            is_stockable: false,
            base_uom: { id: 30, name: 'Each' },
            resolved_service_unit_price: '2500.000000',
        };

        expect(lineValueWithItem(value, item)).toMatchObject({
            source: 'service_item',
            item,
            description: 'Inspection',
            uom: item.base_uom,
            unit_price: '2500.000000',
            issueWarehouse: null,
            issueLocation: null,
        });
    });

    it('combines eligible item searches, removes duplicates, and excludes unsupported items', async () => {
        const stock = { id: 1, name: 'Oil', item_type: 'stock', is_stockable: true } satisfies ItemLookupResource;
        const invalid = { id: 2, name: 'Invalid non-stock', item_type: 'non_stock', is_stockable: true } satisfies ItemLookupResource;
        const service = { id: 3, name: 'Inspection', item_type: 'service' } satisfies ItemLookupResource;
        vi.spyOn(lookupApi, 'untrackedStockableItems').mockResolvedValue({ data: [stock, invalid] });
        vi.spyOn(lookupApi, 'serviceBatchItems').mockResolvedValue({ data: [] });
        vi.spyOn(lookupApi, 'serviceItems').mockResolvedValue({ data: [service] });
        vi.spyOn(lookupApi, 'labourItems').mockResolvedValue({ data: [] });
        vi.spyOn(lookupApi, 'comboItems').mockResolvedValue({ data: [stock] });

        const result = await searchVehicleServiceLineItems({
            search: 'i',
            page: 1,
            perPage: 20,
            signal: new AbortController().signal,
        });

        expect(result.data).toEqual([stock, service]);
    });

    it('does not expose line type and provides a secondary external-item flow', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        const { rerender } = render(
            <LineItemFields value={emptyLineForm()} error={null} onChange={onChange} />,
        );

        expect(screen.queryByLabelText('Line type')).not.toBeInTheDocument();
        expect(screen.getByLabelText('Item')).toBeInTheDocument();

        await user.click(screen.getByRole('button', {
            name: 'Enter an external or customer-supplied item',
        }));

        const externalValue = onChange.mock.calls[0][0];
        expect(externalValue.source).toBe('external_item');

        rerender(<LineItemFields value={externalValue} error={null} onChange={onChange} />);
        expect(screen.getByLabelText('External item description')).toBeRequired();
        expect(screen.getByRole('button', { name: 'Search registered items instead' })).toBeInTheDocument();
    });
});
