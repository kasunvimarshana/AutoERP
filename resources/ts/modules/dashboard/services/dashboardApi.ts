import { mockCollectionResponse } from '../../../services/mock/mockResponse';
import { dashboardMetrics } from '../mock/dashboardMock';

export const dashboardApi = {
    listMetrics: () => mockCollectionResponse(dashboardMetrics),
};
