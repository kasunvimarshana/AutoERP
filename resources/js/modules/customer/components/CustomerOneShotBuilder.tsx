import { useState, type ReactNode } from 'react';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import type { NamedResource } from '@/shared/types/common';
import {
    customerAddressTypes,
    type CustomerAddressPayload,
    type CustomerBankAccountPayload,
    type CustomerContactPayload,
    type CustomerCreditProfile,
} from '../customerTypes';
import { CustomerCurrencySelect } from './CustomerCurrencySelect';

type CreditProfileDraft = Omit<CustomerCreditProfile, 'id' | 'row_version'>;

export interface CustomerOneShotDraft {
    contacts: CustomerContactPayload[];
    addresses: CustomerAddressPayload[];
    bankAccounts: Array<CustomerBankAccountPayload & { currency?: NamedResource | null }>;
    creditProfile: CreditProfileDraft;
}

export const emptyCustomerOneShotDraft: CustomerOneShotDraft = {
    contacts: [],
    addresses: [],
    bankAccounts: [],
    creditProfile: { credit_limit: '0.000000', credit_period_days: null, warning_threshold_percent: '80.000000', credit_allowed: true, advance_allowed: true, allow_over_credit: false, allow_partial_payment: true, is_active: true },
};

export function CustomerOneShotBuilder({ section, value, onChange }: {
    section: 'contacts' | 'addresses' | 'bank_accounts' | 'credit_profile' | 'review';
    value: CustomerOneShotDraft;
    onChange: (value: CustomerOneShotDraft) => void;
}) {
    if (section === 'contacts') return <ContactDraft value={value} onChange={onChange} />;
    if (section === 'addresses') return <AddressDraft value={value} onChange={onChange} />;
    if (section === 'bank_accounts') return <BankDraft value={value} onChange={onChange} />;
    if (section === 'credit_profile') return <CreditDraft value={value} onChange={onChange} />;
    return <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"><Count label="Contacts" value={value.contacts.length} /><Count label="Addresses" value={value.addresses.length} /><Count label="Bank accounts" value={value.bankAccounts.length} /></div>;
}

