import { useCallback, useEffect, useState } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { lookupApi, type ItemLookupResource, type VehicleLookupResource } from '@/shared/api/lookupApi';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import type { NamedResource } from '@/shared/types/common';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { promotionApi, searchPromotionJobItems, type PromotionCard, type PromotionOffer, type PromotionTemplate } from '../promotionApi';

type BenefitDraft = {
    kind: 'direct' | 'earned';
    item: ItemLookupResource | null;
    offerId: string;
    discountType: 'free' | 'percentage';
    rate: string;
    uses: string;
    weekdaysOnly: boolean;
};

const emptyBenefit = (): BenefitDraft => ({ kind: 'direct', item: null, offerId: '', discountType: 'free', rate: '', uses: '1', weekdaysOnly: false });
const today = businessDateInputValue;
const vehicleLabel = (vehicle: VehicleLookupResource) => vehicle.registration_number || vehicle.name;

export default function PromotionWorkspacePage() {
    const canManage = hasPermission(useAuth(), 'promotion.manage');
    const [tab, setTab] = useState<'offers' | 'templates' | 'cards'>('offers');
    const [offers, setOffers] = useState<PromotionOffer[]>([]);
    const [templates, setTemplates] = useState<PromotionTemplate[]>([]);
    const [cards, setCards] = useState<PromotionCard[]>([]);
    const [error, setError] = useState<ApiError | null>(null);
    const [message, setMessage] = useState('');
    const [saving, setSaving] = useState(false);
    const [offer, setOffer] = useState({ code: '', name: '', paid: '5', reward: '1', scope: 'vehicle', starts: today(), ends: '' });
    const [qualifyingItem, setQualifyingItem] = useState<ItemLookupResource | null>(null);
    const [rewardItem, setRewardItem] = useState<ItemLookupResource | null>(null);
    const [template, setTemplate] = useState({ code: '', name: '', starts: today(), ends: '' });
    const [benefits, setBenefits] = useState<BenefitDraft[]>([emptyBenefit()]);
    const [issueTemplateId, setIssueTemplateId] = useState('');
    const [cardNumber, setCardNumber] = useState('');
    const [customer, setCustomer] = useState<NamedResource | null>(null);
    const [vehicle, setVehicle] = useState<VehicleLookupResource | null>(null);
    const [assignmentOfferId, setAssignmentOfferId] = useState('');
    const [managedCardId, setManagedCardId] = useState<number | null>(null);
    const [transferVehicle, setTransferVehicle] = useState<VehicleLookupResource | null>(null);
    const [changeReason, setChangeReason] = useState('');
    const managedCard = cards.find((entry) => entry.id === managedCardId);

    const refresh = useCallback(async () => {
        const [nextOffers, nextTemplates, nextCards] = await Promise.all([
            promotionApi.offers(), promotionApi.templates(), promotionApi.cards(customer?.id),
        ]);
        setOffers(nextOffers);
        setTemplates(nextTemplates);
        setCards(nextCards);
    }, [customer?.id]);
    useEffect(() => {
        let active = true;
        Promise.all([promotionApi.offers(), promotionApi.templates(), promotionApi.cards(customer?.id)])
            .then(([nextOffers, nextTemplates, nextCards]) => {
                if (!active) return;
                setOffers(nextOffers); setTemplates(nextTemplates); setCards(nextCards);
            })
            .catch((reason) => { if (active) setError(toApiError(reason)); });
        return () => { active = false; };
    }, [customer?.id]);

    const run = async (action: () => Promise<unknown>, success: string) => {
        setSaving(true); setError(null); setMessage('');
        try { await action(); await refresh(); setMessage(success); }
        catch (reason) { setError(toApiError(reason)); }
        finally { setSaving(false); }
    };

    return <>
        <ContentHeader title="Service cards & offers" description="Set up reusable offers, build promotional cards, and issue a card to a customer's vehicle." />
        <div className="mb-5 flex flex-wrap gap-2" role="tablist" aria-label="Promotions">
            {([['offers', 'Count offers'], ['templates', 'Card designs'], ['cards', 'Issued cards']] as const).map(([value, label]) =>
                <button key={value} type="button" role="tab" aria-selected={tab === value} onClick={() => { setTab(value); setError(null); }}
                    className={`rounded-lg px-4 py-2 text-sm font-medium ${tab === value ? 'bg-blue-700 text-white' : 'bg-white text-slate-700 hover:bg-slate-100'}`}>{label}</button>)}
        </div>
        <ErrorAlert error={error} />
        {message && <p role="status" className="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{message}</p>}
        {tab === 'offers' && <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            {canManage && <Panel title="Create a repeat offer">
                <p className="mb-4 text-sm text-slate-600">A paid, invoiced job item counts toward the reward. A free item does not count.</p>
                <form className="grid gap-4 sm:grid-cols-2" onSubmit={(event) => { event.preventDefault(); void run(() => promotionApi.createOffer({
                    code: offer.code, name: offer.name, qualifying_item_id: qualifyingItem?.item_id ?? qualifyingItem?.id,
                    reward_item_id: rewardItem?.item_id ?? rewardItem?.id, paid_quantity: Number(offer.paid), reward_quantity: Number(offer.reward),
                    count_scope: offer.scope, starts_on: offer.starts, ends_on: offer.ends || null,
                }), 'Offer created. Assign it to a customer or vehicle below.'); }}>
                    <Input label="Offer code" value={offer.code} required error={fieldError(error, 'code')} onChange={(event) => setOffer({ ...offer, code: event.target.value })} />
                    <Input label="Offer name" value={offer.name} required error={fieldError(error, 'name')} onChange={(event) => setOffer({ ...offer, name: event.target.value })} />
                    <LookupSelect label="Paid item" value={qualifyingItem} onChange={setQualifyingItem} search={searchPromotionJobItems} placeholder="Search job items..." required loadOnOpen minSearchLength={0} />
                    <LookupSelect label="Free reward item" value={rewardItem} onChange={setRewardItem} search={searchPromotionJobItems} placeholder="Search job items..." required loadOnOpen minSearchLength={0} />
                    <Input label="Paid units needed" type="number" min="1" value={offer.paid} required onChange={(event) => setOffer({ ...offer, paid: event.target.value })} />
                    <Input label="Free uses earned" type="number" min="1" value={offer.reward} required onChange={(event) => setOffer({ ...offer, reward: event.target.value })} />
                    <Select label="Count by" options={[{ value: 'vehicle', label: 'Vehicle' }, { value: 'customer', label: 'Customer (all vehicles)' }]}
                        value={offer.scope} onChange={(event) => setOffer({ ...offer, scope: event.target.value })} />
                    <div />
                    <Input label="Valid from" type="date" value={offer.starts} required onChange={(event) => setOffer({ ...offer, starts: event.target.value })} />
                    <Input label="Valid until (optional)" type="date" min={offer.starts} value={offer.ends} onChange={(event) => setOffer({ ...offer, ends: event.target.value })} />
                    <div className="sm:col-span-2 flex justify-end"><Button type="submit" loading={saving}>Create offer</Button></div>
                </form>
            </Panel>}
            <Panel title="Available offers">
                <div className="space-y-3">{offers.length === 0 ? <p className="text-sm text-slate-500">No offers yet.</p> : offers.map((entry) =>
                    <div key={entry.id} className="rounded-xl border border-slate-200 p-4">
                        <p className="font-semibold text-slate-900">{entry.name}</p>
                        <p className="mt-1 text-sm text-slate-600">{entry.paid_quantity} × {entry.qualifying_item_name} → {entry.reward_quantity} × {entry.reward_item_name} free</p>
                        <p className="mt-1 text-xs text-slate-500">{entry.count_scope === 'vehicle' ? 'Vehicle count' : 'Customer count'} · {entry.ends_on ? `Until ${entry.ends_on}` : 'No expiry'} · {entry.status}</p>
                        {canManage && <div className="mt-3 flex gap-2">
                            <Button type="button" variant="secondary" disabled={saving} onClick={() => void run(() => promotionApi.setOfferStatus(entry.id, entry.row_version, entry.status === 'active' ? 'paused' : 'active'), entry.status === 'active' ? 'Offer deactivated.' : 'Offer reactivated.')}>{entry.status === 'active' ? 'Deactivate' : 'Reactivate'}</Button>
                            <Button type="button" variant="danger" disabled={saving} onClick={() => { if (window.confirm(`Delete unused offer ${entry.name}? Offers with assignments or card benefits cannot be deleted.`)) void run(() => promotionApi.deleteOffer(entry.id, entry.row_version), 'Offer deleted.'); }}>Delete</Button>
                        </div>}
                    </div>)}
                </div>
            </Panel>
        </div>}
        {tab === 'templates' && <div className="grid gap-5 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
            {canManage && <Panel title="Create a card design">
                <p className="mb-4 text-sm text-slate-600">Add free or percentage benefits with limited uses. Add a repeat offer to print a buy-N reward on the same card.</p>
                <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); void run(() => promotionApi.createTemplate({
                    code: template.code, name: template.name, starts_on: template.starts, ends_on: template.ends,
                    benefits: benefits.map((entry) => entry.kind === 'earned' ? { kind: 'earned', offer_id: Number(entry.offerId) } : {
                        kind: 'direct', item_id: entry.item?.item_id ?? entry.item?.id, discount_type: entry.discountType,
                        discount_rate: entry.discountType === 'percentage' ? entry.rate : undefined,
                        use_limit: Number(entry.uses), valid_weekdays: entry.weekdaysOnly ? [1, 2, 3, 4, 5] : undefined,
                    }),
                }), 'Card design created. You can issue it on the Issued cards tab.'); }}>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input label="Card code" value={template.code} required onChange={(event) => setTemplate({ ...template, code: event.target.value })} />
                        <Input label="Card name" value={template.name} required onChange={(event) => setTemplate({ ...template, name: event.target.value })} />
                        <Input label="Valid from" type="date" value={template.starts} required onChange={(event) => setTemplate({ ...template, starts: event.target.value })} />
                        <Input label="Valid until" type="date" min={template.starts} value={template.ends} required onChange={(event) => setTemplate({ ...template, ends: event.target.value })} />
                    </div>
                    {benefits.map((entry, index) => <div key={index} className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div className="mb-3 flex items-center justify-between"><p className="text-sm font-semibold">Benefit {index + 1}</p>
                            {benefits.length > 1 && <Button type="button" variant="ghost" onClick={() => setBenefits((current) => current.filter((_, position) => position !== index))}>Remove</Button>}</div>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <Select label="Benefit type" value={entry.kind} options={[{ value: 'direct', label: 'Free or discount uses' }, { value: 'earned', label: 'Buy-N reward' }]}
                                onChange={(event) => setBenefits((current) => current.map((benefit, position) => position === index ? { ...benefit, kind: event.target.value as BenefitDraft['kind'] } : benefit))} />
                            {entry.kind === 'earned' ? <Select label="Repeat offer" value={entry.offerId} options={offers.filter((candidate) => candidate.count_scope === 'vehicle').map((candidate) => ({ value: String(candidate.id), label: candidate.name }))}
                                onChange={(event) => setBenefits((current) => current.map((benefit, position) => position === index ? { ...benefit, offerId: event.target.value } : benefit))} required /> : <>
                                <LookupSelect label="Job item" value={entry.item} search={searchPromotionJobItems} placeholder="Search job items..." required loadOnOpen minSearchLength={0}
                                    onChange={(item) => setBenefits((current) => current.map((benefit, position) => position === index ? { ...benefit, item } : benefit))} />
                                <Select label="Benefit" value={entry.discountType} options={[{ value: 'free', label: 'Free' }, { value: 'percentage', label: 'Percentage off' }]}
                                    onChange={(event) => setBenefits((current) => current.map((benefit, position) => position === index ? { ...benefit, discountType: event.target.value as BenefitDraft['discountType'] } : benefit))} />
                                {entry.discountType === 'percentage' && <Input label="Discount %" type="number" min="1" max="100" value={entry.rate} required onChange={(event) => setBenefits((current) => current.map((benefit, position) => position === index ? { ...benefit, rate: event.target.value } : benefit))} />}
                                <Input label="Maximum uses" type="number" min="1" value={entry.uses} required onChange={(event) => setBenefits((current) => current.map((benefit, position) => position === index ? { ...benefit, uses: event.target.value } : benefit))} />
                                <label className="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" checked={entry.weekdaysOnly} onChange={(event) => setBenefits((current) => current.map((benefit, position) => position === index ? { ...benefit, weekdaysOnly: event.target.checked } : benefit))} />Weekdays only</label>
                            </>}
                        </div>
                    </div>)}
                    <div className="flex justify-between"><Button type="button" variant="secondary" onClick={() => setBenefits((current) => [...current, emptyBenefit()])}>Add benefit</Button><Button type="submit" loading={saving}>Create card design</Button></div>
                </form>
            </Panel>}
            <Panel title="Card designs"><div className="space-y-3">{templates.length === 0 ? <p className="text-sm text-slate-500">No cards designed yet.</p> : templates.map((entry) =>
                <div key={entry.id} className="rounded-xl border border-slate-200 p-4"><p className="font-semibold">{entry.name}</p><p className="text-xs text-slate-500">Valid until {entry.ends_on}</p>
                    <ul className="mt-2 space-y-1 text-sm text-slate-700">{entry.benefits.map((benefit) => <li key={benefit.id}>{benefit.kind === 'earned' ? benefit.offer_name : `${benefit.item_name} · ${benefit.discount_type === 'free' ? 'Free' : `${benefit.discount_rate}% off`} · ${benefit.use_limit} uses`}</li>)}</ul>
                </div>)}</div></Panel>
        </div>}
        {tab === 'cards' && <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <div className="space-y-5"><Panel title="Customer and vehicle">
                <p className="mb-4 text-sm text-slate-600">The card is issued free to one customer and vehicle. The card's count offers follow this vehicle.</p>
                <div className="space-y-3">
                    <GenericLookupSelect label="Customer" value={customer} search={lookupApi.customers} formatLabel={(entry) => entry.name} loadOnOpen onChange={(next) => { setCustomer(next); setVehicle(null); }} />
                    <GenericLookupSelect label="Vehicle" value={vehicle} search={(params) => lookupApi.serviceVehicles(params, customer?.id)} formatLabel={vehicleLabel} loadOnOpen disabled={!customer} onChange={setVehicle} />
                </div>
            </Panel>{canManage && <><Panel title="Issue a service card"><form className="space-y-3" onSubmit={(event) => { event.preventDefault(); void run(() => promotionApi.issueCard({ template_id: Number(issueTemplateId), card_number: cardNumber, customer_id: customer?.id, vehicle_id: vehicle?.id }), 'Card issued to this vehicle.'); }}>
                <Select label="Card design" value={issueTemplateId} options={templates.map((entry) => ({ value: String(entry.id), label: `${entry.name} · until ${entry.ends_on}` }))} required onChange={(event) => setIssueTemplateId(event.target.value)} />
                <Input label="Physical card number (optional)" value={cardNumber} onChange={(event) => setCardNumber(event.target.value)} hint="Leave empty if the printed card has no number; the system will assign one." />
                <div className="flex justify-end"><Button type="submit" loading={saving} disabled={!customer || !vehicle}>Issue free card</Button></div>
            </form></Panel><Panel title="Assign a repeat offer"><form className="space-y-3" onSubmit={(event) => { event.preventDefault(); void run(() => promotionApi.assignOffer(Number(assignmentOfferId), { customer_id: customer?.id, vehicle_id: vehicle?.id }), 'Offer assigned. Progress starts from posted invoices.'); }}>
                <Select label="Offer" value={assignmentOfferId} options={offers.filter((entry) => entry.status === 'active').map((entry) => ({ value: String(entry.id), label: `${entry.name} · ${entry.count_scope}` }))} required onChange={(event) => setAssignmentOfferId(event.target.value)} />
                <div className="flex justify-end"><Button type="submit" loading={saving} disabled={!customer || !assignmentOfferId}>Assign offer</Button></div>
            </form></Panel></>}</div>
            <Panel title="Recent issued cards"><div className="space-y-3">{cards.length === 0 ? <p className="text-sm text-slate-500">No cards issued yet.</p> : cards.map((entry) =>
                <div key={entry.id} className="rounded-xl border border-slate-200 p-4"><div className="flex justify-between gap-3"><p className="font-semibold">{entry.template_name}</p><span className="text-xs text-slate-500">{entry.status}</span></div>
                    <p className="mt-1 text-sm text-slate-700">{entry.customer_name} · {entry.vehicle_registration_number}</p>
                    <p className="mt-1 text-xs text-slate-500">Card {entry.card_number} · Expires {entry.expires_on}</p>
                    {canManage && <Button type="button" variant="ghost" onClick={() => { setManagedCardId(entry.id); setTransferVehicle(null); setChangeReason(''); }}>Manage card</Button>}
                </div>)}</div></Panel>
            {canManage && managedCard && <Panel title={`Manage ${managedCard.template_name}`}>
                <p className="mb-3 text-sm text-slate-600">{managedCard.customer_name} · {managedCard.vehicle_registration_number}. Changes require a reason and are recorded in card history.</p>
                <div className="space-y-3">
                    <Input label="Reason for change" value={changeReason} required onChange={(event) => setChangeReason(event.target.value)} />
                    <div className="flex justify-end"><Button type="button" variant="secondary" disabled={saving || !changeReason.trim()} onClick={() => void run(() => promotionApi.setCardStatus(managedCard.id, {
                        status: managedCard.status === 'active' ? 'paused' : 'active', expected_version: managedCard.row_version, reason: changeReason,
                    }), managedCard.status === 'active' ? 'Card paused.' : 'Card reactivated.')}>{managedCard.status === 'active' ? 'Pause card' : 'Reactivate card'}</Button></div>
                    <GenericLookupSelect label="Transfer to another vehicle of this customer" value={transferVehicle}
                        search={(params) => lookupApi.serviceVehicles(params, managedCard.customer_id)} formatLabel={vehicleLabel} loadOnOpen
                        onChange={setTransferVehicle} />
                    <p className="text-xs text-slate-600">Card benefits follow the new vehicle. Count offer progress starts from zero there; the previous vehicle's invoice history stays recorded.</p>
                    <div className="flex justify-end"><Button type="button" disabled={saving || !changeReason.trim() || !transferVehicle || transferVehicle.id === managedCard.vehicle_id}
                        onClick={() => void run(() => promotionApi.transferCard(managedCard.id, {
                            vehicle_id: transferVehicle?.id, expected_version: managedCard.row_version, reason: changeReason,
                        }), 'Card transferred to the selected vehicle.')}>Transfer card</Button></div>
                </div>
            </Panel>}
        </div>}
    </>;
}
