import { Head, useForm } from '@inertiajs/react';
import {
    BriefcaseBusiness,
    DollarSign,
    Plus,
    Save,
    UserRound,
    Users,
} from 'lucide-react';
import { ColumnDef } from '@tanstack/react-table';
import { FormEvent, ReactNode, useMemo, useState } from 'react';
import AppLayout from '@/Components/Bidscape/AppLayout';
import { DataTable } from '@/Components/Bidscape/DataTable';
import {
    Drawer,
    KpiCard,
    MetricGrid,
    Paginated,
    Pagination,
    Panel,
    PrimaryButton,
    RowMenu,
    SearchInput,
    SecondaryButton,
} from '@/Components/Bidscape/UI';
import { useLiveTableFilters } from '@/hooks/useLiveTableFilters';
import { money } from '@/lib/format';

interface CustomerRow {
    id: number;
    initials: string;
    name: string;
    email?: string | null;
    phone?: string | null;
    lead_source_id?: number | null;
    site_address?: string | null;
    city?: string | null;
    state?: string | null;
    postal_code?: string | null;
    notes?: string | null;
    total_jobs: number;
    lifetime_value_cents: number;
    last_activity: string;
}

interface Props {
    customers: Paginated<CustomerRow>;
    sources: Array<{ id: number; name: string }>;
    filters: { search?: string; sort?: string; direction?: string };
    kpis: Array<{ label: string; value: number; trend: string; money?: boolean }>;
}

const blankCustomer = {
    name: '',
    email: '',
    phone: '',
    lead_source_id: '',
    site_address: '',
    city: 'Mesa',
    state: 'AZ',
    postal_code: '',
    notes: '',
};

