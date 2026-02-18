<template>
  <div class="reports-view">
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900">
        Reports
      </h1>
      <p class="mt-1 text-sm text-gray-600">
        View and generate reports for your business
      </p>
    </div>

    <!-- Report Categories -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <div
        v-for="category in reportCategories"
        :key="category.id"
        class="bg-white shadow rounded-lg p-6 hover:shadow-lg transition-shadow cursor-pointer"
        @click="selectCategory(category)"
      >
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-medium text-gray-900">
              {{ category.name }}
            </h3>
            <p class="mt-1 text-sm text-gray-500">
              {{ category.description }}
            </p>
          </div>
          <div class="flex-shrink-0">
            <svg
              class="h-8 w-8 text-primary-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                :d="category.icon"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
              />
            </svg>
          </div>
        </div>
        <div class="mt-4">
          <span class="text-sm text-gray-500">{{ category.count }} reports</span>
        </div>
      </div>
    </div>

    <!-- Selected Category Reports -->
    <div
      v-if="selectedCategory"
      class="mt-8"
    >
      <div class="mb-6">
        <button
          class="text-primary-600 hover:text-primary-700 flex items-center"
          @click="selectedCategory = null"
        >
          <svg
            class="h-5 w-5 mr-1"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 19l-7-7 7-7"
            />
          </svg>
          Back to Categories
        </button>
      </div>

      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-xl font-semibold text-gray-900">
            {{ selectedCategory.name }}
          </h2>
        </div>

        <div class="divide-y divide-gray-200">
          <div
            v-for="report in filteredReports"
            :key="report.id"
            class="px-6 py-4 hover:bg-gray-50 cursor-pointer"
            @click="generateReport(report)"
          >
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-base font-medium text-gray-900">
                  {{ report.name }}
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                  {{ report.description }}
                </p>
              </div>
              <button
                class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500"
              >
                Generate
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Report Generation Modal -->
    <div
      v-if="generatingReport"
      class="fixed inset-0 z-50 overflow-y-auto"
    >
      <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div
          class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
          @click="cancelReportGeneration"
        />

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="text-center">
              <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-primary-100">
                <svg
                  class="animate-spin h-6 w-6 text-primary-600"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  />
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                  />
                </svg>
              </div>
              <h3 class="mt-4 text-lg leading-6 font-medium text-gray-900">
                Generating Report
              </h3>
              <div class="mt-2">
                <p class="text-sm text-gray-500">
                  Please wait while we generate your report...
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

const selectedCategory = ref<any>(null);
const generatingReport = ref(false);

const reportCategories = [
  {
    id: 'financial',
    name: 'Financial Reports',
    description: 'Income statements, balance sheets, and more',
    count: 8,
    icon: 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
  },
  {
    id: 'inventory',
    name: 'Inventory Reports',
    description: 'Stock levels, movements, and valuations',
    count: 6,
    icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
  },
  {
    id: 'sales',
    name: 'Sales Reports',
    description: 'Sales performance and analytics',
    count: 10,
    icon: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
  },
  {
    id: 'purchasing',
    name: 'Purchasing Reports',
    description: 'Purchase orders and supplier analysis',
    count: 5,
    icon: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
  },
];

const reports = [
  // Financial Reports
  { id: 'f1', categoryId: 'financial', name: 'Profit & Loss Statement', description: 'Shows revenue, costs, and expenses over a period' },
  { id: 'f2', categoryId: 'financial', name: 'Balance Sheet', description: 'Snapshot of assets, liabilities, and equity' },
  { id: 'f3', categoryId: 'financial', name: 'Cash Flow Statement', description: 'Shows cash inflows and outflows' },
  { id: 'f4', categoryId: 'financial', name: 'Trial Balance', description: 'List of all ledger account balances' },
  { id: 'f5', categoryId: 'financial', name: 'Accounts Receivable Aging', description: 'Outstanding customer invoices by age' },
  { id: 'f6', categoryId: 'financial', name: 'Accounts Payable Aging', description: 'Outstanding supplier bills by age' },
  { id: 'f7', categoryId: 'financial', name: 'General Ledger', description: 'Complete list of financial transactions' },
  { id: 'f8', categoryId: 'financial', name: 'Tax Summary', description: 'Summary of tax collected and payable' },
  
  // Inventory Reports
  { id: 'i1', categoryId: 'inventory', name: 'Stock Level Report', description: 'Current stock levels by product' },
  { id: 'i2', categoryId: 'inventory', name: 'Stock Movement Report', description: 'Inventory movements over time' },
  { id: 'i3', categoryId: 'inventory', name: 'Low Stock Alert', description: 'Products below reorder level' },
  { id: 'i4', categoryId: 'inventory', name: 'Inventory Valuation', description: 'Total value of inventory' },
  { id: 'i5', categoryId: 'inventory', name: 'Dead Stock Report', description: 'Items with no movement' },
  { id: 'i6', categoryId: 'inventory', name: 'Stock Turnover Report', description: 'How quickly inventory is sold' },
  
  // Sales Reports
  { id: 's1', categoryId: 'sales', name: 'Sales Summary', description: 'Overview of sales performance' },
  { id: 's2', categoryId: 'sales', name: 'Sales by Customer', description: 'Sales breakdown by customer' },
  { id: 's3', categoryId: 'sales', name: 'Sales by Product', description: 'Sales breakdown by product' },
  { id: 's4', categoryId: 'sales', name: 'Sales by Region', description: 'Geographic sales analysis' },
  { id: 's5', categoryId: 'sales', name: 'Sales Rep Performance', description: 'Sales performance by representative' },
  { id: 's6', categoryId: 'sales', name: 'Quote Analysis', description: 'Quotation conversion rates' },
  { id: 's7', categoryId: 'sales', name: 'Customer Analysis', description: 'Customer purchasing patterns' },
  { id: 's8', categoryId: 'sales', name: 'Sales Forecast', description: 'Projected future sales' },
  { id: 's9', categoryId: 'sales', name: 'Commission Report', description: 'Sales commissions earned' },
  { id: 's10', categoryId: 'sales', name: 'Invoice Status', description: 'Status of all invoices' },
  
  // Purchasing Reports
  { id: 'p1', categoryId: 'purchasing', name: 'Purchase Order Report', description: 'All purchase orders' },
  { id: 'p2', categoryId: 'purchasing', name: 'Supplier Analysis', description: 'Supplier performance metrics' },
  { id: 'p3', categoryId: 'purchasing', name: 'Purchase by Category', description: 'Purchases grouped by category' },
  { id: 'p4', categoryId: 'purchasing', name: 'Goods Receipt Report', description: 'Items received from suppliers' },
  { id: 'p5', categoryId: 'purchasing', name: 'Supplier Payment Status', description: 'Payments due to suppliers' },
];

const filteredReports = computed(() => {
  if (!selectedCategory.value) return [];
  return reports.filter(r => r.categoryId === selectedCategory.value.id);
});

const selectCategory = (category: any) => {
  selectedCategory.value = category;
};

const generateReport = async (report: any) => {
  generatingReport.value = true;
  
  try {
    // Simulate report generation
    await new Promise(resolve => setTimeout(resolve, 2000));
    
    console.log('Report generated:', report.name);
    
    // In a real app, this would open the report or download a file
    alert(`Report "${report.name}" generated successfully!`);
  } catch (error) {
    console.error('Failed to generate report', error);
  } finally {
    generatingReport.value = false;
  }
};

const cancelReportGeneration = () => {
  generatingReport.value = false;
};
</script>
