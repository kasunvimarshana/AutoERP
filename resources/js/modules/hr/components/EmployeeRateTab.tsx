import { useState } from 'react';
import type { NamedResource } from '@/shared/types/common';
import { Input } from '@/shared/components/Input';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { Select } from '@/shared/components/Select';
import { searchCurrencies } from '@/shared/api/referenceApi';
import { rateApi } from '../hrApi';
import type { EmployeeRate, EmployeeRatePayload } from '../hrTypes';
import { EmployeeRelationTab } from './EmployeeRelationTab';
import { useEmployeeRelationCrud } from './useEmployeeRelationCrud';

const rateTypes = ['hourly', 'daily', 'monthly', 'service', 'fixed', 'commission'] as const;

const emptyRate: EmployeeRatePayload = {
    rate_type: 'hourly',
    amount: '0.000000',
    effective_from: '',
    effective_to: '',
    is_active: true,
};

function rateDraft(row: EmployeeRate): EmployeeRatePayload {
    return {
        rate_type: row.rate_type,
        amount: row.amount,
        currency_id: row.currency_id,
        effective_from: row.effective_from,
        effective_to: row.effective_to,
        is_active: row.is_active,
    };
}

export function EmployeeRateTab({ employeeId }: { employeeId: number }) {
    const crud = useEmployeeRelationCrud(employeeId, rateApi);
    const [currency, setCurrency] = useState<NamedResource | null>(null);
    const [draft, setDraft] = useState<EmployeeRatePayload>(emptyRate);

    const startCreate = () => {
        setCurrency(null);
        setDraft(emptyRate);
        crud.startCreate();
    };

    const startEdit = (row: EmployeeRate) => {
        setCurrency(row.currency ?? null);
        setDraft(rateDraft(row));
        crud.startEdit(row);
    };

    return (
        <EmployeeRelationTab
            title="Rate"
            fields={['rate_type', 'amount', 'currency', 'effective_from', 'effective_to', 'is_active']}
            result={crud}
            open={crud.open}
            editing={crud.editing}
            submitting={crud.submitting}
            actionError={crud.actionError}
            onCreate={startCreate}
            onEdit={startEdit}
            onDelete={crud.destroy}
            onClose={crud.close}
            onSubmit={() => void crud.submit({ ...draft, currency_id: currency?.id ?? null })}
        >
            <div className="grid gap-3 md:grid-cols-2">
                <Select
                    label="Type"
                    value={draft.rate_type}
                    options={rateTypes.map((value) => ({ value, label: value }))}
                    onChange={(event) => setDraft({ ...draft, rate_type: event.target.value })}
                />
                <Input label="Amount" value={draft.amount} onChange={(event) => setDraft({ ...draft, amount: event.target.value })} />
                <LookupSelect label="Currency" value={currency} onChange={setCurrency} search={searchCurrencies} placeholder="Search currency code..." loadOnOpen minSearchLength={0} />
                <Input label="Effective from" type="date" value={draft.effective_from ?? ''} onChange={(event) => setDraft({ ...draft, effective_from: event.target.value })} />
                <Input label="Effective to" type="date" value={draft.effective_to ?? ''} onChange={(event) => setDraft({ ...draft, effective_to: event.target.value })} />
                <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={draft.is_active} onChange={(event) => setDraft({ ...draft, is_active: event.target.checked })} />
                    Active
                </label>
            </div>
        </EmployeeRelationTab>
    );
}
