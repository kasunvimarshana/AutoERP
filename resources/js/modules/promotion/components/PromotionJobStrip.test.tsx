import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { PromotionJobStrip } from './PromotionJobStrip';

const apiMocks = vi.hoisted(() => ({
    available: vi.fn(),
    issueCard: vi.fn(),
    assignOffer: vi.fn(),
    searchPromotionJobItems: vi.fn(),
}));

vi.mock('../promotionApi', () => ({ promotionApi: apiMocks, searchPromotionJobItems: apiMocks.searchPromotionJobItems }));
vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => ({ roles: [], permissions: ['promotion.view', 'promotion.manage', 'promotion.redeem'], permissionsLoaded: true }),
}));

describe('PromotionJobStrip count cards', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.available
            .mockResolvedValueOnce({ cards: [], offers: [], unassigned_offers: [], card_designs: [
                { id: 7, name: 'Five washes', ends_on: '2026-12-31' },
            ] })
            .mockResolvedValueOnce({ cards: [{
                id: 18, template_name: 'Earlier card', card_number: 'CARD-18', expires_on: '2026-12-31', benefits: [],
            }, {
                id: 19, template_name: 'Five washes', card_number: 'CARD-19', expires_on: '2026-12-31', benefits: [],
            }], offers: [{
                id: 31, card_id: 19, offer_name: 'Five washes', progress_quantity: '0.000000',
                paid_quantity: 5, remaining_to_next: '5.000000', available_rewards: '0.000000',
                qualifying_item_code: 'WASH', qualifying_item_name: 'Wash',
            }], unassigned_offers: [], card_designs: [] });
        apiMocks.issueCard.mockResolvedValue({ id: 19 });
        apiMocks.searchPromotionJobItems.mockResolvedValue({ data: [{
            id: 42, item_id: 42, code: 'WASH', name: 'Wash', resolved_service_unit_price: '150.000000',
        }] });
    });

    it('issues a valid design to the selected customer and vehicle and shows reset progress', async () => {
        const user = userEvent.setup();
        render(<PromotionJobStrip customerId={4} vehicleId={9} />);

        await user.click(await screen.findByRole('button', { name: 'Assign card' }));

        await waitFor(() => expect(apiMocks.issueCard).toHaveBeenCalledWith({
            template_id: 7, customer_id: 4, vehicle_id: 9,
        }));
        expect(await screen.findByText('0/5')).toBeInTheDocument();
        expect(screen.getAllByText('Five washes').length).toBeGreaterThan(0);
    });

    it('issues a direct benefit card from job creation and selects it', async () => {
        const user = userEvent.setup();
        apiMocks.available.mockReset()
            .mockResolvedValueOnce({ cards: [], offers: [], unassigned_offers: [], card_designs: [
                { id: 8, name: 'Service card', ends_on: '2026-12-31' },
            ] })
            .mockResolvedValueOnce({ cards: [{
                id: 21, template_name: 'Service card', card_number: 'CARD-21', expires_on: '2026-12-31',
                benefits: [{ id: 3, kind: 'direct', item_id: 42, item_code: 'WASH', item_name: 'Wash', discount_type: 'free', remaining_uses: 1, available_today: true }],
            }], offers: [], unassigned_offers: [], card_designs: [] });
        apiMocks.issueCard.mockResolvedValue({ id: 21 });
        const onAdd = vi.fn();
        render(<PromotionJobStrip customerId={4} vehicleId={9} onAdd={onAdd} />);

        await user.click(await screen.findByRole('button', { name: 'Assign card' }));

        await waitFor(() => expect(apiMocks.issueCard).toHaveBeenCalledWith({ template_id: 8, customer_id: 4, vehicle_id: 9 }));
        expect(await screen.findByText('Wash')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: '1 available item' })).toHaveAttribute('aria-expanded', 'true');
        await user.click(screen.getByRole('button', { name: 'Add Wash line' }));
        await waitFor(() => expect(onAdd).toHaveBeenCalledWith(expect.objectContaining({ item_id: 42 }), { card_id: 21, card_benefit_id: 3 }));
    });

    it('shows assigned and available count offers beside service card assignment', async () => {
        apiMocks.available.mockReset().mockResolvedValue({
            cards: [], card_designs: [{ id: 8, name: 'Service card', ends_on: '2026-11-20' }],
            offers: [{ id: 31, card_id: null, offer_name: 'Wash and vacuum', qualifying_item_id: 42,
                qualifying_item_code: 'WASH', qualifying_item_name: 'Wash', progress_quantity: '2.000000',
                paid_quantity: 5, remaining_to_next: '3.000000', available_rewards: '0.000000' }],
            unassigned_offers: [{ id: 32, name: 'Count 2', count_scope: 'vehicle', paid_item_name: 'Vacuum', paid_item_code: 'VAC' }],
        });
        render(<PromotionJobStrip customerId={4} vehicleId={9} onAddPaidItem={vi.fn()} />);

        expect(await screen.findByRole('heading', { name: /Count offers/ })).toBeInTheDocument();
        expect(screen.getByRole('heading', { name: /Service cards/ })).toBeInTheDocument();
        expect(screen.getByText('2/5')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Assign offer' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Assign card' })).toBeInTheDocument();
    });

    it('adds an individual discounted benefit on a saved job and shows its remaining use', async () => {
        const user = userEvent.setup();
        const onAddCardBenefit = vi.fn();
        apiMocks.available.mockReset().mockResolvedValue({ cards: [{
            id: 21, template_name: 'Service card', card_number: 'CARD-21', expires_on: '2026-12-31',
            benefits: [{ id: 4, kind: 'direct', item_id: 42, item_code: 'WASH', item_name: 'Wash',
                discount_type: 'percentage', discount_rate: '10.000000', remaining_uses: 1, available_today: true }],
        }], offers: [], unassigned_offers: [], card_designs: [] });
        const { rerender } = render(<PromotionJobStrip customerId={4} vehicleId={9} onAddCardBenefit={onAddCardBenefit} />);

        await user.click(await screen.findByRole('button', { name: '1 available item' }));
        await user.click(screen.getByRole('button', { name: 'Add Wash line' }));
        await waitFor(() => expect(onAddCardBenefit).toHaveBeenCalledWith(expect.objectContaining({ item_id: 42 }), { card_id: 21, card_benefit_id: 4 }));

        rerender(<PromotionJobStrip customerId={4} vehicleId={9} onAddCardBenefit={onAddCardBenefit}
            pendingChoices={[{ card_id: 21, card_benefit_id: 4 }]} />);
        expect(screen.getByText('10% off · 0 available')).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Add Wash line' })).not.toBeInTheDocument();
    });

    it('adds a card paid item only when Add line is clicked', async () => {
        const user = userEvent.setup();
        const onAddPaidItem = vi.fn();
        apiMocks.available.mockReset().mockResolvedValue({ cards: [{
            id: 19, template_name: 'Five washes', card_number: 'CARD-19', expires_on: '2026-12-31', benefits: [],
        }], offers: [{
            id: 31, card_id: 19, offer_name: 'Five washes', qualifying_item_id: 42,
            qualifying_item_code: 'WASH', qualifying_item_name: 'Wash', progress_quantity: '0.000000',
            paid_quantity: 5, remaining_to_next: '5.000000', available_rewards: '0.000000',
        }], unassigned_offers: [], card_designs: [] });
        render(<PromotionJobStrip customerId={4} vehicleId={9} onAddPaidItem={onAddPaidItem} />);

        expect(await screen.findByRole('button', { name: 'Add line' })).toBeInTheDocument();
        expect(onAddPaidItem).not.toHaveBeenCalled();
        await user.click(screen.getByRole('button', { name: 'Add line' }));
        await waitFor(() => expect(onAddPaidItem).toHaveBeenCalledWith(expect.objectContaining({ item_id: 42 })));
    });

    it('hides Add line when the paid item is already on the job', async () => {
        apiMocks.available.mockReset().mockResolvedValue({ cards: [{
            id: 19, template_name: 'Five washes', card_number: 'CARD-19', expires_on: '2026-12-31', benefits: [],
        }], offers: [{
            id: 31, card_id: 19, offer_name: 'Five washes', qualifying_item_id: 42,
            qualifying_item_code: 'WASH', qualifying_item_name: 'Wash', progress_quantity: '0.000000',
            paid_quantity: 5, remaining_to_next: '5.000000', available_rewards: '0.000000',
        }], unassigned_offers: [], card_designs: [] });
        render(<PromotionJobStrip customerId={4} vehicleId={9} existingPaidItemIds={[42]} onAddPaidItem={vi.fn()} />);

        expect(await screen.findByText('0/5')).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Add line' })).not.toBeInTheDocument();
    });

    it('shows Add line for an assigned standalone count offer', async () => {
        const user = userEvent.setup();
        const onAddPaidItem = vi.fn();
        apiMocks.available.mockReset().mockResolvedValue({ cards: [], offers: [{
            id: 31, card_id: null, offer_name: 'Wash and vacuum', qualifying_item_id: 42,
            qualifying_item_code: 'WASH', qualifying_item_name: 'Wash', progress_quantity: '0.000000',
            paid_quantity: 5, remaining_to_next: '5.000000', available_rewards: '0.000000',
        }], unassigned_offers: [], card_designs: [] });
        render(<PromotionJobStrip customerId={4} vehicleId={9} onAddPaidItem={onAddPaidItem} />);

        await user.click(await screen.findByRole('button', { name: 'Add line' }));
        await waitFor(() => expect(onAddPaidItem).toHaveBeenCalledWith(expect.objectContaining({ item_id: 42 })));
    });

    it('uses the single action for an earned free item instead of the paid item', async () => {
        const user = userEvent.setup();
        const onAddPaidItem = vi.fn();
        const onAdd = vi.fn();
        apiMocks.available.mockReset().mockResolvedValue({ cards: [], offers: [{
            id: 31, card_id: null, offer_name: 'Wash and vacuum', qualifying_item_id: 42,
            qualifying_item_code: 'WASH', qualifying_item_name: 'Wash', reward_item_id: 43,
            reward_item_code: 'FREE-WASH', reward_item_name: 'Free wash', progress_quantity: '0.000000',
            paid_quantity: 5, remaining_to_next: '5.000000', available_rewards: '1.000000',
        }], unassigned_offers: [], card_designs: [] });
        apiMocks.searchPromotionJobItems.mockResolvedValue({ data: [{
            id: 43, item_id: 43, code: 'FREE-WASH', name: 'Free wash', resolved_service_unit_price: '100.000000',
        }] });
        render(<PromotionJobStrip customerId={4} vehicleId={9} onAddPaidItem={onAddPaidItem} onAddOfferReward={onAdd} />);

        expect(screen.queryByRole('button', { name: 'Add line' })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Add available card items' })).not.toBeInTheDocument();
        await user.click(await screen.findByRole('button', { name: 'Add free line' }));
        await waitFor(() => expect(onAdd).toHaveBeenCalledWith(expect.objectContaining({ item_id: 43 }), { offer_assignment_id: 31 }));
        expect(onAddPaidItem).not.toHaveBeenCalled();
    });

    it('shows a completed paid cycle while an earned free line is pending in the job', async () => {
        apiMocks.available.mockReset().mockResolvedValue({ cards: [], offers: [{
            id: 31, card_id: null, offer_name: 'Wash only', qualifying_item_id: 42,
            qualifying_item_code: 'WASH', qualifying_item_name: 'Wash', progress_quantity: '0.000000',
            paid_quantity: 2, available_rewards: '1.000000',
        }], unassigned_offers: [], card_designs: [] });
        const { rerender } = render(<PromotionJobStrip customerId={4} vehicleId={9} onAddPaidItem={vi.fn()} onAddOfferReward={vi.fn()} />);

        expect(await screen.findByText('2/2')).toBeInTheDocument();
        expect(screen.getByText('1 free ready')).toBeInTheDocument();
        expect(screen.getByText('2/2').previousElementSibling?.querySelectorAll('.bg-blue-600')).toHaveLength(2);

        rerender(<PromotionJobStrip customerId={4} vehicleId={9} onAddPaidItem={vi.fn()} onAddOfferReward={vi.fn()}
            pendingChoices={[{ offer_assignment_id: 31 }]} />);
        expect(screen.getByText('2/2')).toBeInTheDocument();
        expect(screen.getByText('Free line added')).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Add free line' })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Add line' })).not.toBeInTheDocument();
    });

    it('shows next-round progress separately when a previous cycle has a free reward', async () => {
        apiMocks.available.mockReset().mockResolvedValue({ cards: [], offers: [{
            id: 31, card_id: null, offer_name: 'Wash only', qualifying_item_id: 42,
            qualifying_item_code: 'WASH', qualifying_item_name: 'Wash', progress_quantity: '1.000000',
            paid_quantity: 2, available_rewards: '1.000000',
        }], unassigned_offers: [], card_designs: [] });
        render(<PromotionJobStrip customerId={4} vehicleId={9} onAddOfferReward={vi.fn()} />);

        expect(await screen.findByText('2/2')).toBeInTheDocument();
        expect(screen.getByText('Next round: 1/2 paid')).toBeInTheDocument();
    });
});
