import { Link } from 'react-router-dom';
import { navigationBreadcrumbs } from '@/app/navigation/navigationUtils';
import type { NavigationMatch } from '@/app/navigation/navigationTypes';

export function Breadcrumbs({ match }: { match: NavigationMatch | null }) {
    const items = navigationBreadcrumbs(match);

    return (
        <nav aria-label="Breadcrumb" className="min-w-0">
            <ol className="flex min-w-0 items-center gap-2 text-sm text-slate-500">
                <li><Link to="/dashboard" className="font-medium hover:text-blue-700">Dashboard</Link></li>
                {items.filter((item) => item !== 'Dashboard').map((item, index, visibleItems) => (
                    <li key={`${item}-${index}`} className="flex min-w-0 items-center gap-2">
                        <span className="text-slate-300" aria-hidden="true">/</span>
                        <span className={`truncate ${index === visibleItems.length - 1 ? 'font-semibold text-slate-800' : ''}`}>{item}</span>
                    </li>
                ))}
            </ol>
        </nav>
    );
}
