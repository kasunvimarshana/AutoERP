export const formatCurrency = (val, currency = 'USD') =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(val ?? 0);

export const formatDate = (val) =>
  val ? new Intl.DateTimeFormat('en-US', { dateStyle: 'medium' }).format(new Date(val)) : '—';

export const formatDateTime = (val) =>
  val ? new Intl.DateTimeFormat('en-US', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(val)) : '—';

export const formatNumber = (val) =>
  new Intl.NumberFormat('en-US').format(val ?? 0);

export const truncate = (str, len = 40) =>
  str && str.length > len ? `${str.slice(0, len)}…` : str;
