export const endpoints = {
    finance: {
        journalEntries: '/api/finance/journal-entries',
        postingPreview: '/api/finance/journal-engine/preview',
        taxPreview: '/api/finance/tax/preview',
    },
    inventory: {
        availabilityPreview: '/api/inventory/stock/availability-preview',
        stockLevels: '/api/inventory/stock-levels',
        transfers: '/api/inventory/transfers',
    },
    payment: {
        allocationPreview: '/api/payment/allocations/preview',
        payments: '/api/payment/payments',
        refundPreview: '/api/payment/refunds/preview',
    },
    purchase: {
        invoicePreview: '/api/purchase/invoices/preview',
        orders: '/api/purchase/orders',
        payments: '/api/purchase/payments',
    },
    uom: {
        conversionPreview: '/api/uom/conversions/preview',
        units: '/api/uom/units',
    },
    vehicleService: {
        invoicePreview: (jobCardId: string | number) => `/api/vehicle-service/job-cards/${jobCardId}/invoice-preview`,
        jobCards: '/api/vehicle-service/vehicle-service-job-cards',
        labourPreview: '/api/vehicle-service/labour/preview',
        stockPreview: '/api/vehicle-service/stock/preview',
    },
};
