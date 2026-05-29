import { PageHeader } from '../../../shared/components/business/PageHeader';
import { ItemForm } from '../components/ItemForms';

export function ItemCreatePage() {
    return (
        <div className="space-y-6">
            <PageHeader eyebrow="Core Master Data" subtitle="Create the item setup first. UOM, variants, combo components, pricing references, inventory summaries, and audit live on the item detail workspace." title="Create Item" />
            <ItemForm mode="create" />
        </div>
    );
}
