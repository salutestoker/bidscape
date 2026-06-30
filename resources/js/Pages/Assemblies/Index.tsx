import { Head, useForm } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { Calculator, MoreVertical, Plus, Save, Users } from 'lucide-react';
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

interface Assembly {
    id: number;
    name: string;
    category: string;
    unit: string;
    items: number;
    base_cost_cents: number;
    markup_basis_points: number;
    selling_price_cents: number;
    image_path: string;
    description?: string | null;
    labor_hours_per_unit: string;
    waste_factor_basis_points?: number;
    base_depth_inches?: string | null;
    default_minutes_per_unit?: string | null;
    production_rate_per_day?: string | null;
}

interface Props {
    assemblies: Paginated<Assembly>;
    selected: Assembly | null;
    filters: { search?: string; sort?: string; direction?: string };
}

const blankAssembly = {
    name: '',
    category: '',
    unit: '',
    description: '',
    markup_basis_points: 3000,
    waste_factor_basis_points: 0,
    base_depth_inches: '',
    labor_hours_per_unit: '0',
    default_minutes_per_unit: '0',
    production_rate_per_day: '',
};

export default function AssembliesIndex({
    assemblies,
    selected,
    filters,
}: Props) {
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editing, setEditing] = useState<Assembly | null>(null);
    const form = useForm({ ...blankAssembly });
    const detail = editing ?? selected;
    const liveFilters = useLiveTableFilters('/assemblies', filters);
    const hasFilters = Object.values(liveFilters.values).some(Boolean);
    const columns = useMemo<ColumnDef<Assembly>[]>(
        () => [
            {
                accessorKey: 'name',
                header: 'Assembly Name',
                cell: ({ row }) => (
                    <>
                        <span className="mr-4 inline-flex size-10 overflow-hidden rounded-md align-middle">
                            <img src={row.original.image_path} alt="" />
                        </span>
                        <span className="font-bold">{row.original.name}</span>
                    </>
                ),
            },
            { accessorKey: 'category', header: 'Category' },
            {
                accessorKey: 'items',
                header: 'Items',
                enableSorting: false,
            },
            { accessorKey: 'unit', header: 'Unit' },
            {
                accessorKey: 'base_cost_cents',
                header: 'Base Cost',
                cell: ({ row }) => money(row.original.base_cost_cents),
            },
            {
                accessorKey: 'markup_basis_points',
                header: 'Markup',
                cell: ({ row }) => percent(row.original.markup_basis_points),
            },
            {
                id: 'actions',
                header: 'Actions',
                enableSorting: false,
                cell: ({ row }) => (
                    <div
                        className="flex items-center gap-2"
                        onClick={(event) => event.stopPropagation()}
                    >
                        <a
                            href={`/assemblies/${row.original.id}/formula`}
                            className="rounded-md bg-secondary px-4 py-2 font-bold text-primary"
                        >
                            Formula
                        </a>
                        <MoreVertical size={16} />
                    </div>
                ),
            },
        ],
        [],
    );

    function openCreate() {
        setEditing(null);
        form.setData({ ...blankAssembly });
        form.clearErrors();
        setDrawerOpen(true);
    }

    function openEdit(assembly: Assembly) {
        setEditing(assembly);
        form.setData({
            name: assembly.name,
            category: assembly.category,
            unit: assembly.unit,
            description: assembly.description ?? '',
            markup_basis_points: assembly.markup_basis_points,
            waste_factor_basis_points: assembly.waste_factor_basis_points ?? 0,
            base_depth_inches: assembly.base_depth_inches ?? '',
            labor_hours_per_unit: assembly.labor_hours_per_unit ?? '0',
            default_minutes_per_unit:
                assembly.default_minutes_per_unit ?? '0',
            production_rate_per_day: assembly.production_rate_per_day ?? '',
        });
        form.clearErrors();
        setDrawerOpen(true);
    }

    function saveAssembly(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => setDrawerOpen(false),
        };

        if (editing) {
            form.put(`/assemblies/${editing.id}`, options);
            return;
        }

        form.post('/assemblies', options);
    }

    return (
        <AppLayout
            title="Assemblies"
            subtitle="Build reusable assemblies with labor, material, and pricing logic."
            action={
                <PrimaryButton onClick={openCreate}>
                    <Plus size={18} /> New Assembly
                </PrimaryButton>
            }
        >
            <Head title="Assemblies" />
            <div className="space-y-6">
                <div className="flex max-w-3xl gap-3">
                    <SearchInput
                        value={liveFilters.values.search}
                        onChange={(value) =>
                            liveFilters.setFilter('search', value, {
                                debounce: true,
                            })
                        }
                        placeholder="Search assemblies..."
                    />
                    {hasFilters ? (
                        <SecondaryButton onClick={liveFilters.clearFilters}>
                            Clear
                        </SecondaryButton>
                    ) : null}
                </div>
                <div className="grid gap-6 xl:grid-cols-[1fr_380px]">
                    <Panel>
                        <div className="p-6">
                            <h2 className="text-xl font-black">
                                Assembly Library
                            </h2>
                        </div>
                        <DataTable
                            page={assemblies}
                            columns={columns}
                            filters={filters}
                            route="/assemblies"
                            onRowClick={openEdit}
                        />
                        <Pagination page={assemblies} />
                    </Panel>
                    {detail ? (
                        <Panel className="p-6">
                            <h2 className="text-xl font-black">
                                Selected Assembly
                            </h2>
                            <img
                                src={detail.image_path}
                                alt=""
                                className="mt-5 h-44 w-full rounded-lg object-cover"
                            />
                            <h3 className="mt-5 text-2xl font-black">
                                {detail.name}
                            </h3>
                            <p className="text-muted-foreground">
                                {detail.category} - per {detail.unit}
                            </p>
                            <div className="mt-6 divide-y divide-border">
                                <Detail
                                    icon={<Calculator />}
                                    label="Materials"
                                    value={String(detail.items)}
                                />
                                <Detail
                                    icon={<Users />}
                                    label="Labor Hours / Unit"
                                    value={detail.labor_hours_per_unit}
                                />
                                <Detail
                                    icon={<Calculator />}
                                    label="Base Cost"
                                    value={money(detail.base_cost_cents)}
                                />
                                <Detail
                                    icon={<Calculator />}
                                    label="Markup"
                                    value={percent(detail.markup_basis_points)}
                                />
                            </div>
                            <div className="mt-6 rounded-md bg-muted p-5">
                                <p className="text-sm text-muted-foreground">
                                    Selling Price
                                </p>
                                <p className="text-3xl font-black text-primary">
                                    {money(detail.selling_price_cents)}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    per {detail.unit}
                                </p>
                            </div>
                            <PrimaryButton
                                onClick={() => openEdit(detail)}
                                className="mt-6 w-full"
                            >
                                View / Edit Details
                            </PrimaryButton>
                        </Panel>
                    ) : null}
                </div>
            </div>

            <Drawer
                open={drawerOpen}
                title={editing ? 'Edit Assembly' : 'New Assembly'}
                subtitle="Assembly formulas stay generic so future contractor industries can reuse the same pricing model."
                onClose={() => setDrawerOpen(false)}
            >
                <form onSubmit={saveAssembly} className="space-y-4">
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
                    <Field label="Description">
                        <textarea
                            className="field min-h-24"
                            value={form.data.description}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                        />
                    </Field>
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Markup (basis points)">
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
                        <Field label="Waste Factor (basis points)">
                            <input
                                type="number"
                                className="field"
                                value={form.data.waste_factor_basis_points}
                                onChange={(event) =>
                                    form.setData(
                                        'waste_factor_basis_points',
                                        Number(event.target.value),
                                    )
                                }
                            />
                        </Field>
                    </div>
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Labor Hours / Unit">
                            <input
                                className="field"
                                value={form.data.labor_hours_per_unit}
                                onChange={(event) =>
                                    form.setData(
                                        'labor_hours_per_unit',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                        <Field label="Production Rate / Day">
                            <input
                                className="field"
                                value={form.data.production_rate_per_day}
                                onChange={(event) =>
                                    form.setData(
                                        'production_rate_per_day',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <SecondaryButton
                            type="button"
                            onClick={() => setDrawerOpen(false)}
                        >
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit">
                            <Save size={17} /> Save Assembly
                        </PrimaryButton>
                    </div>
                </form>
            </Drawer>
        </AppLayout>
    );
}

function Detail({
    icon,
    label,
    value,
}: {
    icon: ReactNode;
    label: string;
    value: string;
}) {
    return (
        <div className="flex items-center justify-between py-4">
            <span className="flex items-center gap-3 text-muted-foreground">
                {icon}
                {label}
            </span>
            <strong>{value}</strong>
        </div>
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
