import { AuditTimeline } from '../../../shared/components/business/AuditTimeline';

export function CustomerActivityTimeline() {
    return (
        <AuditTimeline
            events={[
                { actor: 'Customer service', description: 'Mock customer profile reviewed.', time: 'Today 09:10' },
                { actor: 'Backend audit', description: 'Real audit/history endpoint will populate this panel after integration.', time: 'Pending integration' },
            ]}
        />
    );
}
