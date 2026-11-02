import { formatQuantity } from '@/shared/utils/formatQuantity';

export function QuantityDisplay({ value, precision }: { value?: string | number | null; precision?: number }) {
    return <span className="tabular-nums">{formatQuantity(value, precision)}</span>;
}
