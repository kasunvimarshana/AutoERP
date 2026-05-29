import { useEffect, useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { UomActivityTimeline, UomConversionTable, UomItemUsagePanel, UomPrecisionPanel, UomUnitSummaryCard } from '../components/UomComponents';
import { uomApi } from '../services/uomApi';
import type { UomAuditEntry, UomConversion, UomItemUsage, UomUnit } from '../types/uom.types';

const tabs = [
    { label: 'Overview', value: 'overview' },
    { label: 'Conversions From This Unit', value: 'from' },
    { label: 'Conversions To This Unit', value: 'to' },
    { label: 'Item Usage', value: 'usage' },
    { label: 'Activity / Audit', value: 'audit' },
];

export function UomUnitDetailPage() {
    const { id } = useParams();
    const [activeTab, setActiveTab] = useState('overview');
    const [unit, setUnit] = useState<UomUnit | null>(null);
    const [conversions, setConversions] = useState<UomConversion[]>([]);
    const [usage, setUsage] = useState<UomItemUsage | null>(null);
    const [activity, setActivity] = useState<UomAuditEntry[]>([]);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        const unitId = id ?? '';
        Promise.all([uomApi.getUnit(unitId), uomApi.listConversions(), uomApi.getUnitUsage(unitId), uomApi.getUnitActivity(unitId)])
            .then(([unitResponse, conversionResponse, usageResponse, activityResponse]) => {
                if (mounted) {
                    setUnit(unitResponse.data);
                    setConversions(conversionResponse.data);
                    setUsage(usageResponse.data);
                    setActivity(activityResponse.data);
                }
            })
            .catch((caught: unknown) => { if (mounted) setError(caught instanceof Error ? caught.message : 'Unable to load UOM detail.'); })
            .finally(() => { if (mounted) setIsLoading(false); });
        return () => { mounted = false; };
    }, [id]);

    const fromConversions = useMemo(() => conversions.filter((conversion) => conversion.fromUnitId === id), [conversions, id]);
    const toConversions = useMemo(() => conversions.filter((conversion) => conversion.toUnitId === id), [conversions, id]);

    if (isLoading) return <EmptyState description="Loading unit detail and conversion references..." title="Loading UOM unit" />;
    if (error || !unit || !usage) return <EmptyState description={error || 'UOM unit was not found.'} title="Unable to load UOM unit" />;

    return (
        <div className="space-y-6">
            <PageHeader actions={<><Link to="/uom/units"><Button variant="secondary">Back</Button></Link><Link to={`/uom/units/${unit.id}/edit`}><Button>Edit Unit</Button></Link></>} eyebrow="UOM Unit" subtitle="Unit detail shows compatibility, precision, conversions, usage, and audit without performing conversions in frontend." title={unit.name} />
            <UomUnitSummaryCard unit={unit} />
            <Card className="p-5"><Tabs active={activeTab} items={tabs} onChange={setActiveTab} /></Card>
            {activeTab === 'overview' ? <div className="grid gap-5 xl:grid-cols-[1fr_340px]"><UomPrecisionPanel unit={unit} /><UomItemUsagePanel usage={usage} /></div> : null}
            {activeTab === 'from' ? <UomConversionTable conversions={fromConversions} /> : null}
            {activeTab === 'to' ? <UomConversionTable conversions={toConversions} /> : null}
            {activeTab === 'usage' ? <UomItemUsagePanel usage={usage} /> : null}
            {activeTab === 'audit' ? <UomActivityTimeline entries={activity} /> : null}
        </div>
    );
}
