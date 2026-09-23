import { formatQuantity } from '@/shared/utils/formatQuantity';

export function QuantityDisplay({ value, precision, minimumPrecision }: {
    value?: string | number | null;
    precision?: number;
    minimumPrecision?: number;
}) {
    return <span className="tabular-nums">{formatQuantity(value, precision, minimumPrecision)}</span>;
}
