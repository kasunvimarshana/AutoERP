import http from '@/services/http';

export interface ReportColumn {
  key: string;
  label: string;
  align?: 'left' | 'right';
}

export interface ReportDefinition {
  key: string;
  label: string;
  endpoint: string;
  columns: ReportColumn[];
}

export const REPORT_DEFINITIONS: ReportDefinition[] = [
  {
    key: 'sales-summary',
    label: 'Sales Summary',
    endpoint: '/reports/sales-summary',
    columns: [
      { key: 'period', label: 'Period' },
      { key: 'total_orders', label: 'Orders', align: 'right' },
      { key: 'total_revenue', label: 'Revenue', align: 'right' },
      { key: 'net_revenue', label: 'Net Revenue', align: 'right' },
    ],
  },
  {
    key: 'inventory-summary',
    label: 'Inventory',
    endpoint: '/reports/inventory-summary',
    columns: [
      { key: 'product_name', label: 'Product' },
      { key: 'total_quantity', label: 'Qty', align: 'right' },
      { key: 'total_value', label: 'Value', align: 'right' },
    ],
  },
  {
    key: 'receivables-summary',
    label: 'Receivables',
    endpoint: '/reports/receivables-summary',
    columns: [
      { key: 'status', label: 'Status' },
      { key: 'count', label: 'Count', align: 'right' },
      { key: 'total_amount', label: 'Total', align: 'right' },
    ],
  },
  {
    key: 'top-products',
    label: 'Top Products',
    endpoint: '/reports/top-products',
    columns: [
      { key: 'product_name', label: 'Product' },
      { key: 'units_sold', label: 'Units', align: 'right' },
      { key: 'total_revenue', label: 'Revenue', align: 'right' },
    ],
  },
  {
    key: 'profit-loss',
    label: 'Profit & Loss',
    endpoint: '/reports/profit-loss',
    columns: [
      { key: 'category', label: 'Category' },
      { key: 'amount', label: 'Amount', align: 'right' },
    ],
  },
  {
    key: 'purchase-summary',
    label: 'Purchase Summary',
    endpoint: '/reports/purchase-summary',
    columns: [
      { key: 'status', label: 'Status' },
      { key: 'count', label: 'Count', align: 'right' },
      { key: 'total_amount', label: 'Total', align: 'right' },
    ],
  },
  {
    key: 'expense-summary',
    label: 'Expenses',
    endpoint: '/reports/expense-summary',
    columns: [
      { key: 'category', label: 'Category' },
      { key: 'total_amount', label: 'Total', align: 'right' },
    ],
  },
  {
    key: 'tax-report',
    label: 'Tax Report',
    endpoint: '/reports/tax-report',
    columns: [
      { key: 'tax_name', label: 'Tax' },
      { key: 'rate', label: 'Rate', align: 'right' },
      { key: 'total_collected', label: 'Collected', align: 'right' },
    ],
  },
  {
    key: 'stock-expiry',
    label: 'Stock Expiry',
    endpoint: '/reports/stock-expiry',
    columns: [
      { key: 'product_name', label: 'Product' },
      { key: 'lot_number', label: 'Lot #' },
      { key: 'expires_at', label: 'Expires' },
      { key: 'quantity', label: 'Qty', align: 'right' },
    ],
  },
  {
    key: 'product-sell',
    label: 'Product Sales',
    endpoint: '/reports/product-sell',
    columns: [
      { key: 'product_name', label: 'Product' },
      { key: 'total_quantity', label: 'Qty', align: 'right' },
      { key: 'total_revenue', label: 'Revenue', align: 'right' },
    ],
  },
  {
    key: 'sales-representative',
    label: 'Sales by Agent',
    endpoint: '/reports/sales-representative',
    columns: [
      { key: 'agent_name', label: 'Agent' },
      { key: 'total_sales', label: 'Sales', align: 'right' },
      { key: 'commission', label: 'Commission', align: 'right' },
    ],
  },
];

export const reportService = {
  fetch(endpoint: string, params?: Record<string, unknown>) {
    return http.get<{ data: Record<string, unknown>[] } | Record<string, unknown>[]>(endpoint, { params });
  },
};
