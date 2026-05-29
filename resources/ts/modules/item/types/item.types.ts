export type Item = {
    code: string;
    id: string;
    name: string;
    status: string;
    stockMode: string;
    type: string;
    uom: string;
};

export type ItemFormInput = {
    category: string;
    name: string;
    type: string;
    uom: string;
};
