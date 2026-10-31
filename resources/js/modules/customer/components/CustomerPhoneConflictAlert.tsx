import { Link } from 'react-router-dom';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import type { ApiError } from '@/shared/api/apiError';
import { customerPermissions } from '../customerPermissions';

export function CustomerPhoneConflictAlert({ error }: { error: ApiError | null }) {
    const auth = useAuth();
    if (error?.code !== 'CUSTOMER_PHONE_IN_USE') return null;
    const customer = error.details.existing_customer as { id?: number; archived?: boolean } | null;
    return <div role="alert" className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
        <p>{error.message}</p>
        {customer?.id && !customer.archived && hasPermission(auth, customerPermissions.view) && (
            <Link className="mt-2 inline-block font-semibold underline" to={`/customers/${customer.id}`} target="_blank" rel="noopener noreferrer">View existing customer</Link>
        )}
        {customer?.archived && <p className="mt-2">This customer is archived. Ask an administrator to review the existing record.</p>}
    </div>;
}
