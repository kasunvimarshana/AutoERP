export function formatQuantity(value?: string | number | null, precision = 3): string {
    const raw = String(value ?? '0').trim();
    const match = raw.match(/^(-)?(\d+)(?:\.(\d+))?$/);
    if (!match) return '0';

    const integer = (match[2].replace(/^0+(?=\d)/, '') || '0').replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    const fraction = (match[3] ?? '').slice(0, precision).replace(/0+$/, '');

    return `${match[1] ?? ''}${integer}${fraction ? `.${fraction}` : ''}`;
}
