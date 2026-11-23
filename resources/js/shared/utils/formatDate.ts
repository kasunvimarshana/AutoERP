/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { businessTimeZone, formatBusinessDate } from './businessDate';

export function formatDate(value?: string | null): string {
    if (!value) return '-';

    const formattedBusinessDate = formatBusinessDate(value, '');
    if (formattedBusinessDate !== '') {
        return formattedBusinessDate;
    }

    const date = new Date(value);
    return Number.isNaN(date.getTime())
        ? value
        : new Intl.DateTimeFormat(undefined, {
            timeZone: businessTimeZone(),
            dateStyle: 'medium',
        }).format(date);
}

