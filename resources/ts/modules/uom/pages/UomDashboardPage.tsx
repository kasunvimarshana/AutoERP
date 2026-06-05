import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { uomApi } from '../services/uomApi';

export function UomDashboardPage() {
    const [summary, setSummary] = useState({ activeConversions: 0, baseUnits: 0, units: 0 });
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        Promise.all([uomApi.listUnits(), uomApi.listConversions()])
            .then(([unitResponse, conversionResponse]) => {
                if (mounted) {
                    setSummary({
                        activeConversions: conversionResponse.data.filter((conversion) => conversion.isActive).length,
                        baseUnits: unitResponse.data.filter((unit) => unit.isBase).length,
                        units: unitResponse.data.length,
                    });
                }
            })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load UOM summary.'); })
            .finally(() => { if (mounted) setIsLoading(false); });

        return () => { mounted = false; };
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<><Link to="/uom/units/new"><Button>New Unit</Button></Link><Link to="/uom/conversions/new"><Button variant="secondary">New Conversion</Button></Link></>}
                eyebrow="Core Master Data"
                subtitle="Units and conversions for item, inventory, service, purchase, and warehouse contexts."
                title="UOM"
            />
            {isLoading ? <EmptyState description="Loading UOM summary..." title="Loading UOM" /> : null}
            {error ? <EmptyState description={error} title="UOM summary unavailable" /> : null}
            {!isLoading && !error ? (
                <div className="grid gap-4 md:grid-cols-3">
                    {[
                        ['Units', String(summary.units), '/uom/units'],
                        ['Base units', String(summary.baseUnits), '/uom/units'],
                        ['Active conversions', String(summary.activeConversions), '/uom/conversions'],
                    ].map(([title, value, path]) => (
                        <Link key={title} to={path}>
                            <Card className="h-full p-5 transition hover:border-slate-300 hover:shadow-md">
                                <p className="text-sm text-slate-500">{title}</p>
                                <p className="mt-2 text-2xl font-bold text-slate-950">{value}</p>
                            </Card>
                        </Link>
                    ))}
                </div>
            ) : null}
        </div>
    );
}
