import { useState, type ReactNode } from 'react';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import type { NamedResource } from '@/shared/types/common';
import {
    supplierAddressTypes,
    supplierDocumentStatuses,
    supplierDocumentTypes,
    type SupplierAddressPayload,
    type SupplierBankAccountPayload,
    type SupplierCategory,
    type SupplierContactPayload,
    type SupplierCreditProfile,
    type SupplierDocumentPayload,
    type SupplierItemMappingPayload,
} from '../supplierTypes';
import { SupplierCategorySelect } from './SupplierCategorySelect';
import { SupplierCurrencySelect } from './SupplierCurrencySelect';
import { SupplierItemSelect } from './SupplierItemSelect';
import { SupplierItemVariantSelect } from './SupplierItemVariantSelect';
import { SupplierUomSelect } from './SupplierUomSelect';

export interface SupplierOneShotDraft {
    contacts: SupplierContactPayload[];
    addresses: SupplierAddressPayload[];
    bankAccounts: Array<SupplierBankAccountPayload & { currency?: NamedResource | null }>;
    categories: SupplierCategory[];
    documents: SupplierDocumentPayload[];
    itemMappings: Array<SupplierItemMappingPayload & { item: NamedResource; uom?: NamedResource | null }>;
    creditProfile: SupplierCreditProfile;
}

export const emptySupplierOneShotDraft: SupplierOneShotDraft = {
    contacts: [],
    addresses: [],
    bankAccounts: [],
    categories: [],
    documents: [],
    itemMappings: [],
    creditProfile: { credit_limit: '0.000000', credit_period_days: null, warning_threshold_percent: '80.000000', allow_over_credit: false, allow_partial_payment: true, is_active: true },
};

export function SupplierOneShotBuilder({ section, value, onChange }: {
    section: 'contacts' | 'addresses' | 'bank_accounts' | 'categories' | 'documents' | 'item_mappings' | 'credit_profile' | 'review';
    value: SupplierOneShotDraft;
    onChange: (value: SupplierOneShotDraft) => void;
}) {
    if (section === 'contacts') return <ContactDraft value={value} onChange={onChange} />;
    if (section === 'addresses') return <AddressDraft value={value} onChange={onChange} />;
    if (section === 'bank_accounts') return <BankDraft value={value} onChange={onChange} />;
    if (section === 'categories') return <CategoryDraft value={value} onChange={onChange} />;
    if (section === 'documents') return <DocumentDraft value={value} onChange={onChange} />;
    if (section === 'item_mappings') return <MappingDraft value={value} onChange={onChange} />;
    if (section === 'credit_profile') return <CreditDraft value={value} onChange={onChange} />;
    return <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"><Count label="Contacts" value={value.contacts.length} /><Count label="Addresses" value={value.addresses.length} /><Count label="Bank accounts" value={value.bankAccounts.length} /><Count label="Categories" value={value.categories.length} /><Count label="Documents" value={value.documents.length} /><Count label="Item mappings" value={value.itemMappings.length} /></div>;
}