type Props = { value: CustomerOneShotDraft; onChange: (value: CustomerOneShotDraft) => void };
function ContactDraft({ value, onChange }: Props) {
    const [name, setName] = useState(''); const [email, setEmail] = useState('');
    return <Draft title="Initial contacts" rows={value.contacts.map((row) => `${row.contact_name}${row.email ? ` / ${row.email}` : ''}`)} remove={(index) => onChange({ ...value, contacts: value.contacts.filter((_, i) => i !== index) })}><Input label="Contact name" value={name} onChange={(event) => setName(event.target.value)} /><Input label="Email" type="email" value={email} onChange={(event) => setEmail(event.target.value)} /><Button type="button" disabled={!name} onClick={() => { onChange({ ...value, contacts: [...value.contacts, { contact_name: name, email: email || null, is_primary: value.contacts.length === 0, is_active: true }] }); setName(''); setEmail(''); }}>Add contact</Button></Draft>;
}
function AddressDraft({ value, onChange }: Props) {
    const [type, setType] = useState('registered'); const [line, setLine] = useState(''); const [city, setCity] = useState('');
    return <Draft title="Initial addresses" rows={value.addresses.map((row) => `${row.address_type} / ${row.address_line_1}`)} remove={(index) => onChange({ ...value, addresses: value.addresses.filter((_, i) => i !== index) })}><Select label="Type" value={type} onChange={(event) => setType(event.target.value)} options={options(customerAddressTypes)} /><Input label="Address line 1" value={line} onChange={(event) => setLine(event.target.value)} /><Input label="City" value={city} onChange={(event) => setCity(event.target.value)} /><Button type="button" disabled={!line} onClick={() => { onChange({ ...value, addresses: [...value.addresses, { address_type: type, address_line_1: line, city: city || null, is_primary: !value.addresses.some((row) => row.address_type === type && row.is_primary), is_active: true }] }); setLine(''); setCity(''); }}>Add address</Button></Draft>;
}
function BankDraft({ value, onChange }: Props) {
    const [bank, setBank] = useState(''); const [accountName, setAccountName] = useState(''); const [number, setNumber] = useState(''); const [currency, setCurrency] = useState<NamedResource | null>(null);
    return <Draft title="Initial bank accounts" rows={value.bankAccounts.map((row) => `${row.bank_name} / ${row.account_number}${row.currency ? ` / ${row.currency.code}` : ''}`)} remove={(index) => onChange({ ...value, bankAccounts: value.bankAccounts.filter((_, i) => i !== index) })}><Input label="Bank name" value={bank} onChange={(event) => setBank(event.target.value)} /><Input label="Account name" value={accountName} onChange={(event) => setAccountName(event.target.value)} /><Input label="Account number" value={number} onChange={(event) => setNumber(event.target.value)} /><CustomerCurrencySelect value={currency} onChange={setCurrency} /><Button type="button" disabled={!bank || !accountName || !number} onClick={() => { onChange({ ...value, bankAccounts: [...value.bankAccounts, { bank_name: bank, account_name: accountName, account_number: number, currency, currency_id: currency ? Number(currency.id) : null, is_primary: value.bankAccounts.length === 0, is_active: true }] }); setBank(''); setAccountName(''); setNumber(''); setCurrency(null); }}>Add bank account</Button></Draft>;
}
function CreditDraft({ value, onChange }: Props) {
    const profile = value.creditProfile;
    const set = <K extends keyof CreditProfileDraft>(key: K, next: CreditProfileDraft[K]) => onChange({ ...value, creditProfile: { ...profile, [key]: next } });
    return <div className="space-y-4"><div className="grid gap-4 sm:grid-cols-2"><DecimalInput label="Credit limit" value={profile.credit_limit} onChange={(event) => set('credit_limit', event.target.value)} /><Input label="Credit period days" type="number" min="0" value={profile.credit_period_days ?? ''} onChange={(event) => set('credit_period_days', event.target.value ? Number(event.target.value) : null)} /><DecimalInput label="Warning threshold percent" value={profile.warning_threshold_percent} onChange={(event) => set('warning_threshold_percent', event.target.value)} /></div><div className="flex flex-wrap gap-6 text-sm"><label><input className="mr-2" type="checkbox" checked={profile.credit_allowed} onChange={(event) => set('credit_allowed', event.target.checked)} />Credit allowed</label><label><input className="mr-2" type="checkbox" checked={profile.advance_allowed} onChange={(event) => set('advance_allowed', event.target.checked)} />Advance allowed</label><label><input className="mr-2" type="checkbox" checked={profile.allow_over_credit} disabled={!profile.credit_allowed} onChange={(event) => set('allow_over_credit', event.target.checked)} />Allow over credit</label><label><input className="mr-2" type="checkbox" checked={profile.allow_partial_payment} onChange={(event) => set('allow_partial_payment', event.target.checked)} />Allow partial payment</label><label><input className="mr-2" type="checkbox" checked={profile.is_active} onChange={(event) => set('is_active', event.target.checked)} />Active</label></div></div>;
}
function Draft({ title, rows, remove, children }: { title: string; rows: string[]; remove: (index: number) => void; children: ReactNode }) { return <div><h3 className="mb-3 font-semibold">{title}</h3><div className="grid items-end gap-4 md:grid-cols-2 xl:grid-cols-4">{children}</div><div className="mt-5 space-y-2">{rows.map((row, index) => <div key={`${row}-${index}`} className="flex justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm"><span>{row}</span><button type="button" className="font-semibold text-rose-600" onClick={() => remove(index)}>Remove</button></div>)}</div></div>; }
function Count({ label, value }: { label: string; value: number }) { return <div className="rounded-lg border border-slate-200 p-4"><span className="text-sm text-slate-500">{label}</span><strong className="mt-1 block text-2xl">{value}</strong></div>; }
function options(values: readonly string[]) { return values.map((value) => ({ value, label: value.replaceAll('_', ' ') })); }
