import { format, parseISO } from 'date-fns';

export const formatCurrency = (amount: number, currency = 'USD'): string => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(amount);
};

export const formatDate = (dateStr: string, fmt = 'MMM dd, yyyy'): string => {
  try {
    return format(parseISO(dateStr), fmt);
  } catch {
    return dateStr;
  }
};

export const formatDateTime = (dateStr: string): string => {
  try {
    return format(parseISO(dateStr), 'MMM dd, yyyy HH:mm');
  } catch {
    return dateStr;
  }
};

export const formatNumber = (num: number): string => {
  return new Intl.NumberFormat('en-US').format(num);
};

export const formatStatus = (status: string): string => {
  return status.charAt(0).toUpperCase() + status.slice(1).replace(/_/g, ' ');
};

export const getOrderStatusVariant = (
  status: string
): 'success' | 'warning' | 'danger' | 'info' | 'gray' | 'purple' => {
  const map: Record<string, 'success' | 'warning' | 'danger' | 'info' | 'gray' | 'purple'> = {
    pending: 'warning',
    confirmed: 'info',
    processing: 'purple',
    shipped: 'info',
    delivered: 'success',
    cancelled: 'danger',
  };
  return map[status] || 'gray';
};

export const getProductStatusVariant = (status: string): 'success' | 'danger' | 'gray' => {
  return status === 'active' ? 'success' : 'danger';
};
