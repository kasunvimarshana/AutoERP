import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { SupplierForm } from '../components/SupplierForm';
import { supplierApi } from '../services/supplierApi';
import type { Supplier } from '../types/supplier.types';

export function SupplierEditPage() {
    const { id } = useParams();
    const [supplier, setSupplier] = useState<Supplier | null>(null);
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        let mounted = true;

        supplierApi
            .getSupplier(id ?? '')
            .then((response) => {
                if (mounted) {
                    setSupplier(response.data);
                }
            })
            .catch((caught: unknown) => {
                if (mounted) {
                    setError(caught instanceof Error ? caught.message : 'Unable to load supplier.');
                }
            })
            .finally(() => {
                if (mounted) {
                    setIsLoading(false);
                }
            });

        return () => {
            mounted = false;
        };
    }, [id]);

    if (isLoading) {
        return <EmptyState description="Loading supplier profile..." title="Loading supplier" />;
    }

    if (error || !supplier) {
        return <EmptyState description={error || 'Supplier was not found.'} title="Unable to edit supplier" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Master Data"
                subtitle="Edit permitted supplier inputs. Backend will validate tenant scope, tax profile, finance references, and status rules."
                title={`Edit ${supplier.name}`}
            />
            <SupplierForm mode="edit" supplier={supplier} />
        </div>
    );
}
