import type {
    AssignedSubItem,
    CrewMember,
    CustomerOption,
    JobTypeOption,
    JobLine,
    JobCard,
    LabourAssignment,
    OrderItem,
    ServiceInvoice,
    ServicePreview,
    ServiceItem,
    ServicePayment,
    SubItem,
    VehicleOption,
} from '../types/vehicleService.types';

export const vehicles: VehicleOption[] = [
    { id: 'veh-001', label: 'WP CAD-4521 | Toyota HiAce' },
    { id: 'veh-002', label: 'WP KA-7781 | Nissan Caravan' },
    { id: 'veh-003', label: 'CP CAB-9410 | Mitsubishi L200' },
];

export const customers: CustomerOption[] = [
    { id: 'cus-001', label: 'John Workshop' },
    { id: 'cus-002', label: 'Northline Logistics' },
    { id: 'cus-003', label: 'Metro Hire Services' },
];

export const jobTypes: JobTypeOption[] = [
    { id: 'full-service', label: 'Full Service' },
    { id: 'running-repair', label: 'Running Repair' },
    { id: 'inspection', label: 'Inspection' },
];

export const products = [
    { id: 'oil-filter', label: 'Oil Filter' },
    { id: 'brake-pads', label: 'Brake Pads' },
    { id: 'battery', label: 'Battery 70Ah' },
];

export const orderItems: OrderItem[] = [
    { discountLabel: '0%', id: '1', netUnitPrice: '$15.00', product: 'Oil Filter', quantity: 1, subTotal: '$15.00' },
    { discountLabel: '10%', id: '2', netUnitPrice: '$45.00', product: 'Brake Pads', quantity: 2, subTotal: '$81.00' },
    { discountLabel: '0%', id: '3', netUnitPrice: '$25.00', product: 'Bmen Sergs', quantity: 1, subTotal: '$25.00' },
    { discountLabel: '10%', id: '4', netUnitPrice: '$45.00', product: 'Sorking Pads', quantity: 2, subTotal: '$81.00' },
    { discountLabel: '0%', id: '5', netUnitPrice: '$15.00', product: 'Spotish Series', quantity: 1, subTotal: '$15.00' },
    { discountLabel: '0%', id: '6', netUnitPrice: '$15.00', product: 'Oil Filter', quantity: 1, subTotal: '$15.00' },
    { discountLabel: '10%', id: '7', netUnitPrice: '$45.00', product: 'Brake Pads', quantity: 2, subTotal: '$81.00' },
    { discountLabel: '5%', id: '8', netUnitPrice: '$120.00', product: 'Battery 70Ah', quantity: 1, subTotal: '$114.00' },
];

export const serviceItems: ServiceItem[] = [
    { id: 'full-service', label: 'Full service' },
    { id: 'engine-tune', label: 'Engine tune up' },
    { id: 'wash', label: 'Detailing package' },
];

export const supervisors = [
    { id: 'sup-001', label: 'Select' },
    { id: 'sup-002', label: 'Kasun Perera' },
    { id: 'sup-003', label: 'Nimal Fernando' },
];

export const subItems: SubItem[] = [
    { allow: '35.00', crewId: '270', name: 'Body Wash with Carpets' },
    { allow: '100.00', crewId: '271', name: 'Finishing' },
    { allow: '100.00', crewId: '272', name: 'Technical' },
];

export const crewMembers: CrewMember[] = [
    { crewId: '270', name: 'Adman' },
    { crewId: '271', name: 'dulana' },
    { crewId: '272', name: 'Tharindu' },
];

export const assignedSubItems: AssignedSubItem[] = [
    { employeeName: 'Asana', incentiveAmount: '$15.00', serviceItem: 'Full Service', subItem: 'Body wash', subItemId: '270' },
    { employeeName: 'Brake Pads', incentiveAmount: '$81.00', serviceItem: 'Full Service', subItem: 'Finishing', subItemId: '271' },
    { employeeName: 'Bmen Sergs', incentiveAmount: '$25.00', serviceItem: 'Full Service', subItem: 'Vaccum', subItemId: '275' },
    { employeeName: 'Sorking Pads', incentiveAmount: '$81.00', serviceItem: 'Full Service', subItem: 'Technical', subItemId: '272' },
];

