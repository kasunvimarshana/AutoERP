type AuditEvent = {
    actor: string;
    description: string;
    time: string;
};

const defaultEvents: AuditEvent[] = [
    { actor: 'System', description: 'Record timeline initialized.', time: 'Latest activity' },
];

export function AuditTimeline({ events = defaultEvents }: { events?: AuditEvent[] }) {
    return (
        <div className="rounded-lg border border-slate-200 bg-white p-5">
            <p className="text-sm font-semibold text-slate-950">Audit Timeline</p>
            <p className="mt-1 text-sm text-slate-500">Audit events appear here when backend activity is available.</p>
            <div className="mt-4 space-y-3">
                {events.map((event, index) => (
                    <div className="rounded-lg border border-slate-100 bg-slate-50 p-3" key={`${event.actor}:${event.time}:${event.description}:${index}`}>
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
