import { Link } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { Card } from '../../../components/ui/Card';

export function AccessDeniedPage() {
    return (
        <div className="mx-auto flex min-h-[60vh] w-full max-w-3xl items-center">
            <Card className="w-full p-8">
                <p className="text-xs font-medium uppercase tracking-[0.22em] text-stone-500">Access</p>
                <h2 className="mt-3 text-3xl font-semibold text-stone-950">Access denied</h2>
                <p className="mt-4 max-w-2xl text-sm leading-6 text-stone-600">
                    Your session is valid, but this route is not available for the current permissions and role context.
                </p>
                <div className="mt-6">
                    <Link to="/">
                        <Button>Return to dashboard</Button>
                    </Link>
                </div>
            </Card>
        </div>
    );
}
