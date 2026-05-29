import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { pricingApi } from '../services/pricingApi';
import type { Discount, PriceHistory, PriceList, PricingRule } from '../types/pricing.types';

export function PricingDashboardPage() {
    const [priceLists, setPriceLists] = useState<PriceList[]>([]);
    const [rules, setRules] = useState<PricingRule[]>([]);
    const [discounts, setDiscounts] = useState<Discount[]>([]);
    const [history, setHistory] = useState<PriceHistory[]>([]);

    useEffect(() => {
        let mounted = true;
        Promise.all([pricingApi.listPriceLists(), pricingApi.listPricingRules(), pricingApi.listDiscounts(), pricingApi.listPriceHistory()]).then(([priceListResponse, ruleResponse, discountResponse, historyResponse]) => {
            if (mounted) {
                setPriceLists(priceListResponse.data);
                setRules(ruleResponse.data);
                setDiscounts(discountResponse.data);
                setHistory(historyResponse.data);
            }
        });
        return () => { mounted = false; };
    }, []);

    const metrics = [
        ['Active price lists', String(priceLists.filter((row) => row.status === 'active').length), 'Backend/mock normalized'],
        ['Active rules', String(rules.filter((row) => row.status === 'active').length), 'Priority evaluated by backend'],
        ['Active discounts', String(discounts.filter((row) => row.status === 'active').length), 'Amounts calculated by backend'],
        ['Recent changes', String(history.length), 'Readonly price history'],
    ];

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<><Link to="/pricing/price-lists/new"><Button>Create Price List</Button></Link><Link to="/pricing/resolve"><Button variant="secondary">Resolve Price</Button></Link></>}
                eyebrow="Core Pricing"
                subtitle="Pricing supports sales, purchase, service, rental, customer-specific, and supplier-specific rates. Authoritative price resolving stays in backend."
                title="Pricing"
            />
            <div className="grid gap-4 md:grid-cols-4">
                {metrics.map(([label, value, helper]) => <Card className="p-5" key={label}><p className="text-sm text-slate-500">{label}</p><p className="mt-2 text-2xl font-bold text-slate-950">{value}</p><p className="mt-1 text-xs text-slate-400">{helper}</p></Card>)}
            </div>
            <div className="grid gap-4 md:grid-cols-4">
                {[
                    ['Price Lists', 'Manage sales, purchase, service, rental, and party-specific lists.', '/pricing/price-lists'],
                    ['Pricing Rules', 'Configure resolver priority, conditions, discounts, and tiers.', '/pricing/rules'],
                    ['Discounts', 'Maintain discount definitions and preview through backend.', '/pricing/discounts'],
                    ['Tiers', 'Manage quantity/rental tiers without frontend calculations.', '/pricing/tiers'],
                ].map(([title, description, path]) => (
                    <Link key={title} to={path}>
                        <Card className="h-full p-5 transition hover:border-slate-300 hover:shadow-md">
                            <p className="font-bold text-slate-950">{title}</p>
                            <p className="mt-2 text-sm text-slate-500">{description}</p>
                        </Card>
                    </Link>
                ))}
            </div>
        </div>
    );
}
