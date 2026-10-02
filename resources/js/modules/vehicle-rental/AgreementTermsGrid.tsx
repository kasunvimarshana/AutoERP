import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { QuantityDisplay } from '@/shared/components/QuantityDisplay';
import { AgreementKind, termLabel, visibleTermKeys, type TermKey } from './agreements';

export function AgreementTermsGrid({
    kind,
    currency,
    terms,
}: {
    kind: AgreementKind;
    currency: string;
    terms: Record<TermKey, string | null>;
}) {
    return (
        <dl className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {visibleTermKeys(kind).map((key) => (
                <div key={key} className="rounded-lg bg-slate-50 p-3">
                    <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">{termLabel(kind, key)}</dt>
                    <dd className="mt-1 text-sm font-medium text-slate-900">
                        <AgreementTermValue currency={currency} term={key} value={terms[key]} />
                    </dd>
                </div>
            ))}
        </dl>
    );
}

function AgreementTermValue({ currency, term, value }: { currency: string; term: TermKey; value: string | null }) {
    if (value === null) return <>Not specified</>;
    if (term === 'included_km') return <><QuantityDisplay value={value} /> km</>;
    return <MoneyDisplay value={value} currency={currency} />;
}
