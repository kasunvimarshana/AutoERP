import { LoadingState } from '@/shared/components/LoadingState';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { RecordTable } from '@/shared/components/RecordTable';
import { useApi } from '@/shared/hooks/useApi';
import { listEmployeeStatusHistory } from '../hrApi';
export function EmployeeStatusHistoryTab({ employeeId }: { employeeId: number }) { const result = useApi((signal) => listEmployeeStatusHistory(employeeId, 1, signal), [employeeId]); if (result.loading) return <LoadingState label="Loading status history..." />; return <><ErrorAlert error={result.error} /><RecordTable rows={(result.data?.data ?? []) as unknown as Record<string, unknown>[]} fields={['old_status', 'new_status', 'reason', 'changed_by', 'changed_at']} rowKey={(row, index) => String(row.id ?? row.changed_at ?? `status-${index}`)} /></>; }
