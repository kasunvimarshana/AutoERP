/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { formatMoney } from '@/shared/utils/formatMoney';

export function MoneyDisplay({ value, currency }: { value?: string | number | null; currency?: string }) {
    return <span className="tabular-nums">{formatMoney(value, currency)}</span>;
}
