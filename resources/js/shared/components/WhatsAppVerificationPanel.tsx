import { useState } from 'react';
import type { ApiError } from '@/shared/api/apiError';
import { toApiError } from '@/shared/api/apiError';
import { notifySuccess } from '@/shared/notifications/appToast';
import type { WhatsAppVerificationChallenge, WhatsAppVerificationState } from '@/shared/types/whatsAppVerification';
import { closePendingWhatsAppWindow, navigateToWhatsApp, openPendingWhatsAppWindow } from '@/shared/utils/whatsAppNavigation';
import { Button } from './Button';
import { ErrorAlert } from './ErrorAlert';
import { Input } from './Input';
import { LoadingState } from './LoadingState';
import { Modal } from './Modal';
import { StatusBadge } from './StatusBadge';
import { useApi } from '../hooks/useApi';

interface WhatsAppVerificationPanelProps {
    subjectId: number;
    subjectLabel: string;
    canManage: boolean;
    load: (signal?: AbortSignal) => Promise<WhatsAppVerificationState>;
    start: (idempotencyKey: string) => Promise<WhatsAppVerificationChallenge>;
    confirm: (code: string) => Promise<WhatsAppVerificationState>;
}

const statusLabels: Record<WhatsAppVerificationState['status'], string> = {
    unverified: 'Unverified',
    pending: 'Pending',
    verified: 'Verified',
    number_changed: 'Number changed - reverify',
};

export function WhatsAppVerificationPanel({
    subjectId,
    subjectLabel,
    canManage,
    load,
    start,
    confirm,
}: WhatsAppVerificationPanelProps) {
    const verification = useApi((signal) => load(signal), [subjectId], Number.isFinite(subjectId));
    const [dialogOpen, setDialogOpen] = useState(false);
    const [code, setCode] = useState('');
    const [ownershipConfirmed, setOwnershipConfirmed] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);

    if (verification.loading && !verification.data) return <LoadingState />;
    const state = verification.data;

    const begin = async () => {
        const pendingWindow = openPendingWhatsAppWindow();
        setSubmitting(true);
        setActionError(null);
        try {
            const challenge = await start(crypto.randomUUID());
            verification.setData(challenge);
            setCode('');
            setOwnershipConfirmed(false);
            setDialogOpen(true);
            if (!navigateToWhatsApp(challenge.whatsapp_url, pendingWindow)) {
                throw new Error('The server returned an invalid WhatsApp link.');
            }
        } catch (error: unknown) {
            closePendingWhatsAppWindow(pendingWindow);
            setActionError(toApiError(error));
        } finally {
            setSubmitting(false);
        }
    };

    const complete = async () => {
        if (!ownershipConfirmed) return;
        setSubmitting(true);
        setActionError(null);
        try {
            const next = await confirm(code.trim().toUpperCase());
            verification.setData(next);
            setDialogOpen(false);
            notifySuccess(`${subjectLabel} WhatsApp number manually verified.`);
        } catch (error: unknown) {
            setActionError(toApiError(error));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <section className="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 className="font-semibold text-slate-900">WhatsApp number verification</h3>
                    <p className="mt-1 text-sm text-slate-600">
                        Employee-confirmed reply-code check. AutoERP does not automatically read WhatsApp replies.
                    </p>
                </div>
                {state ? <StatusBadge status={statusLabels[state.status]} /> : null}
            </div>
            <ErrorAlert error={actionError ?? verification.error} inline />
            <div className="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                <div><span className="font-medium text-slate-700">Recipient:</span> {state?.recipient?.name ?? '-'}</div>
                <div><span className="font-medium text-slate-700">WhatsApp:</span> {state?.recipient?.phone ?? 'No active mobile number'}</div>
                {state?.verified_at ? <div><span className="font-medium text-slate-700">Verified at:</span> {new Date(state.verified_at).toLocaleString()}</div> : null}
                {state?.verified_by ? <div><span className="font-medium text-slate-700">Verified by:</span> {state.verified_by}</div> : null}
            </div>
            {canManage ? (
                <div className="mt-4 flex flex-wrap gap-2">
                    <Button
                        variant="secondary"
                        disabled={!state?.available}
                        loading={submitting && !dialogOpen}
                        loadingLabel="Opening WhatsApp..."
                        onClick={() => void begin()}
                    >
                        {state?.status === 'verified' ? 'Verify again' : 'Verify in WhatsApp'}
                    </Button>
                    {state?.status === 'pending' ? (
                        <Button variant="secondary" onClick={() => { setActionError(null); setDialogOpen(true); }}>
                            Confirm reply
                        </Button>
                    ) : null}
                </div>
            ) : null}
            {!state?.available ? (
                <p className="mt-3 text-sm text-amber-700">Add an active primary contact mobile or a master mobile number before verification.</p>
            ) : null}

            <Modal
                open={dialogOpen}
                title="Confirm WhatsApp reply"
                closeDisabled={submitting}
                onClose={() => setDialogOpen(false)}
            >
                <div className="space-y-4">
                    <p className="text-sm text-slate-600">
                        Check WhatsApp and confirm that the reply came from <strong>{state?.recipient?.phone}</strong>.
                    </p>
                    <ErrorAlert error={actionError} />
                    <Input
                        label={`Verification code received from the ${subjectLabel.toLowerCase()}`}
                        value={code}
                        placeholder="WA-1A2B3C4D"
                        autoComplete="off"
                        onChange={(event) => setCode(event.target.value)}
                    />
                    <label className="flex items-start gap-2 text-sm text-slate-700">
                        <input
                            className="mt-1"
                            type="checkbox"
                            checked={ownershipConfirmed}
                            onChange={(event) => setOwnershipConfirmed(event.target.checked)}
                        />
                        <span>I confirmed that this reply came from the same WhatsApp number.</span>
                    </label>
                    <div className="flex justify-end gap-2">
                        <Button variant="secondary" disabled={submitting} onClick={() => setDialogOpen(false)}>Cancel</Button>
                        <Button
                            loading={submitting}
                            disabled={!ownershipConfirmed || !/^WA-[A-F0-9]{8}$/i.test(code.trim())}
                            onClick={() => void complete()}
                        >
                            Mark as manually verified
                        </Button>
                    </div>
                </div>
            </Modal>
        </section>
    );
}
