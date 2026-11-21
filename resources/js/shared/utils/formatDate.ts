import { formatBusinessDate } from './businessDate';

export function formatDate(value?: string | null): string {
    if (!value) return '-';

    const formattedBusinessDate = formatBusinessDate(value, '');
    if (formattedBusinessDate !== '') {
        return formattedBusinessDate;
    }

    const date = new Date(value);
    return Number.isNaN(date.getTime())
        ? value
        : new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(date);
}

