export const ORDER_STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

export const STATUS_COLORS = {
  pending:    'bg-yellow-100 text-yellow-800',
  processing: 'bg-blue-100 text-blue-800',
  shipped:    'bg-purple-100 text-purple-800',
  delivered:  'bg-green-100 text-green-800',
  cancelled:  'bg-red-100 text-red-800',
  active:     'bg-green-100 text-green-800',
  inactive:   'bg-gray-100 text-gray-800',
  low:        'bg-yellow-100 text-yellow-800',
  critical:   'bg-red-100 text-red-800',
  ok:         'bg-green-100 text-green-800',
};

export const STOCK_THRESHOLDS = { critical: 5, low: 20 };

export const PAGE_SIZES = [10, 25, 50, 100];

export const ROLES = ['admin', 'manager', 'staff', 'viewer'];
