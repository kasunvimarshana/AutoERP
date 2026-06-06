import { Link } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';

export default function NotFoundPage() {
    return (
        <div className="py-20 text-center">
            <ContentHeader title="Page not found" description="The requested AutoERP route does not exist." />
            <Link className="text-sm font-semibold text-sky-700 hover:underline" to="/dashboard">Return to dashboard</Link>
        </div>
    );
}
