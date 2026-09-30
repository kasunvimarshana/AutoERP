import type { WhatsAppVerificationStatus } from '@/shared/types/whatsAppVerification';

interface WhatsAppListContact {
    name: string;
    phone: string;
    source: string;
    status: WhatsAppVerificationStatus;
}

export function WhatsAppContactCell({
    contact,
    email,
    phone,
}: {
    contact?: WhatsAppListContact | null;
    email?: string | null;
    phone?: string | null;
}) {
    if (!contact) return <span>{email ?? phone ?? '-'}</span>;

    return (
        <div className="space-y-1">
            <div className="flex items-center gap-1.5 font-medium text-slate-800">
                <span>{contact.phone}</span>
                <VerificationIndicator status={contact.status} />
            </div>
            {email ? <span className="block text-xs text-slate-500">{email}</span> : null}
        </div>
    );
}

function VerificationIndicator({ status }: { status: WhatsAppVerificationStatus }) {
    const presentation = {
        verified: { label: 'Manually verified', className: 'text-emerald-600', path: 'M9 12.75 11.25 15 15 9.75M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9Z' },
        pending: { label: 'Verification pending', className: 'text-amber-600', path: 'M12 6v6l4 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' },
        number_changed: { label: 'Number changed - reverify', className: 'text-amber-600', path: 'M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374L10.052 3.38c.866-1.5 3.03-1.5 3.896 0l7.355 12.746ZM12 16.5h.008v.008H12V16.5Z' },
        unverified: { label: 'Not verified', className: 'text-slate-400', path: 'M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374L10.052 3.38c.866-1.5 3.03-1.5 3.896 0l7.355 12.746ZM12 16.5h.008v.008H12V16.5Z' },
    }[status];

    return (
        <span className={presentation.className} title={presentation.label} aria-label={presentation.label}>
            <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" d={presentation.path} />
            </svg>
        </span>
    );
}

