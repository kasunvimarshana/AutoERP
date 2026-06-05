import type { ApiCollectionResponse, ApiResponse } from '../../../services/api/apiResponse';
import { httpClient } from '../../../services/api/httpClient';
import type { JournalEntry, JournalPage } from '../types/journal.types';

type JournalRecord = { created_at: string; description?: string | null; entry_date: string; entry_number: string; entry_type: string; id: number; source_module?: string | null; source_reference?: string | null; status: string; total_credit: string; total_debit: string };
function mapEntry(record: JournalRecord): JournalEntry { return { createdAt: record.created_at, description: record.description, entryDate: record.entry_date, entryNumber: record.entry_number, entryType: record.entry_type, id: record.id, sourceModule: record.source_module, sourceReference: record.source_reference, status: record.status, totalCredit: record.total_credit, totalDebit: record.total_debit }; }
export const journalApi = {
    async get(id: number): Promise<JournalEntry> { const response = await httpClient<ApiResponse<JournalRecord>>(`/api/finance/journal-entries/${id}`); return mapEntry(response.data); },
    async list(query: { page: number; perPage: number; search?: string; status?: string }): Promise<JournalPage> { const response = await httpClient<ApiCollectionResponse<JournalRecord>>('/api/finance/journal-entries', { query: { page: query.page, per_page: query.perPage, search: query.search, status: query.status } }); return { entries: response.data.map(mapEntry), meta: { currentPage: response.meta?.current_page ?? query.page, lastPage: response.meta?.last_page ?? 1, perPage: response.meta?.per_page ?? query.perPage, total: response.meta?.total ?? response.data.length } }; },
};
