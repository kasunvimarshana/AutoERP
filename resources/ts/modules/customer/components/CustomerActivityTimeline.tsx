import { AuditTimeline } from '../../../shared/components/business/AuditTimeline';

export function CustomerActivityTimeline() {
    return (
        <AuditTimeline
            events={[
                { actor: 'Customer API', description: 'Customer profile data is loaded from backend services.', time: 'Current session' },
                { actor: 'Audit module', description: 'Detailed customer audit endpoint is not exposed for this slice yet.', time: 'Not available' },
            ]}
        />
    );
}
