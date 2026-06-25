import { Link, Outlet, useParams } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { parsePositiveInteger } from '@/shared/utils/routeParams';

const identifierParameterPattern = /(?:^id$|id$)/i;

export function PositiveIntegerRouteParamsBoundary() {
    const params = useParams();
    const invalidIdentifier = Object.entries(params).find(([name, value]) => (
        identifierParameterPattern.test(name)
        && value !== undefined
        && parsePositiveInteger(value) === null
    ));

    if (!invalidIdentifier) return <Outlet />;

    return (
        <div className="py-20 text-center">
            <ContentHeader
                title="Invalid record link"
                description="This link does not contain a valid record identifier. No data request was sent."
            />
            <Link className="text-sm font-semibold text-sky-700 hover:underline" to="/dashboard">
                Return to dashboard
            </Link>
        </div>
    );
}