export const jobLines: JobLine[] = [
    { category: 'service', description: 'Full inspection service package', id: 'line-001', quantity: '1', stockImpact: 'No direct stock impact', unit: 'service' },
    { category: 'stock', description: 'Oil filter - backend availability preview required', id: 'line-002', quantity: '1', stockImpact: 'Stock issue on backend confirmation', unit: 'pcs' },
    { category: 'non_inventory', description: 'Workshop consumables', id: 'line-003', quantity: '1', stockImpact: 'No stock ledger impact', unit: 'lot' },
    { category: 'customer_supplied', description: 'Customer supplied engine oil', id: 'line-004', quantity: '4', stockImpact: 'No stock impact', unit: 'ltr' },
    { category: 'external_service', description: 'External wheel alignment provider', id: 'line-005', quantity: '1', stockImpact: 'Provider payable preview required', unit: 'service' },
    { category: 'combo', description: 'Premium service bundle - expanded by backend', id: 'line-006', quantity: '1', stockImpact: 'Backend expands stock and labour effects', unit: 'bundle' },
];

export const labourAssignments: LabourAssignment[] = [
    { employee: 'Kasun Perera', id: 'lab-001', labourItem: 'Inspection and diagnosis', role: 'Supervisor', shareRule: 'Backend incentive preview' },
    { employee: 'Nimal Fernando', id: 'lab-002', labourItem: 'Engine tune up', role: 'Technician', shareRule: 'Backend split/share preview' },
    { employee: 'Tharindu Silva', id: 'lab-003', labourItem: 'Detailing package', role: 'Technician', shareRule: 'Backend incentive preview' },
];

export const servicePreview: ServicePreview[] = [
    { label: 'Sequence preview', value: 'Backend generated after tenant/org validation' },
    { label: 'Invoice preview', value: 'Mock backend preview pending' },
    { label: 'Stock availability', value: 'Mock backend preview pending' },
    { label: 'Labour incentives', value: 'Mock backend preview pending' },
];

export const jobCards: JobCard[] = [
    {
        customer: 'Northline Logistics',
        expectedCompletion: '2026-05-31 16:00',
        id: 'job-001',
        jobNumber: 'JC-MOCK-001',
        openedAt: '2026-05-29 09:10',
        previewStatus: 'Invoice preview pending',
        serviceAdvisor: 'Kasun Perera',
        status: 'In Progress',
        vehicle: 'WP CAD-4521 | Toyota HiAce',
    },
    {
        customer: 'Metro Hire Services',
        expectedCompletion: '2026-06-01 12:00',
        id: 'job-002',
        jobNumber: 'JC-MOCK-002',
        openedAt: '2026-05-29 10:30',
        previewStatus: 'Stock preview ready',
        serviceAdvisor: 'Nimal Fernando',
        status: 'Draft',
        vehicle: 'WP KA-7781 | Nissan Caravan',
    },
    {
        customer: 'John Workshop',
        expectedCompletion: '2026-06-02 15:00',
        id: 'job-003',
        jobNumber: 'JC-MOCK-003',
        openedAt: '2026-05-28 14:15',
        previewStatus: 'Labour preview pending',
        serviceAdvisor: 'Kasun Perera',
        status: 'Submitted',
        vehicle: 'CP CAB-9410 | Mitsubishi L200',
    },
];

export const serviceInvoices: ServiceInvoice[] = [
    { customer: 'Northline Logistics', id: 'svc-inv-001', invoiceNumber: 'SINV-MOCK-001', jobNumber: 'JC-MOCK-001', previewStatus: 'Backend totals preview only', status: 'Draft' },
    { customer: 'Metro Hire Services', id: 'svc-inv-002', invoiceNumber: 'SINV-MOCK-002', jobNumber: 'JC-MOCK-002', previewStatus: 'Backend tax preview only', status: 'Preview' },
];

export const servicePayments: ServicePayment[] = [
    { customer: 'Northline Logistics', id: 'svc-pay-001', paymentNumber: 'SPAY-MOCK-001', previewStatus: 'Backend allocation preview only', source: 'SINV-MOCK-001', status: 'Unallocated' },
    { customer: 'Metro Hire Services', id: 'svc-pay-002', paymentNumber: 'SPAY-MOCK-002', previewStatus: 'Backend allocation preview only', source: 'SINV-MOCK-002', status: 'Draft' },
];

export const statuses = {
    job: [
        { label: 'Pending', value: 'pending' },
        { label: 'In Progress', value: 'in-progress' },
        { label: 'Completed', value: 'completed' },
    ],
    payment: [
        { label: 'Unpaid', value: 'unpaid' },
        { label: 'Partially Paid', value: 'partial' },
        { label: 'Paid', value: 'paid' },
    ],
};
