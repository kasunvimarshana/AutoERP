import { StatusBadge } from '@/shared/components/StatusBadge';

export function VoucherStatusStrip({ document, allocation, posting, instrument }: {
    document?: string | null;
    allocation?: string | null;
    posting?: string | null;
    instrument?: string | null;
}) {
    return (
        <div className="flex flex-wrap gap-2">
            <StatusBadge status={document} />
            {allocation && <StatusBadge status={allocation} />}
            {posting && <StatusBadge status={posting} />}
            {instrument && <StatusBadge status={instrument} />}
        </div>
    );
}
