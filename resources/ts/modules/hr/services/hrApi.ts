import { mockCollectionResponse, mockResponse } from '../../../services/mock/mockResponse';
import { employees } from '../mock/hrMock';

export const hrApi = {
    createEmployee: (input: unknown) => mockResponse(input),
    listEmployees: () => mockCollectionResponse(employees),
};
