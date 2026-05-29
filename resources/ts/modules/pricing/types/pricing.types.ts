export type PriceRule = {
    id: string;
    name: string;
    priority: string;
    scope: string;
    status: string;
};

export type PricePreviewInput = {
    customerId?: string;
    itemId: string;
    quantity: string;
    transactionDate: string;
};
