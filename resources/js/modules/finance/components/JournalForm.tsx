import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { compareDecimalStrings, sumDecimals } from '@/shared/utils/decimal';
import type { FinanceLookups, JournalPayload } from '../financeApi';

export function JournalForm({ value, onChange, lookups, error }: {
    value: JournalPayload;
    onChange: (value: JournalPayload) => void;
    lookups: FinanceLookups;
    error: ApiError | null;
}) {
    const totalDebit = sumDecimals(value.lines.map((line) => line.debit || '0'));
    const totalCredit = sumDecimals(value.lines.map((line) => line.credit || '0'));
    const balanced = compareDecimalStrings(totalDebit, totalCredit) === 0 && compareDecimalStrings(totalDebit, '0') > 0;
    const postableAccounts = lookups.accounts.filter((account) => account.is_posting_account && account.is_active);
    const set = <K extends keyof JournalPayload>(key: K, next: JournalPayload[K]) => onChange({ ...value, [key]: next });

    return <div className="space-y-5">
        <Panel title="Journal header">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Input label="Journal date" type="date" value={value.journal_date} onChange={(event) => set('journal_date', event.target.value)} error={fieldError(error, 'journal_date')} required />
                <Select label="Journal type" value={value.journal_type} onChange={(event) => set('journal_type', event.target.value)} options={[
                    { value: 'general', label: 'General' },
                    { value: 'adjustment', label: 'Adjustment' },
                    { value: 'opening', label: 'Opening' },
                ]} />
                <Select
                    label="Posting profile"
                    value={value.posting_profile_id ?? ''}
                    onChange={(event) => set('posting_profile_id', event.target.value ? Number(event.target.value) : null)}
                    options={lookups.profiles.map((profile) => ({ value: String(profile.id), label: `${profile.code} - ${profile.name}` }))}
                    error={fieldError(error, 'posting_profile_id')}
                />
                <DecimalInput label="Exchange rate" value={value.exchange_rate} onChange={(event) => set('exchange_rate', event.target.value)} error={fieldError(error, 'exchange_rate')} required />
                <Input label="Source module" value={value.source_module ?? ''} onChange={(event) => set('source_module', event.target.value || null)} />
                <Input label="Source type" value={value.source_type ?? ''} onChange={(event) => set('source_type', event.target.value || null)} />
                <Input label="Source number" value={value.source_number ?? ''} onChange={(event) => set('source_number', event.target.value || null)} />
                <Input label="Source date" type="date" value={value.source_date ?? ''} onChange={(event) => set('source_date', event.target.value || null)} />
            </div>
            <div className="mt-4"><Textarea label="Description" value={value.description ?? ''} onChange={(event) => set('description', event.target.value || null)} error={fieldError(error, 'description')} /></div>
        </Panel>

        <Panel title="Journal lines">
            <div className="space-y-4">
                {value.lines.map((line, index) => (
                    <div key={index} className="rounded-lg border border-slate-200 p-4">
                        <div className="grid gap-4 lg:grid-cols-[minmax(14rem,2fr)_minmax(12rem,2fr)_minmax(9rem,1fr)_minmax(9rem,1fr)_auto]">
                            <Select
                                label="Account"
                                value={line.account_id ?? ''}
                                onChange={(event) => updateLine(index, { account_id: event.target.value ? Number(event.target.value) : null })}
                                options={postableAccounts.map((account) => ({ value: String(account.id), label: `${account.code} - ${account.name}` }))}
                                error={fieldError(error, `lines.${index}.account_id`)}
                                required
                            />
                            <Input label="Description" value={line.description ?? ''} onChange={(event) => updateLine(index, { description: event.target.value || null })} error={fieldError(error, `lines.${index}.description`)} />
                            <DecimalInput label="Debit" value={line.debit} onChange={(event) => updateLine(index, { debit: event.target.value })} error={fieldError(error, `lines.${index}.debit`)} />
                            <DecimalInput label="Credit" value={line.credit} onChange={(event) => updateLine(index, { credit: event.target.value })} error={fieldError(error, `lines.${index}.credit`)} />
                            <div className="flex items-end">
                                <Button type="button" variant="danger" disabled={value.lines.length <= 2} onClick={() => removeLine(index)}>Remove</Button>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
            <div className="mt-4 flex flex-wrap items-center justify-between gap-4">
                <Button type="button" variant="secondary" onClick={addLine}>Add line</Button>
                <div className={`rounded-lg px-4 py-2 text-sm font-semibold tabular-nums ${balanced ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800'}`}>
                    Debit {totalDebit} / Credit {totalCredit} {balanced ? '(balanced)' : '(not balanced)'}
                </div>
            </div>
        </Panel>
    </div>;

    function updateLine(index: number, patch: Partial<JournalPayload['lines'][number]>) {
        set('lines', value.lines.map((line, lineIndex) => lineIndex === index ? { ...line, ...patch } : line));
    }

    function addLine() {
        set('lines', [...value.lines, {
            account_id: null,
            line_number: value.lines.length + 1,
            description: null,
            debit: '0.000000',
            credit: '0.000000',
        }]);
    }

    function removeLine(index: number) {
        set('lines', value.lines
            .filter((_, lineIndex) => lineIndex !== index)
            .map((line, lineIndex) => ({ ...line, line_number: lineIndex + 1 })));
    }
}
