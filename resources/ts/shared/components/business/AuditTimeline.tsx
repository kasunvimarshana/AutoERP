type AuditEvent = {
    actor: string;
    description: string;
    time: string;
};

const defaultEvents: AuditEvent[] = [
    { actor: 'System', description: 'Mock record created for UI preview.', time: 'Today 09:10' },
    { actor: 'Operations', description: 'Workflow preview requested.', time: 'Today 09:18' },
];

export function AuditTimeline({ events = defaultEvents }: { events?: AuditEvent[] }) {
    return (
        <div className="rounded-lg border border-slate-200 bg-white p-5">
            <p className="text-sm font-semibold text-slate-950">Audit Timeline</p>
            <p className="mt-1 text-sm text-slate-500">Mock timeline. Real audit data will come from backend audit endpoints.</p>
            <div className="mt-4 space-y-3">
                {events.map((event) => (
                    <div className="rounded-lg border border-slate-100 bg-slate-50 p-3" key={`${event.actor}-${event.time}`}>
                        <div className="flex items-center justify-between gap-3">
                            <p className="text-sm font-semibold text-slate-800">{event.actor}</p>
                            <p className="text-xs text-slate-400">{event.time}</p>
                        </div>
                        <p className="mt-1 text-sm text-slate-500">{event.description}</p>
                    </div>
                ))}
            </div>
        </div>
    );
}
