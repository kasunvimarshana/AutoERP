export function formatDate(value: string | Date | null | undefined): string {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('en', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(typeof value === 'string' ? new Date(value) : value);
}
