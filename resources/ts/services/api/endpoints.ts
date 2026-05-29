export const endpoints = {
    document: {
        documents: '/api/document/documents',
        preview: '/api/document/documents/preview',
    },
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
    pricing: {
        previewDiscount: '/api/pricing/discounts/preview-calculate',
        resolvePrice: '/api/pricing/resolve-price',
    },
    purchase: {
        invoicePreview: '/api/purchase/invoices/preview',
        orders: '/api/purchase/orders',
        payments: '/api/purchase/payments',
    },
    sales: {
        deliveries: '/api/sales/deliveries',
        invoicePreview: '/api/sales/invoices/preview',
        orders: '/api/sales/orders',
        payments: '/api/sales/payments',
    },
    uom: {
        conversionPreview: '/api/uom/conversions/preview',
        units: '/api/uom/units',
    },
    vehicleRental: {
        agreementPreview: '/api/vehicle-rental/agreements/preview',
        availabilityPreview: '/api/vehicle-rental/availability/preview',
        invoicePreview: '/api/vehicle-rental/invoices/preview',
        runningChartPreview: '/api/vehicle-rental/running-charts/preview',
    },
    vehicleService: {
        invoicePreview: (jobCardId: string | number) => `/api/vehicle-service/job-cards/${jobCardId}/invoice-preview`,
        jobCards: '/api/vehicle-service/vehicle-service-job-cards',
        labourPreview: '/api/vehicle-service/labour/preview',
        stockPreview: '/api/vehicle-service/stock/preview',
    },
    voucher: {
        postingPreview: '/api/voucher/vouchers/preview',
        vouchers: '/api/voucher/vouchers',
    },
};
