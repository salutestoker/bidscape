import { Head, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { MoreVertical, PackageOpen, Plus, Save } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import AppLayout from '@/Components/Bidscape/AppLayout';
import { DataTable } from '@/Components/Bidscape/DataTable';
import {
    Drawer,
    Paginated,
    Pagination,
    Panel,
    PrimaryButton,
    SearchInput,
    SecondaryButton,
} from '@/Components/Bidscape/UI';
import { useLiveTableFilters } from '@/hooks/useLiveTableFilters';
import { money, percent } from '@/lib/format';
import {
    Field,
    SettingsBackLink,
    basisPointsToPercent,
    centsToDollars,
    dollarsToCents,
    percentToBasisPoints,
} from './Partials';

interface Material {
    id: number;
    name: string;
    type: string;
    type_label: string;
    sku?: string | null;
    category: string;
    unit: string;
    unit_cost_cents: number;
    markup_basis_points: number;
    selling_price_cents: number;
    vendor?: string | null;
    description?: string | null;
    notes?: string | null;
    is_active: boolean;
    photo_path?: string | null;
}

interface MaterialForm {
    name: string;
    type: string;
    sku: string;
    category: string;
    unit: string;
    unit_cost_cents: string | number;
    markup_basis_points: string | number;
    vendor: string;
    description: string;
    notes: string;
    is_active: boolean;
}

interface Props {
    materials: Paginated<Material>;
    types: Array<{ value: string; label: string }>;
    filters: { search?: string; sort?: string; direction?: string };
    defaultMarkupBasisPoints: number;
}

export default function PriceSheet({
    materials,
    types,
    filters,
    defaultMarkupBasisPoints,
}: Props) {
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editing, setEditing] = useState<Material | null>(null);
    const form = useForm<MaterialForm>(blankMaterial(defaultMarkupBasisPoints));
    const liveFilters = useLiveTableFilters('/settings/price-sheet', filters);
    const hasFilters = Object.values(liveFilters.values).some(Boolean);
    const columns = useMemo<ColumnDef<Material>[]>(
        () => [
            {
                accessorKey: 'name',
                header: 'Item',
                cell: ({ row }) => (
                    <div className="flex items-center gap-4">
                        <img
                            src={
                                row.original.photo_path ??
                                '/images/demo/gravel.svg'
                            }
                            alt=""
                            className="size-10 rounded-md object-cover"
                        />
                        <div>
                            <strong>{row.original.name}</strong>
                            <p className="text-xs text-muted-foreground">
                                {row.original.sku ?? 'No SKU'}
                            </p>
                        </div>
                    </div>
                ),
            },
            { accessorKey: 'type_label', header: 'Type' },
            { accessorKey: 'category', header: 'Category' },
            { accessorKey: 'unit', header: 'Unit' },
            {
                accessorKey: 'unit_cost_cents',
                header: 'Cost',
                cell: ({ row }) => money(row.original.unit_cost_cents),
            },
            {
                accessorKey: 'markup_basis_points',
                header: 'Markup',
                cell: ({ row }) => percent(row.original.markup_basis_points),
            },
            {
                accessorKey: 'selling_price_cents',
                header: 'Selling Price',
                cell: ({ row }) => money(row.original.selling_price_cents),
            },
            {
                id: 'actions',
                header: 'Actions',
                enableSorting: false,
                cell: () => (
                    <div className="flex items-center gap-2 text-primary">
                        <span className="font-black">Edit</span>
                        <MoreVertical size={16} />
                    </div>
                ),
            },
        ],
        [],
    );

    function openCreate() {
        setEditing(null);
        form.setData(blankMaterial(defaultMarkupBasisPoints));
        form.clearErrors();
        setDrawerOpen(true);
    }

    function openEdit(material: Material) {
        setEditing(material);
        form.setData({
            name: material.name,
            type: material.type,
            sku: material.sku ?? '',
            category: material.category,
            unit: material.unit,
            unit_cost_cents: centsToDollars(material.unit_cost_cents),
            markup_basis_points: basisPointsToPercent(
                material.markup_basis_points,
            ),
            vendor: material.vendor ?? '',
            description: material.description ?? '',
            notes: material.notes ?? '',
            is_active: material.is_active,
        });
        form.clearErrors();
        setDrawerOpen(true);
    }

    function saveMaterial(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            unit_cost_cents: dollarsToCents(data.unit_cost_cents),
            markup_basis_points: percentToBasisPoints(
                data.markup_basis_points,
            ),
        }));

        const options = {
            preserveScroll: true,
            onSuccess: () => setDrawerOpen(false),
        };

        if (editing) {
            form.put(`/settings/price-sheet/materials/${editing.id}`, options);
            return;
        }

        form.post('/settings/price-sheet/materials', options);
    }

    return (
        <AppLayout
            title="Price Sheet"
            subtitle="Manage company-scoped sale items, costs, units, and cost-plus markup."
            action={<SettingsBackLink />}
        >
            <Head title="Price Sheet" />
            <div className="space-y-6">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex max-w-3xl gap-3">
                        <SearchInput
                            value={liveFilters.values.search}
                            onChange={(value) =>
                                liveFilters.setFilter('search', value, {
                                    debounce: true,
                                })
                            }
                            placeholder="Search price sheet..."
                        />
                        {hasFilters ? (
                            <SecondaryButton onClick={liveFilters.clearFilters}>
                                Clear
                            </SecondaryButton>
                        ) : null}
                    </div>
                    <PrimaryButton onClick={openCreate}>
                        <Plus size={18} /> New Item
                    </PrimaryButton>
                </div>

                <Panel>
                    <div className="flex items-center gap-4 p-6">
                        <span className="flex size-12 items-center justify-center rounded-lg bg-secondary text-primary">
                            <PackageOpen />
                        </span>
                        <div>
                            <h2 className="text-xl font-black">
                                Company Price Sheet
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                New items default to{' '}
                                {percent(defaultMarkupBasisPoints)} markup.
                            </p>
                        </div>
                    </div>
                    <DataTable
                        page={materials}
                        columns={columns}
                        filters={filters}
                        route="/settings/price-sheet"
                        onRowClick={openEdit}
                    />
                    <Pagination page={materials} />
                </Panel>
            </div>

            <Drawer
                open={drawerOpen}
                title={editing ? 'Edit Price Sheet Item' : 'New Price Sheet Item'}
                subtitle="Each item stores cost in cents and markup as basis points."
                onClose={() => setDrawerOpen(false)}
            >
                <form onSubmit={saveMaterial} className="space-y-4">
                    <Field label="Name" error={form.errors.name}>
                        <input
                            className="field"
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                        />
                    </Field>
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Type" error={form.errors.type}>
                            <select
                                className="field"
                                value={form.data.type}
                                onChange={(event) =>
                                    form.setData('type', event.target.value)
                                }
                            >
                                {types.map((type) => (
                                    <option key={type.value} value={type.value}>
                                        {type.label}
                                    </option>
                                ))}
                            </select>
                        </Field>
                        <Field label="SKU" error={form.errors.sku}>
                            <input
                                className="field"
                                value={form.data.sku}
                                onChange={(event) =>
                                    form.setData('sku', event.target.value)
                                }
                            />
                        </Field>
                    </div>
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Category" error={form.errors.category}>
                            <input
                                className="field"
                                value={form.data.category}
                                onChange={(event) =>
                                    form.setData('category', event.target.value)
                                }
                            />
                        </Field>
                        <Field label="Unit" error={form.errors.unit}>
                            <input
                                className="field"
                                value={form.data.unit}
                                onChange={(event) =>
                                    form.setData('unit', event.target.value)
                                }
                            />
                        </Field>
                    </div>
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field
                            label="Unit Cost"
                            error={form.errors.unit_cost_cents}
                            hint="Enter dollars. Bidscape stores cents."
                        >
                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                className="field"
                                value={form.data.unit_cost_cents}
                                onChange={(event) =>
                                    form.setData(
                                        'unit_cost_cents',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                        <Field
                            label="Markup"
                            error={form.errors.markup_basis_points}
                            hint="Enter percent. Bidscape stores basis points."
                        >
                            <div className="flex items-center gap-3">
                                <input
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    className="field"
                                    value={form.data.markup_basis_points}
                                    onChange={(event) =>
                                        form.setData(
                                            'markup_basis_points',
                                            event.target.value,
                                        )
                                    }
                                />
                                <span className="font-black text-muted-foreground">
                                    %
                                </span>
                            </div>
                        </Field>
                    </div>
                    <Field label="Vendor" error={form.errors.vendor}>
                        <input
                            className="field"
                            value={form.data.vendor}
                            onChange={(event) =>
                                form.setData('vendor', event.target.value)
                            }
                        />
                    </Field>
                    <Field label="Description" error={form.errors.description}>
                        <textarea
                            className="field min-h-24"
                            value={form.data.description}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                        />
                    </Field>
                    <label className="flex items-center gap-3 text-sm font-bold">
                        <input
                            type="checkbox"
                            checked={form.data.is_active}
                            onChange={(event) =>
                                form.setData(
                                    'is_active',
                                    event.target.checked,
                                )
                            }
                        />
                        Active
                    </label>
                    <div className="flex justify-end gap-3 pt-2">
                        <SecondaryButton
                            type="button"
                            onClick={() => setDrawerOpen(false)}
                        >
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" disabled={form.processing}>
                            <Save size={17} /> Save Item
                        </PrimaryButton>
                    </div>
                </form>
            </Drawer>
        </AppLayout>
    );
}

function blankMaterial(defaultMarkupBasisPoints: number): MaterialForm {
    return {
        name: '',
        type: 'physical_material',
        sku: '',
        category: '',
        unit: '',
        unit_cost_cents: '0.00',
        markup_basis_points: basisPointsToPercent(defaultMarkupBasisPoints),
        vendor: '',
        description: '',
        notes: '',
        is_active: true,
    };
}
