import { Link } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { Card } from '../../../components/ui/Card';

export function NotFoundPage() {
    return (
        <div className="mx-auto flex min-h-[60vh] w-full max-w-3xl items-center">
            <Card className="w-full p-8">
                <p className="text-xs font-medium uppercase tracking-[0.22em] text-stone-500">Navigation</p>
                <h2 className="mt-3 text-3xl font-semibold text-stone-950">Page not found</h2>
                <p className="mt-4 max-w-2xl text-sm leading-6 text-stone-600">
                    This route is not mapped into the current ERP shell yet, or the URL is not part of the frontend route
                    configuration.
                </p>
                <div className="mt-6">
                    <Link to="/">
                        <Button>Back to dashboard</Button>
                    </Link>
                </div>
            </Card>
        </div>
    );
}
