import { Link } from 'react-router-dom';
import { ForbiddenError } from '../../api/client';
import { ErrorState } from '../../components/feedback/ErrorState';
import { Button } from '../../components/ui/Button';

type ProtectedErrorStateProps = {
    description?: string;
    title?: string;
    className?: string;
};

export function isForbiddenError(error: unknown): error is ForbiddenError {
    return error instanceof ForbiddenError;
}

export function ProtectedErrorState({
    className,
    description = 'You do not have permission to view this area with the current account and tenant.',
    title = 'Access denied',
}: ProtectedErrorStateProps) {
    return (
        <ErrorState
            action={
                <Link to="/access-denied">
                    <Button variant="secondary">Open access page</Button>
                </Link>
            }
            className={className}
            description={description}
            title={title}
        />
    );
}
