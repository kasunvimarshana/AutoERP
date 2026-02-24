/**
 * Shared formatting helpers.
 *
 * Centralises the date, currency, and file-size formatting logic that was
 * previously duplicated across every page component.  Import and destructure
 * only what you need:
 *
 *   import { useFormatters } from '@/composables/useFormatters';
 *   const { formatDate, formatCurrency } = useFormatters();
 */
export function useFormatters() {
    /**
     * Format a date string or Date object as a short human-readable string.
     * Returns '—' for falsy values.
     *
     * @param {string|Date|null|undefined} value
     * @param {Intl.DateTimeFormatOptions} [options]
     * @returns {string}
     */
    function formatDate(value, options = { year: 'numeric', month: 'short', day: 'numeric' }) {
        if (!value) return '—';
        return new Date(value).toLocaleDateString('en-US', options);
    }

    /**
     * Format a numeric value as a currency string (USD by default).
     * Returns '—' for null/undefined.
     *
     * @param {number|string|null|undefined} value
     * @param {string} [currencyCode]
     * @returns {string}
     */
    function formatCurrency(value, currencyCode = 'USD') {
        if (value == null) return '—';
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currencyCode,
            minimumFractionDigits: 2,
        }).format(value);
    }

    /**
     * Format a file size in bytes as a human-readable string (e.g. "1.2 MB").
     * Returns '—' for null/undefined.
     *
     * @param {number|null|undefined} bytes
     * @returns {string}
     */
    function formatFileSize(bytes) {
        if (bytes == null) return '—';
        if (bytes === 0) return '0 B';
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return `${(bytes / Math.pow(1024, i)).toFixed(i === 0 ? 0 : 1)} ${units[i]}`;
    }

    /**
     * Format a decimal number as a percentage string (e.g. 0.1275 → "12.75%").
     * Returns '—' for null/undefined.
     *
     * @param {number|string|null|undefined} value  Decimal fraction (not 0-100).
     * @param {number} [decimals]
     * @returns {string}
     */
    function formatPercent(value, decimals = 2) {
        if (value == null) return '—';
        return `${(parseFloat(value) * 100).toFixed(decimals)}%`;
    }

    /**
     * Format a raw numeric string or number with thousands separators.
     * Useful for displaying point balances, counts, etc.
     *
     * @param {number|string|null|undefined} value
     * @returns {string}
     */
    function formatNumber(value) {
        if (value == null) return '—';
        return new Intl.NumberFormat('en-US').format(value);
    }


    function formatDateTime(value) {
        if (!value) return '—';
        return new Date(value).toLocaleString('en-US', {
            year: 'numeric', month: 'short', day: 'numeric',
            hour: '2-digit', minute: '2-digit',
        });
    }

    function formatTime(value) {
        if (!value) return '—';
        return new Intl.DateTimeFormat('en-US', { timeStyle: 'short' }).format(new Date(value));
    }

    return { formatDate, formatCurrency, formatFileSize, formatPercent, formatNumber, formatDateTime, formatTime };
}