export default function CustomersIndex({
    customers,
    sources,
    filters,
    kpis,
}: Props) {
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editing, setEditing] = useState<CustomerRow | null>(null);
    const form = useForm({ ...blankCustomer });
    const liveFilters = useLiveTableFilters('/customers', filters);
    const hasFilters = Object.values(liveFilters.values).some(Boolean);
    const columns = useMemo<ColumnDef<CustomerRow>[]>(
        () => [
            {
                accessorKey: 'name',
                header: 'Customer',
                cell: ({ row }) => (
                    <>
                        <span className="mr-3 inline-flex size-8 items-center justify-center rounded-full bg-secondary text-xs font-bold text-primary">
                            {row.original.initials}
                        </span>
                        <span className="font-bold">{row.original.name}</span>
                    </>
                ),
            },
            {
                accessorKey: 'email',
                header: 'Email',
            },
            {
                accessorKey: 'phone',
                header: 'Phone',
            },
            {
                accessorKey: 'total_jobs',
                header: 'Total Jobs',
                enableSorting: false,
            },
            {
                accessorKey: 'lifetime_value_cents',
                header: 'Lifetime Value',
                enableSorting: false,
                cell: ({ row }) => money(row.original.lifetime_value_cents),
            },
            {
                accessorKey: 'last_activity',
                header: 'Last Activity',
            },
            {
                id: 'details',
                header: 'Details',
                enableSorting: false,
                cell: () => (
                    <span className="font-bold text-primary">View</span>
                ),
            },
            {
                id: 'actions',
                header: 'Actions',
                enableSorting: false,
                cell: () => (
                    <div
                        className="flex justify-end"
                        onClick={(event) => event.stopPropagation()}
                    >
                        <RowMenu />
                    </div>
                ),
            },
        ],
        [],
    );

    function openCreate() {
        setEditing(null);
        form.setData({ ...blankCustomer });
        form.clearErrors();
        setDrawerOpen(true);
    }

    function openEdit(customer: CustomerRow) {
        setEditing(customer);
        form.setData({
            name: customer.name,
            email: customer.email ?? '',
            phone: customer.phone ?? '',
            lead_source_id: customer.lead_source_id
                ? String(customer.lead_source_id)
                : '',
            site_address: customer.site_address ?? '',
            city: customer.city ?? '',
            state: customer.state ?? '',
            postal_code: customer.postal_code ?? '',
            notes: customer.notes ?? '',
        });
        form.clearErrors();
        setDrawerOpen(true);
    }

    function saveCustomer(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => setDrawerOpen(false),
        };

        if (editing) {
            form.put(`/customers/${editing.id}`, options);
            return;
        }

        form.post('/customers', options);
    }

    return (
        <AppLayout
            title="Customers"
            subtitle="Your customer database - approved and signed projects."
            action={
                <PrimaryButton onClick={openCreate}>
                    <Plus size={18} /> New Customer
                </PrimaryButton>
            }
        >
            <Head title="Customers" />
            <div className="space-y-6">
                <MetricGrid>
                    {kpis.map((kpi, index) => (
                        <KpiCard
                            key={kpi.label}
                            label={kpi.label}
                            value={kpi.value}
                            trend={kpi.trend}
                            icon={
                                [
                                    Users,
                                    UserRound,
                                    BriefcaseBusiness,
                                    DollarSign,
                                    DollarSign,
                                ][index]
                            }
                            moneyValue={kpi.money}
                        />
                    ))}
                </MetricGrid>
                <div className="flex max-w-3xl gap-3">
                    <SearchInput
                        value={liveFilters.values.search}
                        onChange={(value) =>
                            liveFilters.setFilter('search', value, {
                                debounce: true,
                            })
                        }
                        placeholder="Search customers..."
                    />
                    {hasFilters ? (
                        <SecondaryButton onClick={liveFilters.clearFilters}>
                            Clear
                        </SecondaryButton>
                    ) : null}
                </div>
                <Panel>
                    <DataTable
                        page={customers}
                        columns={columns}
                        filters={filters}
                        route="/customers"
                        onRowClick={openEdit}
                    />
                    <Pagination page={customers} />
                </Panel>
            </div>

            <Drawer
                open={drawerOpen}
                title={editing ? 'Edit Customer' : 'New Customer'}
                subtitle="Customer records are created by signed estimates or internal entry."
                onClose={() => setDrawerOpen(false)}
            >
                <form onSubmit={saveCustomer} className="space-y-4">
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
                        <Field label="Email" error={form.errors.email}>
                            <input
                                className="field"
                                value={form.data.email}
                                onChange={(event) =>
                                    form.setData('email', event.target.value)
                                }
                            />
                        </Field>
                        <Field label="Phone" error={form.errors.phone}>
                            <input
                                className="field"
                                value={form.data.phone}
                                onChange={(event) =>
                                    form.setData('phone', event.target.value)
                                }
                            />
                        </Field>
                    </div>
                    <Field label="Lead Source">
                        <select
                            className="field"
                            value={form.data.lead_source_id}
                            onChange={(event) =>
                                form.setData(
                                    'lead_source_id',
                                    event.target.value,
                                )
                            }
                        >
                            <option value="">No source</option>
                            {sources.map((source) => (
                                <option key={source.id} value={source.id}>
                                    {source.name}
                                </option>
                            ))}
                        </select>
                    </Field>
                    <Field label="Site Address">
                        <input
                            className="field"
                            value={form.data.site_address}
                            onChange={(event) =>
                                form.setData('site_address', event.target.value)
                            }
                        />
                    </Field>
                    <div className="grid gap-4 md:grid-cols-3">
                        <Field label="City">
                            <input
                                className="field"
                                value={form.data.city}
                                onChange={(event) =>
                                    form.setData('city', event.target.value)
                                }
                            />
                        </Field>
                        <Field label="State">
                            <input
                                className="field"
                                value={form.data.state}
                                onChange={(event) =>
                                    form.setData('state', event.target.value)
                                }
                            />
                        </Field>
                        <Field label="Postal Code">
                            <input
                                className="field"
                                value={form.data.postal_code}
                                onChange={(event) =>
                                    form.setData(
                                        'postal_code',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                    </div>
                    <Field label="Notes">
                        <textarea
                            className="field min-h-24"
                            value={form.data.notes}
                            onChange={(event) =>
                                form.setData('notes', event.target.value)
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
                            <Save size={17} /> Save Customer
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
