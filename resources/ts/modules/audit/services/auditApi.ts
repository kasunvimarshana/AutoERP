import { mockCollectionResponse } from '../../../services/mock/mockResponse';
import { auditRecords } from '../mock/auditMock';

export const auditApi = {
    list: () => mockCollectionResponse(auditRecords),
};
