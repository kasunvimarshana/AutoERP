import { ContentHeader } from '@/shared/components/ContentHeader';
import { ManualSupplierReturnForm } from '../components/ManualSupplierReturnForm';

export default function ManualSupplierReturnCreatePage() {
    return (
        <>
            <ContentHeader title="Manual supplier return" />
            <ManualSupplierReturnForm />
        </>
    );
}
