import { mockDelay } from './mockDelay';
import { mockPagination } from './mockPagination';
import { mockPreviewResponse } from './mockPreviewResponse';
import type { ApiCollectionResponse, ApiResponse } from '../api/apiResponse';

export async function mockResponse<T>(data: T, message = 'Mock response'): Promise<ApiResponse<T>> {
    await mockDelay();

    return { data, message };
}

export async function mockCollectionResponse<T>(data: T[]): Promise<ApiCollectionResponse<T>> {
    await mockDelay();

    return {
        data,
        ...mockPagination(data),
    };
}

export { mockPreviewResponse };
