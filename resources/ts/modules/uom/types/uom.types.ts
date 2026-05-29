export type Unit = {
    category: string;
    code: string;
    id: string;
    name: string;
    precision: number;
    status: string;
};

export type UomConversionInput = {
    fromUnit: string;
    quantity: string;
    toUnit: string;
};
