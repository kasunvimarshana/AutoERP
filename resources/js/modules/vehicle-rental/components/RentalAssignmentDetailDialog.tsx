import { DetailGrid } from '@/shared/components/DetailGrid';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { getRentalAssignment } from '../vehicleRentalApi';
import type { RentalReference } from '../vehicleRentalTypes';

interface RentalAssignmentDetailDialogProps {
    assignmentId: number | null;
    open: boolean;
    onClose: () => void;
}

function referenceLabel(value?: RentalReference | null): string {
    return value?.name || value?.code || '—';
}

function dateTimeLabel(value?: string | null): string {
    if (!value) return '—';
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleString();
}

function sideLabel(value: string): string {
    return value.split('_').map((part) => part.charAt(0).toUpperCase() + part.slice(1)).join(' ');
}

export function RentalAssignmentDetailDialog({
    assignmentId,
    open,
    onClose,
}: RentalAssignmentDetailDialogProps) {
    const result = useApi((signal) => {
        if (assignmentId === null) throw new Error('Rental assignment id is required.');
        return getRentalAssignment(assignmentId, signal);
    }, [assignmentId], open && assignmentId !== null);
    const assignment = result.data;

    return (
        <Modal
            open={open}
            title={assignment ? `Vehicle operation — ${referenceLabel(assignment.agreement)}` : 'Vehicle operation'}
            onClose={onClose}
        >
            <ErrorAlert error={result.error} inline />
            {result.loading && <LoadingState />}
            {assignment && (
                <div className="space-y-6">
                    <div className="flex items-center justify-between gap-4">
                        <p className="text-sm text-slate-600">{sideLabel(assignment.side)}</p>
                        <StatusBadge status={assignment.status} />
                    </div>
                    <DetailGrid items={[
                        { label: 'Agreement', value: referenceLabel(assignment.agreement) },
                        { label: 'Customer / owner', value: referenceLabel(assignment.agreement?.party) },
                        { label: 'Vehicle', value: referenceLabel(assignment.vehicle) },
                        { label: 'Driver', value: assignment.self_drive ? 'Self-drive' : referenceLabel(assignment.driver) },
                        { label: 'Starts at', value: dateTimeLabel(assignment.starts_at) },
                        { label: 'Planned / actual end', value: dateTimeLabel(assignment.ends_at) },
                        { label: 'Handover odometer', value: assignment.handover_odometer || '—' },
                        { label: 'Return odometer', value: assignment.return_odometer || '—' },
                        { label: 'Owner-supply source', value: referenceLabel(assignment.source_assignment?.agreement) },
                        { label: 'Replaces vehicle', value: referenceLabel(assignment.replaces_assignment?.vehicle) },
                        { label: 'Replacement reason', value: assignment.replacement_reason || '—' },
                        { label: 'Version', value: assignment.row_version },
                    ]} />
                    <div>
                        <h3 className="text-sm font-semibold text-slate-900">Custody history</h3>
                        {(assignment.custody_events ?? []).length === 0 ? (
                            <p className="mt-2 text-sm text-slate-500">No handover or return events are recorded.</p>
                        ) : (
                            <div className="mt-3 overflow-x-auto rounded-lg border border-slate-200">
                                <table className="min-w-full text-left text-sm">
                                    <thead className="border-b border-slate-200 bg-slate-50 text-slate-600">
                                        <tr>
                                            <th className="px-3 py-2 font-medium">Event</th>
                                            <th className="px-3 py-2 font-medium">Time</th>
                                            <th className="px-3 py-2 font-medium">Odometer</th>
                                            <th className="px-3 py-2 font-medium">Fuel</th>
                                            <th className="px-3 py-2 font-medium">Condition / damage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {(assignment.custody_events ?? []).map((event) => (
                                            <tr key={event.id} className="border-b border-slate-100 last:border-0">
                                                <td className="px-3 py-2">{sideLabel(event.event_type)}</td>
                                                <td className="px-3 py-2">{dateTimeLabel(event.event_at)}</td>
                                                <td className="px-3 py-2">{event.odometer}</td>
                                                <td className="px-3 py-2">{event.fuel_level || '—'}</td>
                                                <td className="px-3 py-2 whitespace-pre-wrap">
                                                    {[event.condition_notes, event.damage_notes].filter(Boolean).join('\n') || '—'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </div>
            )}
        </Modal>
    );
}
