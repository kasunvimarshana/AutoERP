/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import PromotionWorkspacePage from './PromotionWorkspacePage';

const promotionApiMocks = vi.hoisted(() => ({
    offers: vi.fn(),
    templates: vi.fn(),
    cardActivity: vi.fn(),
    deleteOffer: vi.fn(),
    setOfferStatus: vi.fn(),
}));

vi.mock('../promotionApi', () => ({
    promotionApi: promotionApiMocks,
    searchPromotionJobItems: vi.fn(),
}));

vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({ permissions: ['promotion.manage'] }),
}));

vi.mock('@/modules/auth/accessControl', () => ({
    hasPermission: () => true,
}));

const mockOffer = {
    id: 99,
    row_version: 1,
    code: 'OFFER99',
    name: 'Oil Change Special',
    count_scope: 'vehicle',
    qualifying_count: 5,
    reward_count: 1,
    starts_on: '2026-01-01',
    ends_on: null,
    status: 'active',
};

describe('PromotionWorkspacePage ConfirmDialog', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        promotionApiMocks.offers.mockResolvedValue([mockOffer]);
        promotionApiMocks.templates.mockResolvedValue([]);
        promotionApiMocks.cardActivity.mockResolvedValue([]);
    });

    it('opens ConfirmDialog when deleting an offer and deletes upon confirmation', async () => {
        promotionApiMocks.deleteOffer.mockResolvedValue({});

        render(
            <TestRouter>
                <PromotionWorkspacePage />
            </TestRouter>,
        );

        expect(await screen.findByText('Oil Change Special')).toBeInTheDocument();

        // Click delete on the offer
        const deleteBtn = screen.getByRole('button', { name: 'Delete' });
        fireEvent.click(deleteBtn);

        // Confirm dialog should be open
        expect(await screen.findByText('Delete offer Oil Change Special?')).toBeInTheDocument();
        expect(screen.getByText(/Offers with assignments or card benefits cannot be deleted/)).toBeInTheDocument();

        // Cancel
        fireEvent.click(screen.getByRole('button', { name: 'Cancel' }));
        expect(promotionApiMocks.deleteOffer).not.toHaveBeenCalled();

        // Open again and confirm
        fireEvent.click(screen.getByRole('button', { name: 'Delete' }));
        const dialog = screen.getByRole('dialog');
        const confirmBtn = dialog.querySelector('button.bg-rose-600');
        expect(confirmBtn).not.toBeNull();
        fireEvent.click(confirmBtn!);

        await waitFor(() => {
            expect(promotionApiMocks.deleteOffer).toHaveBeenCalledWith(99, 1);
        });
    });
});
