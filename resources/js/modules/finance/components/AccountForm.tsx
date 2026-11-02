import { fieldError, type ApiError } from '@/shared/api/apiError';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import type { AccountPayload, FinanceLookups } from '../financeApi';

export function AccountForm({ value, onChange, lookups, error, accountId }: {
    value: AccountPayload;
    onChange: (value: AccountPayload) => void;
    lookups: FinanceLookups;
    error: ApiError | null;
    accountId?: number;
}) {
    const set = <K extends keyof AccountPayload>(key: K, next: AccountPayload[K]) => onChange({ ...value, [key]: next });
    const categories = lookups.categories.filter((category) => !value.account_type_id || Number(category.account_type_id) === value.account_type_id);
    const parents = lookups.accounts.filter((account) => Number(account.id) !== accountId);

    return <div className="space-y-5">
        <Panel title="Account identity">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Input label="Account code" value={value.code} onChange={(event) => set('code', event.target.value)} error={fieldError(error, 'code')} required />
                <Input label="Account name" value={value.name} onChange={(event) => set('name', event.target.value)} error={fieldError(error, 'name')} required />
                <Select
                    label="Account type"
                    value={value.account_type_id ?? ''}
                    onChange={(event) => {
                        const accountType = lookups.types.find((type) => Number(type.id) === Number(event.target.value));
                        onChange({
                            ...value,
                            account_type_id: event.target.value ? Number(event.target.value) : null,
                            account_category_id: null,
                            normal_balance: accountType?.normal_balance ?? value.normal_balance,
                        });
                    }}
                    options={lookups.types.map((type) => ({ value: String(type.id), label: `${type.code} - ${type.name}` }))}
                    error={fieldError(error, 'account_type_id')}
                    required
                />
                <Select
                    label="Category"
                    value={value.account_category_id ?? ''}
                    onChange={(event) => set('account_category_id', event.target.value ? Number(event.target.value) : null)}
                    options={categories.map((category) => ({ value: String(category.id), label: `${category.code} - ${category.name}` }))}
                    error={fieldError(error, 'account_category_id')}
                />
                <Select
                    label="Parent account"
                    value={value.parent_id ?? ''}
                    onChange={(event) => set('parent_id', event.target.value ? Number(event.target.value) : null)}
                    options={parents.map((account) => ({ value: String(account.id), label: `${account.code} - ${account.name}` }))}
                    error={fieldError(error, 'parent_id')}
                />
                <Select
                    label="Normal balance"
                    value={value.normal_balance}
                    onChange={(event) => set('normal_balance', event.target.value as AccountPayload['normal_balance'])}
                    options={[{ value: 'debit', label: 'Debit' }, { value: 'credit', label: 'Credit' }]}
                    error={fieldError(error, 'normal_balance')}
                    required
                />
                <DecimalInput label="Opening balance" value={value.opening_balance} onChange={(event) => set('opening_balance', event.target.value)} error={fieldError(error, 'opening_balance')} />
            </div>
            <div className="mt-4"><Textarea label="Description" value={value.description ?? ''} onChange={(event) => set('description', event.target.value || null)} /></div>
        </Panel>
        <Panel title="Posting behavior">
            <div className="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <Flag label="Active" checked={value.is_active} onChange={(checked) => set('is_active', checked)} />
                <Flag label="Postable account" checked={value.is_posting_account} onChange={(checked) => set('is_posting_account', checked)} />
                <Flag label="Control account" checked={value.is_control_account} onChange={(checked) => set('is_control_account', checked)} />
                <Flag label="Cash account" checked={value.is_cash_account} onChange={(checked) => set('is_cash_account', checked)} />
                <Flag label="Bank account" checked={value.is_bank_account} onChange={(checked) => set('is_bank_account', checked)} />
                <Flag label="Tax account" checked={value.is_tax_account} onChange={(checked) => set('is_tax_account', checked)} />
            </div>
        </Panel>
    </div>;
}

function Flag({ label, checked, onChange }: { label: string; checked: boolean; onChange: (checked: boolean) => void }) {
    return <label className="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2">
        <input type="checkbox" checked={checked} onChange={(event) => onChange(event.target.checked)} />
        <span>{label}</span>
    </label>;
}
