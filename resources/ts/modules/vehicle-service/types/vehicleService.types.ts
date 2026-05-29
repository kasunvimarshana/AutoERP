export type VehicleOption = {
    id: string;
    label: string;
};

export type CustomerOption = {
    id: string;
    label: string;
};

export type JobTypeOption = {
    id: string;
    label: string;
};

export type OrderItem = {
    discountLabel: string;
    id: string;
    netUnitPrice: string;
    product: string;
    quantity: number;
    subTotal: string;
};

export type ServiceItem = {
    id: string;
    label: string;
};

export type CrewMember = {
    allow?: string;
    crewId: string;
    name: string;
};

export type SubItem = {
    allow: string;
    crewId: string;
    name: string;
};

export type AssignedSubItem = {
    employeeName: string;
    incentiveAmount: string;
    serviceItem: string;
    subItem: string;
    subItemId: string;
};

export type JobLineCategory =
    | 'service'
    | 'stock'
    | 'non_inventory'
    | 'customer_supplied'
    | 'external_service'
    | 'combo';

export type JobLine = {
    category: JobLineCategory;
    description: string;
    id: string;
    quantity: string;
    stockImpact: string;
    unit: string;
};

export type LabourAssignment = {
    employee: string;
    id: string;
    labourItem: string;
    role: string;
    shareRule: string;
};

export type ServicePreview = {
    label: string;
    value: string;
};

export type JobCard = {
    customer: string;
    expectedCompletion: string;
    id: string;
    jobNumber: string;
    openedAt: string;
    previewStatus: string;
    serviceAdvisor: string;
    status: string;
    vehicle: string;
};

export type ServiceInvoice = {
    customer: string;
    id: string;
    invoiceNumber: string;
    jobNumber: string;
    previewStatus: string;
    status: string;
};

export type ServicePayment = {
    customer: string;
    id: string;
    paymentNumber: string;
    previewStatus: string;
    source: string;
    status: string;
};
