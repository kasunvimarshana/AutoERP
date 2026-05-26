import { SectionCard } from '../../../components/forms/SectionCard';

type TimelinePlaceholderProps = {
    title?: string;
    description?: string;
};

export function TimelinePlaceholder({
    title = 'Timeline',
    description = 'This workflow is ready for future event-history integration once timeline-specific backend resources are available.',
}: TimelinePlaceholderProps) {
    return (
        <SectionCard description={description} title={title}>
            <div className="space-y-3">
                <div className="rounded-2xl border border-dashed border-stone-200 bg-stone-50/80 px-4 py-4 text-sm text-stone-600">
                    Workflow event history is not exposed by the current API contract for this document.
                </div>
            </div>
        </SectionCard>
    );
}
