import { DecimalInput } from '@/shared/components/DecimalInput';
import type { SaleDiscountType } from '../sellingApi';

export function SaleDiscountInput({ label, currency, type, value, onTypeChange, onValueChange }: {
    label: string;
    currency: string;
    type: SaleDiscountType;
    value: string;
    onTypeChange: (type: SaleDiscountType) => void;
    onValueChange: (value: string) => void;
}) {
    return <div className="flex items-end gap-2">
        <DecimalInput aria-label={label} label={label} className="w-28" maxFractionDigits={2} value={value} onChange={(event) => onValueChange(event.target.value)} />
        <select aria-label={`${label} type`} className="h-10 rounded-lg border border-slate-300 bg-white px-2 text-sm text-slate-800" value={type} onChange={(event) => onTypeChange(event.target.value as SaleDiscountType)}>
            <option value="fixed">{currency}</option>
            <option value="percentage">%</option>
        </select>
    </div>;
}
