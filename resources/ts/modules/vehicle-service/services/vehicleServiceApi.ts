import { mockCollectionResponse, mockPreviewResponse } from '../../../services/mock/mockResponse';
import {
    assignedSubItems,
    crewMembers,
    customers,
    jobLines,
    jobCards,
    jobTypes,
    labourAssignments,
    orderItems,
    serviceItems,
    serviceInvoices,
    servicePayments,
    servicePreview,
    subItems,
    vehicles,
} from '../mock/vehicleServiceMock';

export const vehicleServiceApi = {
    history: {
        list: () => mockCollectionResponse(jobCards),
    },
    invoices: {
        createFromJobCard: (jobCardId: string, input: unknown) => mockCollectionResponse([{ input, jobCardId }]),
        list: () => mockCollectionResponse(serviceInvoices),
        post: (invoiceId: string) => mockPreviewResponse({ invoiceId }, { note: 'Backend service invoice posting placeholder' }),
        preview: (jobCardId: string) => mockPreviewResponse({ jobCardId }, { note: 'Backend service invoice calculation preview placeholder' }),
    },
    jobCards: {
        create: (input: unknown) => mockPreviewResponse(input, { note: 'Backend job card validation and sequence preview placeholder' }),
        get: (id: string) => mockCollectionResponse(jobCards.filter((jobCard) => jobCard.id === id || jobCard.jobNumber === id)),
        list: () => mockCollectionResponse(jobCards),
        previewLabour: (input: unknown) => mockPreviewResponse(input, { note: 'Backend labour assignment and incentive preview placeholder' }),
        previewStock: (input: unknown) => mockPreviewResponse(input, { note: 'Backend stock availability/consumption preview placeholder' }),
        update: (id: string, input: unknown) => mockPreviewResponse({ id, input }, { note: 'Backend job card update validation placeholder' }),
    },
    lookups: {
        assignedSubItems: () => mockCollectionResponse(assignedSubItems),
        crewMembers: () => mockCollectionResponse(crewMembers),
        customers: () => mockCollectionResponse(customers),
        jobLines: () => mockCollectionResponse(jobLines),
        jobTypes: () => mockCollectionResponse(jobTypes),
        labourAssignments: () => mockCollectionResponse(labourAssignments),
        orderItems: () => mockCollectionResponse(orderItems),
        serviceItems: () => mockCollectionResponse(serviceItems),
        servicePreview: () => mockCollectionResponse(servicePreview),
        subItems: () => mockCollectionResponse(subItems),
        vehicles: () => mockCollectionResponse(vehicles),
    },
    payments: {
        allocate: (paymentId: string, input: unknown) => mockPreviewResponse({ input, paymentId }, { note: 'Backend service payment allocation placeholder' }),
        create: (input: unknown) => mockPreviewResponse(input, { note: 'Backend service payment validation placeholder' }),
        list: () => mockCollectionResponse(servicePayments),
        previewAllocation: (input: unknown) => mockPreviewResponse(input, { note: 'Backend service payment allocation preview placeholder' }),
    },
};
