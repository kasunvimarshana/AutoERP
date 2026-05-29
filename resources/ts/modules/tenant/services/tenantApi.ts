import { mockCollectionResponse } from '../../../services/mock/mockResponse';
import { tenantRecords } from '../mock/tenantMock';

export const tenantApi = {
    list: () => mockCollectionResponse(tenantRecords),
};
