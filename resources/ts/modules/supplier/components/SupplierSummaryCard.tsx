import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { Card } from '../../../shared/components/ui/Card';
import type { Supplier } from '../types/supplier.types';
import { SupplierStatusBadge } from './SupplierStatusBadge';

export function SupplierSummaryCard({ supplier }: { supplier: Supplier }) {
    return (
        <Card className="p-5">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="text-xs font-bold uppercase tracking-widest text-slate-400">{supplier.code}</p>
                        <SupplierStatusBadge status={supplier.status} />
                        <StatusBadge status={supplier.userAccessStatus === 'linked' ? 'Linked' : 'Optional'} />
                    </div>
                    <h2 className="mt-2 text-xl font-bold text-slate-950">{supplier.name}</h2>
                    <p className="mt-1 text-sm text-slate-500">{supplier.category} · {supplier.supplierType}</p>
                </div>
                <div className="grid gap-3 text-sm md:grid-cols-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-wide text-slate-400">Email</p>
                        <p className="mt-1 font-semibold text-slate-800">{supplier.email}</p>
                    </div>
                    <div>
                        <p className="text-xs font-bold uppercase tracking-wide text-slate-400">Phone</p>
                        <p className="mt-1 font-semibold text-slate-800">{supplier.phone}</p>
                    </div>
                    <div>
                        <p className="text-xs font-bold uppercase tracking-wide text-slate-400">Currency</p>
                        <p className="mt-1 font-semibold text-slate-800">{supplier.defaultCurrency || 'Backend default pending'}</p>
                    </div>
                </div>
            </div>
        </Card>
    );
}
