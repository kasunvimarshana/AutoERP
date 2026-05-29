import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';

export function UomDashboardPage() {
    return (
        <div className="space-y-6">
            <PageHeader
                actions={<><Link to="/uom/units/new"><Button>New Unit</Button></Link><Link to="/uom/conversions/new"><Button variant="secondary">New Conversion</Button></Link></>}
                eyebrow="Core Master Data"
                subtitle="UOM controls quantity consistency across item, inventory, purchase, sales, service, rental, and pricing workflows."
                title="UOM"
            />
            <div className="grid gap-4 md:grid-cols-3">
                {[
                    ['Units', 'PCS, BOX, KG, L, HOUR, DAY, KM', '/uom/units'],
                    ['Conversions', 'BOX to PCS, L to ML, KG to G', '/uom/conversions'],
                    ['Preview', 'Backend-owned conversion result', '/uom/convert'],
                ].map(([title, description, path]) => (
                    <Link key={title} to={path}>
                        <Card className="h-full p-5 transition hover:border-slate-300 hover:shadow-md">
                            <p className="text-base font-bold text-slate-950">{title}</p>
                            <p className="mt-2 text-sm text-slate-500">{description}</p>
                        </Card>
                    </Link>
                ))}
            </div>
        </div>
    );
}
