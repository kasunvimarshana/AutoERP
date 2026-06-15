import type { ReactNode } from 'react';

export function WorkflowHeader({
    status,
    nextAction,
    secondaryActions,
    blockedReason,
    historyAction,
}: {
    status: ReactNode;
    nextAction?: ReactNode;
    secondaryActions?: ReactNode;
    blockedReason?: string;
    historyAction?: ReactNode;
}) {
    return (
        <div className="mb-5 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div className="min-w-0">
                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Current status</p>
                <div className="mt-1 flex flex-wrap items-center gap-3">
                    {status}
                    {blockedReason && <span className="text-sm text-amber-700">{blockedReason}</span>}
                </div>
            </div>
            <div className="flex flex-wrap items-center gap-2">
                {historyAction}
                {secondaryActions}
                {nextAction}
            </div>
        </div>
    );
}
