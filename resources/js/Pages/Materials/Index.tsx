import { Head, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { Box, MoreVertical, Plus, Save } from 'lucide-react';
import { FormEvent, ReactNode, useMemo, useState } from 'react';
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
    hourly_rate_cents?: number | null;
    minimum_charge_cents?: number | null;
    pricing_method?: string | null;
    vendor?: string | null;
    description?: string | null;
    notes?: string | null;
    is_active: boolean;
    photo_path?: string | null;
}

interface Props {
    materials: Paginated<Material>;
    selected: Material | null;
    types: Array<{ value: string; label: string }>;
    filters: { search?: string; sort?: string; direction?: string };
}

const blankMaterial = {
    name: '',
    type: 'physical_material',
    sku: '',
    category: '',
    unit: '',
    unit_cost_cents: 0,
    markup_basis_points: 3000,
    hourly_rate_cents: '',
    minimum_charge_cents: '',
    pricing_method: 'unit',
    vendor: '',
    description: '',
    notes: '',
    is_active: true,
};

export default function MaterialsIndex({
    materials,
    selected,
    types,
    filters,
}: Props) {
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editing, setEditing] = useState<Material | null>(null);
    const form = useForm({ ...blankMaterial });
    const detail = editing ?? selected ?? materials.data[0];
    const liveFilters = useLiveTableFilters('/materials', filters);
    const hasFilters = Object.values(liveFilters.values).some(Boolean);
    const columns = useMemo<ColumnDef<Material>[]>(
        () => [
            {
                accessorKey: 'name',
                header: 'Material',
                cell: ({ row }) => (
                    <>
                        <img
                            src={
                                row.original.photo_path ??
                                '/images/demo/gravel.svg'
                            }
                            alt=""
                            className="mr-4 inline size-10 rounded-md object-cover"
                        />
                        <strong>{row.original.name}</strong>
                    </>
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
                    <div
                        className="flex items-center gap-2"
                        onClick={(event) => event.stopPropagation()}
                    >
                        <button
                            type="button"
                            className="rounded-md bg-secondary px-4 py-2 font-bold text-primary"
                        >
                            Edit
                        </button>
                        <MoreVertical size={16} />
                    </div>
                ),
            },
        ],
        [],
    );

    function openCreate() {
        setEditing(null);
        form.setData({ ...blankMaterial });
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
            unit_cost_cents: material.unit_cost_cents,
            markup_basis_points: material.markup_basis_points,
            hourly_rate_cents: material.hourly_rate_cents
                ? String(material.hourly_rate_cents)
                : '',
            minimum_charge_cents: material.minimum_charge_cents
                ? String(material.minimum_charge_cents)
                : '',
            pricing_method: material.pricing_method ?? 'unit',
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
        const options = {
            preserveScroll: true,
            onSuccess: () => setDrawerOpen(false),
        };

        if (editing) {
            form.put(`/materials/${editing.id}`, options);
            return;
        }

        form.post('/materials', options);
    }

    return (
        <AppLayout
            title="Materials"
            subtitle="Manage your component catalog, supplier pricing, and markups."
            action={
                <PrimaryButton onClick={openCreate}>
                    <Plus size={18} /> New Material
                </PrimaryButton>
            }
        >
            <Head title="Materials" />
            <div className="space-y-6">
                <div className="flex max-w-3xl gap-3">
                    <SearchInput
                        value={liveFilters.values.search}
                        onChange={(value) =>
                            liveFilters.setFilter('search', value, {
                                debounce: true,
                            })
                        }
                        placeholder="Search materials..."
                    />
                    {hasFilters ? (
                        <SecondaryButton onClick={liveFilters.clearFilters}>
                            Clear
                        </SecondaryButton>
                    ) : null}
                </div>
                <div className="grid gap-6 xl:grid-cols-[1fr_360px]">
                    <Panel>
                        <div className="flex items-center gap-4 p-6">
                            <span className="flex size-12 items-center justify-center rounded-lg bg-secondary text-primary">
                                <Box />
                            </span>
                            <h2 className="text-xl font-black">
                                Component Catalog
                            </h2>
                        </div>
                        <DataTable
                            page={materials}
                            columns={columns}
                            filters={filters}
                            route="/materials"
                            onRowClick={openEdit}
                        />
                        <Pagination page={materials} />
                    </Panel>
                    {detail ? (
                        <Panel className="p-6">
                            <h2 className="text-xl font-black">
                                Material Details
                            </h2>
                            <img
                                src={detail.photo_path ?? '/images/demo/gravel.svg'}
                                alt=""
                                className="mt-5 h-44 w-full rounded-lg object-cover"
                            />
                            <h3 className="mt-5 text-2xl font-black">
                                {detail.name}
                            </h3>
                            <p className="text-muted-foreground">
                                SKU: {detail.sku ?? 'Not assigned'}
                            </p>
                            <div className="mt-6 space-y-4">
                                {[
                                    ['Type', detail.type_label],
                                    ['Category', detail.category],
                                    ['Unit', detail.unit],
                                    ['Unit Cost', money(detail.unit_cost_cents)],
                                    [
                                        'Markup',
                                        percent(detail.markup_basis_points),
                                    ],
                                    [
                                        'Selling Price',
                                        money(detail.selling_price_cents),
                                    ],
                                    ['Vendor', detail.vendor ?? 'Internal'],
                                ].map(([label, value]) => (
                                    <div
                                        key={label}
                                        className="flex justify-between"
                                    >
                                        <span className="text-muted-foreground">
                                            {label}
                                        </span>
                                        <strong>{value}</strong>
                                    </div>
                                ))}
                            </div>
                            <PrimaryButton
                                className="mt-8 w-full"
                                onClick={() => openEdit(detail)}
                            >
                                Edit Material
                            </PrimaryButton>
                        </Panel>
                    ) : null}
                </div>
            </div>

            <Drawer
                open={drawerOpen}
                title={editing ? 'Edit Material' : 'New Material'}
                subtitle="Catalog items can be physical materials, labor, equipment, delivery, allowances, services, or other charges."
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
                        <Field label="SKU">
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
                            label="Unit Cost (cents)"
                            error={form.errors.unit_cost_cents}
                        >
                            <input
                                type="number"
                                className="field"
                                value={form.data.unit_cost_cents}
                                onChange={(event) =>
                                    form.setData(
                                        'unit_cost_cents',
                                        Number(event.target.value),
                                    )
                                }
                            />
                        </Field>
                        <Field
                            label="Markup (basis points)"
                            error={form.errors.markup_basis_points}
                        >
                            <input
                                type="number"
                                className="field"
                                value={form.data.markup_basis_points}
                                onChange={(event) =>
                                    form.setData(
                                        'markup_basis_points',
                                        Number(event.target.value),
                                    )
                                }
                            />
                        </Field>
                    </div>
                    <Field label="Vendor">
                        <input
                            className="field"
                            value={form.data.vendor}
                            onChange={(event) =>
                                form.setData('vendor', event.target.value)
                            }
                        />
                    </Field>
                    <Field label="Description">
                        <textarea
                            className="field min-h-24"
                            value={form.data.description}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                        />
                    </Field>
                    <div className="flex justify-end gap-3 pt-2">
                        <SecondaryButton
                            type="button"
                            onClick={() => setDrawerOpen(false)}
                        >
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit">
                            <Save size={17} /> Save Material
                        </PrimaryButton>
                    </div>
                </form>
            </Drawer>
        </AppLayout>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <label className="block text-sm font-bold text-foreground">
            <span>{label}</span>
            <div className="mt-2">{children}</div>
            {error ? (
                <p className="mt-1 text-xs font-semibold text-destructive">
                    {error}
                </p>
            ) : null}
        </label>
    );
}
