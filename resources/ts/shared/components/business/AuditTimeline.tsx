type AuditEvent = {
    id?: string;
    actor: string;
    description: string;
    time: string;
};

export function AuditTimeline({ events = [] }: { events?: AuditEvent[] }) {
    return (
        <div className="rounded-lg border border-slate-200 bg-white p-5">
            <p className="text-sm font-semibold text-slate-950">Audit Timeline</p>
            <p className="mt-1 text-sm text-slate-500">Audit entries are loaded from backend audit endpoints.</p>
            {events.length ? (
                <div className="mt-4 space-y-3">
                    {events.map((event) => (
                        <div className="rounded-lg border border-slate-100 bg-slate-50 p-3" key={event.id ?? `${event.actor}-${event.time}-${event.description}`}>
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-sm font-semibold text-slate-800">{event.actor}</p>
                                <p className="text-xs text-slate-400">{event.time}</p>
                            </div>
                            <p className="mt-1 text-sm text-slate-500">{event.description}</p>
                        </div>
                    ))}
                </div>
            ) : (
                <p className="mt-4 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">No audit entries were returned.</p>
            )}
        </div>
    );
}
