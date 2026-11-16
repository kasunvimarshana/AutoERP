import { useEffect, useState } from 'react';
import { toApiError } from '@/shared/api/apiError';
import type { ItemLookupResource } from '@/shared/api/lookupApi';
import { Button } from '@/shared/components/Button';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { formatBusinessDate } from '@/shared/utils/businessDate';
import { promotionApi, searchPromotionJobItems, type AvailablePromotions } from '../promotionApi';

type Choice = { card_id?: number; card_benefit_id?: number; offer_assignment_id?: number; quantity?: string };
const MAX_VISIBLE_COUNT_MARKS = 8;

export function PromotionJobStrip({ customerId, vehicleId, onAddPaidItem, existingPaidItemIds = [], onAdd, onAddOfferReward, onAddCardBenefit, pendingChoices = [] }: {
    customerId: number | null;
    vehicleId: number | null;
    onAddPaidItem?: (item: ItemLookupResource) => Promise<void> | void;
    existingPaidItemIds?: number[];
    onAdd?: (item: ItemLookupResource, choice: Choice) => Promise<void> | void;
    onAddOfferReward?: (item: ItemLookupResource, choice: Choice) => Promise<void> | void;
    onAddCardBenefit?: (item: ItemLookupResource, choice: Choice) => Promise<void> | void;
    pendingChoices?: Choice[];
}) {
    const auth = useAuth();
    const canView = hasPermission(auth, 'promotion.view') || hasPermission(auth, 'promotion.manage') || hasPermission(auth, 'promotion.redeem');
    const canAssign = hasPermission(auth, 'promotion.manage') || hasPermission(auth, 'promotion.redeem');
    const canIssueCard = hasPermission(auth, 'promotion.manage');
    const canRedeem = hasPermission(auth, 'promotion.redeem');
    const [loaded, setLoaded] = useState<{ key: string; data: AvailablePromotions } | null>(null);
    const [expandedCardId, setExpandedCardId] = useState<number | null>(null);
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

    const add = async (itemId: number, itemCode: string, itemName: string, choice: Choice, addLine = onAdd) => {
        if (!addLine) return;
        setBusy(true); setError('');
        try {
            const controller = new AbortController();
            const result = await searchPromotionJobItems({ search: itemCode, page: 1, perPage: 50, signal: controller.signal });
            const item = result.data.find((candidate) => (candidate.item_id ?? candidate.id) === itemId);
            if (!item || Number(item.resolved_service_unit_price ?? 0) <= 0) {
                setError(`Set a job price for ${itemName} before adding this benefit.`);
                return;
            }
            await addLine(item, choice);
        } catch (reason) { setError(toApiError(reason).message); }
        finally { setBusy(false); }
    };

    const addPaidItem = async (itemId: number, itemCode: string, itemName: string) => {
        if (!onAddPaidItem || existingPaidItemIds.some((id) => Number(id) === itemId)) return;
        setBusy(true); setError('');
        try {
            const result = await searchPromotionJobItems({ search: itemCode, page: 1, perPage: 50, signal: new AbortController().signal });
            const item = result.data.find((candidate) => (candidate.item_id ?? candidate.id) === itemId);
            if (!item || Number(item.resolved_service_unit_price ?? 0) <= 0) {
                setError(`Set a job price for ${itemName} before adding this line.`);
                return;
            }
            await onAddPaidItem(item);
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

    const issueServiceCard = async (designId: number) => {
        if (!customerId || !vehicleId) return;
        setBusy(true); setError('');
        try {
            const issuedCard = await promotionApi.issueCard({ template_id: designId, customer_id: customerId, vehicle_id: vehicleId });
            setExpandedCardId(issuedCard.id);
            const refreshed = await promotionApi.available(customerId, vehicleId);
            setLoaded({ key, data: refreshed });
        } catch (reason) { setError(toApiError(reason).message); }
        finally { setBusy(false); }
    };

    const pendingForBenefit = (id: number) => pendingChoices
        .filter((choice) => choice.card_benefit_id === id)
        .reduce((total, choice) => {
            const quantity = Number(choice.quantity ?? 1);
            return total + (Number.isFinite(quantity) ? quantity : 0);
        }, 0);
    const pendingForOffer = (id: number) => pendingChoices.filter((choice) => choice.offer_assignment_id === id).length;

    const offerTile = (offer: AvailablePromotions['offers'][number]) => {
        const earnedRewards = Number(offer.available_rewards);
        const pendingRewards = pendingForOffer(offer.id);
        const readyRewards = Math.max(0, earnedRewards - pendingRewards);
        const freeReady = canRedeem && (onAddOfferReward || onAdd) && readyRewards > 0;
        const paidQuantity = Number(offer.paid_quantity);
        const progress = Math.max(0, Math.min(Number(offer.progress_quantity), paidQuantity));
        const completedCycle = earnedRewards > 0;
        const displayedProgress = completedCycle ? paidQuantity : progress;
        const paidItemInJob = existingPaidItemIds.some((id) => Number(id) === offer.qualifying_item_id);

        return <div key={offer.id} className="rounded-xl border border-blue-200 bg-blue-50/50 px-4 py-3">
            <div className="flex items-start justify-between gap-3">
                <div><p className="font-semibold text-slate-900">{offer.offer_name}</p>
                    <p className="text-sm text-slate-600">{offer.qualifying_item_name}</p></div>
                <span className="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">Assigned</span>
            </div>
            <div className="mt-2 flex flex-wrap items-center gap-2 text-sm text-slate-600">
                {Number.isInteger(paidQuantity) && paidQuantity > 0 && paidQuantity <= MAX_VISIBLE_COUNT_MARKS &&
                    <span className="flex gap-0.5" aria-hidden="true">{Array.from({ length: paidQuantity }, (_, index) =>
                        <span key={index} className={`h-1.5 w-3 rounded-full ${index < displayedProgress ? 'bg-blue-600' : 'bg-slate-300'}`} />)}</span>}
                <span className="font-semibold text-slate-800">{completedCycle ? paidQuantity : Number(offer.progress_quantity)}/{offer.paid_quantity}</span> paid visits{completedCycle ? ' completed' : ''}
                {readyRewards > 0 && <span className="font-semibold text-green-700">{readyRewards} free ready</span>}
                {pendingRewards > 0 && <span className="font-semibold text-blue-700">Free line added</span>}
                {completedCycle && !offer.card_id && progress > 0 && <span>Next round: {progress}/{offer.paid_quantity} paid</span>}
            </div>
            {freeReady && <Button type="button" variant="secondary" className="mt-3" disabled={busy}
                onClick={() => void add(offer.reward_item_id, offer.reward_item_code, offer.reward_item_name,
                    { offer_assignment_id: offer.id }, onAddOfferReward ?? onAdd)}>Add free line</Button>}
            {!completedCycle && onAddPaidItem && !paidItemInJob &&
                <Button type="button" variant="secondary" className="mt-3" disabled={busy}
                    onClick={() => void addPaidItem(offer.qualifying_item_id, offer.qualifying_item_code, offer.qualifying_item_name)}>Add line</Button>}
        </div>;
    };

    return <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs" aria-label="Service cards and offers">
        <header className="flex items-center gap-4 border-b border-slate-200 px-5 py-4">
            <span className="rounded-xl bg-blue-50 p-3 text-blue-700" aria-hidden="true">
                <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
                    <path d="M3 6h18v4a2 2 0 0 0 0 4v4H3v-4a2 2 0 0 0 0-4V6Z" strokeLinejoin="round" />
                </svg>
            </span>
            <div><h2 className="text-xl font-bold text-slate-900">Service card & offers</h2>
                <p className="text-sm text-slate-600">Savings are confirmed at invoicing.</p></div>
        </header>
        {error && <p role="alert" className="mx-5 mt-4 text-sm text-rose-700">{error}</p>}
        <div className="grid lg:grid-cols-2">
            <div className="space-y-3 p-5 lg:border-r lg:border-slate-200" aria-label="Count offers">
                <h3 className="font-semibold text-slate-900">Count offers <span className="rounded-md bg-slate-100 px-2 py-0.5 text-sm text-slate-600">{(available?.offers.length ?? 0) + (canAssign ? available?.unassigned_offers.length ?? 0 : 0)}</span></h3>
                {available?.offers.map(offerTile)}
                {available && canAssign && available.unassigned_offers.map((offer) =>
                    <div key={offer.id} className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <div><p className="font-semibold text-slate-900">{offer.name}</p>
                                <p className="text-sm text-slate-600">{offer.paid_item_name}</p></div>
                            <Button type="button" variant="secondary" disabled={busy} onClick={() => void assign(offer.id)}>Assign offer</Button>
                        </div>
                    </div>)}
                {available && available.offers.length === 0 && (!canAssign || available.unassigned_offers.length === 0) &&
                    <p className="text-sm text-slate-600">No count offers available.</p>}
            </div>
            <div className="space-y-3 border-t border-slate-200 p-5 lg:border-t-0" aria-label="Service cards">
                <h3 className="font-semibold text-slate-900">Service cards <span className="rounded-md bg-slate-100 px-2 py-0.5 text-sm text-slate-600">{(available?.cards.length ?? 0) + (canIssueCard ? available?.card_designs.length ?? 0 : 0)}</span></h3>
                {available?.cards.map((card) => {
                    const benefits = card.benefits?.filter((benefit) => benefit.kind === 'direct') ?? [];
                    const usable = benefits.filter((benefit) => benefit.available_today && (benefit.remaining_uses ?? 0) > pendingForBenefit(benefit.id));
                    const expanded = expandedCardId === card.id;
                    return <div key={card.id} className="overflow-hidden rounded-xl border border-blue-200 bg-blue-50/50">
                        <div className="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                            <div><p className="font-semibold text-slate-900">{card.template_name}</p>
                                <p className="text-xs text-slate-600">Assigned · Valid until {formatBusinessDate(card.expires_on)}</p></div>
                            <span className="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">Assigned</span>
                        </div>
                        {benefits.length > 0 ? <>
                            <button type="button" className="flex w-full items-center justify-between border-t border-blue-100 px-4 py-2 text-left text-sm font-semibold text-blue-700 hover:bg-blue-50"
                                aria-expanded={expanded} aria-controls={`card-benefits-${card.id}`} onClick={() => setExpandedCardId(expanded ? null : card.id)}>
                                {usable.length} available {usable.length === 1 ? 'item' : 'items'} <span aria-hidden="true">{expanded ? '⌃' : '⌄'}</span>
                            </button>
                            {expanded && <div id={`card-benefits-${card.id}`} className="border-t border-blue-100 bg-white px-4 py-1">{benefits.map((benefit) => {
                                const remaining = Math.max(0, (benefit.remaining_uses ?? 0) - pendingForBenefit(benefit.id));
                                const canAdd = canRedeem && (onAddCardBenefit || onAdd) && benefit.available_today && remaining > 0;
                                return <div key={benefit.id} className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 py-2 last:border-b-0">
                                    <div><p className="text-sm text-slate-800">{benefit.item_name}</p>
                                        <p className="text-xs text-slate-600">{benefit.discount_type === 'free' ? 'Free' : `${Number(benefit.discount_rate)}% off`} · {remaining} available{!benefit.available_today ? ' · Not valid today' : ''}</p></div>
                                    {canAdd && <Button type="button" variant="secondary" disabled={busy} aria-label={`Add ${benefit.item_name} line`}
                                        onClick={() => void add(benefit.item_id!, benefit.item_code!, benefit.item_name!,
                                            { card_id: card.id, card_benefit_id: benefit.id }, onAddCardBenefit ?? onAdd)}>Add line</Button>}
                                </div>;
                            })}</div>}
                        </> : <p className="border-t border-blue-100 px-4 py-2 text-xs text-slate-600">Count benefits appear under Count offers.</p>}
                    </div>;
                })}
                {canIssueCard && available?.card_designs.map((design) =>
                    <div key={design.id} className="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div><p className="font-semibold text-slate-900">{design.name}</p>
                            <p className="text-xs text-slate-600">Valid until {formatBusinessDate(design.ends_on)}</p></div>
                        <Button type="button" variant="secondary" disabled={busy} onClick={() => void issueServiceCard(design.id)}>Assign card</Button>
                    </div>)}
                {available && available.cards.length === 0 && (!canIssueCard || available.card_designs.length === 0) &&
                    <p className="text-sm text-slate-600">No service cards available.</p>}
            </div>
        </div>
        <footer className="flex flex-wrap justify-between gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3 text-sm text-slate-600">
            <span>Assign saves immediately.</span><span>Add line puts the service in Job lines.</span>
        </footer>
    </section>;
}