type Props = { value: SupplierOneShotDraft; onChange: (value: SupplierOneShotDraft) => void };
function ContactDraft({ value, onChange }: Props) {
    const [name, setName] = useState(''); const [email, setEmail] = useState('');
    return <Draft title="Initial contacts" rows={value.contacts.map((row) => `${row.contact_name}${row.email ? ` / ${row.email}` : ''}`)} remove={(index) => onChange({ ...value, contacts: value.contacts.filter((_, i) => i !== index) })}><Input label="Contact name" value={name} onChange={(event) => setName(event.target.value)} /><Input label="Email" type="email" value={email} onChange={(event) => setEmail(event.target.value)} /><Button type="button" disabled={!name} onClick={() => { onChange({ ...value, contacts: [...value.contacts, { contact_name: name, email: email || null, is_primary: value.contacts.length === 0, is_active: true }] }); setName(''); setEmail(''); }}>Add contact</Button></Draft>;
}
function AddressDraft({ value, onChange }: Props) {
    const [type, setType] = useState('registered'); const [line, setLine] = useState(''); const [city, setCity] = useState('');
    return <Draft title="Initial addresses" rows={value.addresses.map((row) => `${row.address_type} / ${row.address_line_1}`)} remove={(index) => onChange({ ...value, addresses: value.addresses.filter((_, i) => i !== index) })}><Select label="Type" value={type} onChange={(event) => setType(event.target.value)} options={options(supplierAddressTypes)} /><Input label="Address line 1" value={line} onChange={(event) => setLine(event.target.value)} /><Input label="City" value={city} onChange={(event) => setCity(event.target.value)} /><Button type="button" disabled={!line} onClick={() => { onChange({ ...value, addresses: [...value.addresses, { address_type: type, address_line_1: line, city: city || null, is_primary: !value.addresses.some((row) => row.address_type === type && row.is_primary), is_active: true }] }); setLine(''); setCity(''); }}>Add address</Button></Draft>;
}
function BankDraft({ value, onChange }: Props) {
    const [bank, setBank] = useState(''); const [accountName, setAccountName] = useState(''); const [number, setNumber] = useState(''); const [currency, setCurrency] = useState<NamedResource | null>(null);
    return <Draft title="Initial bank accounts" rows={value.bankAccounts.map((row) => `${row.bank_name} / ${row.account_number}${row.currency ? ` / ${row.currency.code}` : ''}`)} remove={(index) => onChange({ ...value, bankAccounts: value.bankAccounts.filter((_, i) => i !== index) })}><Input label="Bank name" value={bank} onChange={(event) => setBank(event.target.value)} /><Input label="Account name" value={accountName} onChange={(event) => setAccountName(event.target.value)} /><Input label="Account number" value={number} onChange={(event) => setNumber(event.target.value)} /><SupplierCurrencySelect value={currency} onChange={setCurrency} /><Button type="button" disabled={!bank || !accountName || !number} onClick={() => { onChange({ ...value, bankAccounts: [...value.bankAccounts, { bank_name: bank, account_name: accountName, account_number: number, currency, currency_id: currency ? Number(currency.id) : null, is_primary: value.bankAccounts.length === 0, is_active: true }] }); setBank(''); setAccountName(''); setNumber(''); setCurrency(null); }}>Add bank account</Button></Draft>;
}
function CategoryDraft({ value, onChange }: Props) {
    const [category, setCategory] = useState<SupplierCategory | null>(null);
    return <Draft title="Initial categories" rows={value.categories.map((row) => `${row.code} - ${row.name}`)} remove={(index) => onChange({ ...value, categories: value.categories.filter((_, i) => i !== index) })}><SupplierCategorySelect value={category} onChange={setCategory} /><Button type="button" disabled={!category || value.categories.some((row) => row.id === category.id)} onClick={() => { if (category) onChange({ ...value, categories: [...value.categories, category] }); setCategory(null); }}>Add category</Button></Draft>;
}
function DocumentDraft({ value, onChange }: Props) {
    const [type, setType] = useState('business_registration'); const [number, setNumber] = useState(''); const [status, setStatus] = useState('pending');
    return <Draft title="Initial documents" rows={value.documents.map((row) => `${row.document_type} / ${row.document_number ?? '-'}`)} remove={(index) => onChange({ ...value, documents: value.documents.filter((_, i) => i !== index) })}><Select label="Type" value={type} onChange={(event) => setType(event.target.value)} options={options(supplierDocumentTypes)} /><Input label="Document number" value={number} onChange={(event) => setNumber(event.target.value)} /><Select label="Status" value={status} onChange={(event) => setStatus(event.target.value)} options={options(supplierDocumentStatuses)} /><Button type="button" onClick={() => { onChange({ ...value, documents: [...value.documents, { document_type: type, document_number: number || null, status }] }); setNumber(''); }}>Add document</Button></Draft>;
}
function MappingDraft({ value, onChange }: Props) {
    const [item, setItem] = useState<NamedResource | null>(null); const [variant, setVariant] = useState<NamedResource | null>(null); const [uom, setUom] = useState<NamedResource | null>(null); const [quantity, setQuantity] = useState('0.000000');
    return <Draft title="Initial item mappings" rows={value.itemMappings.map((row) => `${row.item.code} - ${row.item.name} / ${row.minimum_order_quantity}`)} remove={(index) => onChange({ ...value, itemMappings: value.itemMappings.filter((_, i) => i !== index) })}><SupplierItemSelect value={item} onChange={(next) => { setItem(next); setVariant(null); }} /><SupplierItemVariantSelect item={item} value={variant} onChange={setVariant} /><SupplierUomSelect value={uom} onChange={setUom} /><Input label="Minimum order quantity" value={quantity} onChange={(event) => setQuantity(event.target.value)} /><Button type="button" disabled={!item} onClick={() => { if (!item) return; onChange({ ...value, itemMappings: [...value.itemMappings, { item, item_id: Number(item.id), item_variant_id: variant ? Number(variant.id) : null, uom, default_purchase_uom_id: uom ? Number(uom.id) : null, minimum_order_quantity: quantity, is_preferred: false, is_active: true }] }); setItem(null); setVariant(null); setUom(null); }}>Add mapping</Button></Draft>;
}
function CreditDraft({ value, onChange }: Props) {
    const profile = value.creditProfile;
    const set = <K extends keyof SupplierCreditProfile>(key: K, next: SupplierCreditProfile[K]) => onChange({ ...value, creditProfile: { ...profile, [key]: next } });
    return <div className="grid gap-4 sm:grid-cols-2"><Input label="Credit limit" value={profile.credit_limit} onChange={(event) => set('credit_limit', event.target.value)} /><Input label="Credit period days" type="number" min="0" value={profile.credit_period_days ?? ''} onChange={(event) => set('credit_period_days', event.target.value ? Number(event.target.value) : null)} /><Input label="Warning threshold percent" value={profile.warning_threshold_percent} onChange={(event) => set('warning_threshold_percent', event.target.value)} /><label className="self-end pb-2 text-sm"><input className="mr-2" type="checkbox" checked={profile.allow_over_credit} onChange={(event) => set('allow_over_credit', event.target.checked)} />Allow over credit</label></div>;
}
function Draft({ title, rows, remove, children }: { title: string; rows: string[]; remove: (index: number) => void; children: ReactNode }) { return <div><h3 className="mb-3 font-semibold">{title}</h3><div className="grid items-end gap-4 md:grid-cols-2 xl:grid-cols-4">{children}</div><div className="mt-5 space-y-2">{rows.map((row, index) => <div key={`${row}-${index}`} className="flex justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm"><span>{row}</span><button type="button" className="font-semibold text-rose-600" onClick={() => remove(index)}>Remove</button></div>)}</div></div>; }
function Count({ label, value }: { label: string; value: number }) { return <div className="rounded-lg border border-slate-200 p-4"><span className="text-sm text-slate-500">{label}</span><strong className="mt-1 block text-2xl">{value}</strong></div>; }
function options(values: readonly string[]) { return values.map((value) => ({ value, label: value.replaceAll('_', ' ') })); }
