import { useEffect, useState } from 'react';
import { toApiError } from '@/shared/api/apiError';
import type { ItemLookupResource } from '@/shared/api/lookupApi';
import { Button } from '@/shared/components/Button';
import { Select } from '@/shared/components/Select';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { promotionApi, searchPromotionJobItems, type AvailablePromotions } from '../promotionApi';

type Choice = { card_id?: number; card_benefit_id?: number; offer_assignment_id?: number };

export function PromotionJobStrip({ customerId, vehicleId, onAdd, pendingChoices = [] }: {
    customerId: number | null;
    vehicleId: number | null;
    onAdd?: (item: ItemLookupResource, choice: Choice) => void;
    pendingChoices?: Choice[];
}) {
    const auth = useAuth();
    const canView = hasPermission(auth, 'promotion.view') || hasPermission(auth, 'promotion.manage') || hasPermission(auth, 'promotion.redeem');
    const canAssign = hasPermission(auth, 'promotion.manage') || hasPermission(auth, 'promotion.redeem');
    const canRedeem = hasPermission(auth, 'promotion.redeem');
    const [loaded, setLoaded] = useState<{ key: string; data: AvailablePromotions } | null>(null);
    const [cardId, setCardId] = useState('');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');

    const key = `${customerId ?? ''}:${vehicleId ?? ''}`;
    const available = loaded?.key === key ? loaded.data : null;
    useEffect(() => {
        if (!canView || !customerId || !vehicleId) return;
        let active = true;
        promotionApi.available(customerId, vehicleId).then((result) => {
            if (active) { setLoaded({ key, data: result }); setError(''); }
        }).catch((reason) => { if (active) setError(toApiError(reason).message); });
        return () => { active = false; };
    }, [canView, customerId, vehicleId, key]);

    if (!canView || !customerId || !vehicleId) return null;

    const add = async (itemId: number, itemCode: string, itemName: string, choice: Choice) => {
        if (!onAdd) return;
        setBusy(true); setError('');
        try {
            const controller = new AbortController();
            const result = await searchPromotionJobItems({ search: itemCode, page: 1, perPage: 50, signal: controller.signal });
            const item = result.data.find((candidate) => (candidate.item_id ?? candidate.id) === itemId);
            if (!item || Number(item.resolved_service_unit_price ?? 0) <= 0) {
                setError(`Set a job price for ${itemName} before adding this benefit.`);
                return;
            }
            onAdd(item, choice);
        } catch (reason) { setError(toApiError(reason).message); }
        finally { setBusy(false); }
    };

    const assign = async (offerId: number) => {
        if (!customerId || !vehicleId) return;
        setBusy(true); setError('');
        try {
            await promotionApi.assignOffer(offerId, { customer_id: customerId, vehicle_id: vehicleId });
            setLoaded({ key, data: await promotionApi.available(customerId, vehicleId) });
        } catch (reason) { setError(toApiError(reason).message); }
        finally { setBusy(false); }
    };

    const selectedCard = available?.cards.find((card) => String(card.id) === cardId) ?? available?.cards[0];
    const cardOffers = available?.offers.filter((offer) => offer.card_id === selectedCard?.id) ?? [];
    const standaloneOffers = available?.offers.filter((offer) => offer.card_id === null) ?? [];
    const pendingForBenefit = (id: number) => pendingChoices.filter((choice) => choice.card_benefit_id === id).length;
    const pendingForOffer = (id: number) => pendingChoices.filter((choice) => choice.offer_assignment_id === id).length;

    return <section className="rounded-xl border border-sky-200 bg-sky-50/70 px-4 py-4" aria-label="Service cards and offers">
        <div className="flex flex-wrap items-start justify-between gap-3">
            <div><p className="text-sm font-semibold text-slate-900">Service card & offers</p>
                <p className="text-xs text-slate-600">Benefits available for this customer and vehicle. Savings are confirmed when invoiced.</p></div>
            {selectedCard && <span className="rounded-full bg-white px-3 py-1 text-xs font-medium text-sky-800">Expires {selectedCard.expires_on}</span>}
        </div>
        {error && <p role="alert" className="mt-3 text-sm text-rose-700">{error}</p>}
        {available && canAssign && available.unassigned_offers.length > 0 && <div className="mt-3 flex flex-wrap gap-2">{available.unassigned_offers.map((offer) =>
            <label key={offer.id} className="flex cursor-pointer items-center gap-2 rounded-lg border border-sky-200 bg-white px-3 py-2 text-sm text-slate-800">
                <input type="checkbox" checked={false} disabled={busy} onChange={() => void assign(offer.id)} />
                <span>Add {offer.name} · {offer.count_scope === 'vehicle' ? 'this vehicle' : 'customer'}<span className="block text-xs text-slate-500">Counts {offer.paid_item_code} · {offer.paid_item_name} after invoice posting</span></span>
            </label>)}</div>}
        {available && available.cards.length === 0 && standaloneOffers.length === 0 && (!canAssign || available.unassigned_offers.length === 0) && <p className="mt-3 text-sm text-slate-600">No active card or offer for this vehicle.</p>}
        {available && available.cards.length > 0 && <div className="mt-3 max-w-sm"><Select label="Service card" value={selectedCard ? String(selectedCard.id) : ''}
            options={available.cards.map((card) => ({ value: String(card.id), label: `${card.template_name} · ${card.card_number}` }))}
            onChange={(event) => setCardId(event.target.value)} /></div>}
        {selectedCard && <div className="mt-3 flex flex-wrap gap-2">{selectedCard.benefits?.filter((benefit) => benefit.kind === 'direct').map((benefit) =>
            <div key={benefit.id} className="min-w-48 rounded-lg border border-sky-100 bg-white p-3 text-sm">
                <p className="font-medium text-slate-900">{benefit.item_name}</p>
                <div className="mt-2 flex flex-wrap gap-1.5">
                    <span className="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-800">{benefit.discount_type === 'free' ? 'Free' : `${benefit.discount_rate}% off`}</span>
                    <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{Math.max(0, (benefit.remaining_uses ?? 0) - pendingForBenefit(benefit.id))} uses left</span>
                    {!benefit.available_today && <span className="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800">Not valid today</span>}
                </div>
                {onAdd && canRedeem && <Button type="button" variant="secondary" className="mt-3" disabled={busy || !benefit.available_today || (benefit.remaining_uses ?? 0) <= pendingForBenefit(benefit.id)} onClick={() => void add(benefit.item_id!, benefit.item_code!, benefit.item_name!, { card_id: selectedCard.id, card_benefit_id: benefit.id })}>Add job line</Button>}
            </div>)}</div>}
        {[...cardOffers, ...standaloneOffers].length > 0 && <div className="mt-3 flex flex-wrap gap-2">{[...cardOffers, ...standaloneOffers].map((offer) =>
            <div key={offer.id} className="min-w-52 rounded-lg border border-sky-100 bg-white p-3 text-sm">
                <span className="mb-2 inline-flex items-center gap-1 text-xs font-medium text-emerald-800"><input type="checkbox" checked readOnly aria-label={`${offer.offer_name} added`} /> Added</span>
                <p className="font-medium text-slate-900">{offer.offer_name}</p>
                <div className="mt-2 flex flex-wrap gap-1.5">
                    <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{Number(offer.progress_quantity)}/{offer.paid_quantity} paid</span>
                    <span className="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-800">{Number(offer.remaining_to_next)} to next free</span>
                    {Number(offer.available_rewards) > pendingForOffer(offer.id) && <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">{Number(offer.available_rewards) - pendingForOffer(offer.id)} free ready</span>}
                </div>
                <p className="mt-2 text-xs text-slate-500">Paid item: {offer.qualifying_item_code} · {offer.qualifying_item_name}. Count updates after invoice posting.</p>
                {onAdd && canRedeem && Number(offer.available_rewards) > pendingForOffer(offer.id) && <Button type="button" variant="secondary" className="mt-3" disabled={busy} onClick={() => void add(offer.reward_item_id, offer.reward_item_code, offer.reward_item_name, { offer_assignment_id: offer.id })}>Add free job line</Button>}
            </div>)}</div>}
    </section>;
}
