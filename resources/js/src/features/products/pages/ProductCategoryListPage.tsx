import { useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ConfirmModal } from '../../../components/feedback/ConfirmModal';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { Input } from '../../../components/forms/Input';
import { Select } from '../../../components/forms/Select';
import { StatusBadge, TableToolbar } from '../../../components/tables';
import { useToast } from '../../../app/providers/ToastProvider';
import { useTenant } from '../../auth/context/TenantContext';
import { useDeleteProductCategory, useProductCategories } from '../hooks';
import type { ProductCategory } from '../types';
import { formatDate, parseBooleanSearchParam } from '../utils';

type TreeNode = ProductCategory & {
    children: TreeNode[];
};

function buildCategoryTree(categories: ProductCategory[]) {
    const map = new Map<number, TreeNode>();
    const roots: TreeNode[] = [];

    for (const category of categories) {
        map.set(category.id, { ...category, children: [] });
    }

    for (const category of categories) {
        const node = map.get(category.id);
        if (!node) {
            continue;
        }

        if (category.parent_id && map.has(category.parent_id)) {
            map.get(category.parent_id)?.children.push(node);
            continue;
        }

        roots.push(node);
    }

    return roots.sort((left, right) => left.name.localeCompare(right.name));
}

function flattenTree(nodes: TreeNode[], depth = 0): Array<TreeNode & { depthLevel: number }> {
    return nodes.flatMap((node) => [
        { ...node, depthLevel: depth },
        ...flattenTree(node.children.sort((left, right) => left.name.localeCompare(right.name)), depth + 1),
    ]);
}

export function ProductCategoryListPage() {
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();
    const [deleteTarget, setDeleteTarget] = useState<ProductCategory | null>(null);

    const search = searchParams.get('search') ?? '';
    const isActive = parseBooleanSearchParam(searchParams.get('is_active'));
    const categoriesQuery = useProductCategories({
        tenant_id: tenantId,
        per_page: 100,
        name: search || undefined,
        is_active: isActive,
        sort: 'name',
    });
    const deleteMutation = useDeleteProductCategory();

    function updateParams(updates: Record<string, string | number | undefined>) {
        setSearchParams((current) => {
            const next = new URLSearchParams(current);

            for (const [key, value] of Object.entries(updates)) {
                if (value === undefined || value === '') {
                    next.delete(key);
                } else {
                    next.set(key, String(value));
                }
            }

            return next;
        });
    }

    async function handleDeleteConfirm() {
        if (!deleteTarget) {
            return;
        }

        const target = deleteTarget;
        await deleteMutation.mutateAsync(target.id);
        setDeleteTarget(null);
        showToast({
            title: 'Category deleted',
            description: `${target.name} was removed from the category hierarchy.`,
            tone: 'success',
        });
    }

    const treeRows = useMemo(() => {
        const items = categoriesQuery.data?.items ?? [];
        return flattenTree(buildCategoryTree(items));
    }, [categoriesQuery.data?.items]);

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                actions={
                    <Link to="/products/categories/new">
                        <Button>Add Category</Button>
                    </Link>
                }
                breadcrumbs={[{ label: 'Products', href: '/products' }, { label: 'Categories' }]}
                description="Categories now use a hierarchy-aware tree/table hybrid so nested product structures are easier to manage."
                title="Category List"
            />

            <ContentCard className="p-0">
                <TableToolbar description="Search the hierarchy, review nesting, and manage category structure without leaving the Product module." title="Category hierarchy">
                    <div className="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                        <div className="flex flex-1 flex-col gap-3 md:flex-row">
                            <Input
                                className="w-full md:max-w-sm"
                                label={undefined}
                                onChange={(event) => updateParams({ search: event.target.value || undefined })}
                                placeholder="Search category name"
                                value={search}
                            />
                            <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ is_active: event.target.value || undefined })} value={searchParams.get('is_active') ?? ''}>
                                <option value="">All statuses</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </Select>
                        </div>
                        <div className="text-sm text-stone-500">{categoriesQuery.data?.items.length ?? 0} visible categories</div>
                    </div>
                </TableToolbar>

                {categoriesQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : categoriesQuery.isError ? (
                    <ErrorState
                        action={
                            <Button onClick={() => void categoriesQuery.refetch()} variant="secondary">
                                Retry
                            </Button>
                        }
                        className="m-6"
                        description={categoriesQuery.error.message}
                        title="Unable to load categories"
                    />
                ) : treeRows.length === 0 ? (
                    <EmptyState
                        action={
                            <Link to="/products/categories/new">
                                <Button>Create category</Button>
                            </Link>
                        }
                        className="m-6"
                        description="No categories match the current filters yet."
                        title="No categories found"
                    />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-left text-sm">
                            <thead className="bg-stone-50 text-stone-500">
                                <tr>
                                    <th className="px-6 py-3 font-medium">Category</th>
                                    <th className="px-6 py-3 font-medium">Code</th>
                                    <th className="px-6 py-3 font-medium">Status</th>
                                    <th className="px-6 py-3 font-medium">Updated</th>
                                    <th className="px-6 py-3 font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {treeRows.map((category) => (
                                    <tr key={category.id} className="border-t border-stone-200/80">
                                        <td className="px-6 py-4 align-top">
                                            <div className="flex items-start gap-3" style={{ paddingLeft: `${category.depthLevel * 1.25}rem` }}>
                                                <span className="mt-1 h-2 w-2 rounded-full bg-stone-300" />
                                                <div>
                                                    <p className="font-medium text-stone-950">{category.name}</p>
                                                    <p className="mt-1 text-xs text-stone-500">{category.description || 'No description'}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">{category.code || category.slug || '-'}</td>
                                        <td className="px-6 py-4">
                                            <StatusBadge tone={category.is_active ? 'success' : 'default'}>{category.is_active ? 'Active' : 'Inactive'}</StatusBadge>
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">{formatDate(category.updated_at)}</td>
                                        <td className="px-6 py-4">
                                            <div className="flex flex-wrap gap-2">
                                                <Link to={`/products/categories/${category.id}/edit`}>
                                                    <Button className="h-9 px-3 text-xs" type="button" variant="secondary">
                                                        Edit
                                                    </Button>
                                                </Link>
                                                <Button className="h-9 px-3 text-xs" onClick={() => setDeleteTarget(category)} type="button" variant="secondary">
                                                    Delete
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </ContentCard>

            <ConfirmModal
                confirmLabel="Delete category"
                description={deleteTarget ? `Delete ${deleteTarget.name}?` : ''}
                isLoading={deleteMutation.isPending}
                onCancel={() => setDeleteTarget(null)}
                onConfirm={() => void handleDeleteConfirm()}
                open={Boolean(deleteTarget)}
                title="Delete category"
            />
        </div>
    );
}
