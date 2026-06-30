import { Head, router, useForm } from '@inertiajs/react';
import {
    CheckCircle2,
    Clock,
    FileText,
    Plus,
    Save,
    Target,
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
    StatusPill,
} from '@/Components/Bidscape/UI';
import { useLiveTableFilters } from '@/hooks/useLiveTableFilters';
import { money } from '@/lib/format';

interface EstimateRow {
    id: number;
    number: string;
    customer: string;
    project: string;
    status: string;
    status_value: string;
    total_cents: number;
    created: string;
    updated: string;
    next_action: string;
}

interface Props {
    estimates: Paginated<EstimateRow>;
    filters: {
        search?: string;
        status?: string;
        sort?: string;
        direction?: string;
    };
    statuses: Array<{ value: string; label: string }>;
    customers: Array<{ id: number; name: string }>;
    leads: Array<{ id: number; name: string }>;
    kpis: Array<{ label: string; value: number; money?: boolean; percent?: boolean }>;
}

export default function EstimatesIndex({
    estimates,
    filters,
    statuses,
    customers,
    leads,
    kpis,
}: Props) {
    const [drawerOpen, setDrawerOpen] = useState(false);
    const liveFilters = useLiveTableFilters('/estimates', filters);
    const hasFilters = Object.values(liveFilters.values).some(Boolean);
    const form = useForm({
        project_name: '',
        lead_id: '',
        customer_id: '',
        scope_summary: '',
    });
    const columns = useMemo<ColumnDef<EstimateRow>[]>(
        () => [
            {
                accessorKey: 'number',
                header: 'Estimate #',
                cell: ({ row }) => (
                    <span className="font-bold">{row.original.number}</span>
                ),
            },
            {
                accessorKey: 'customer',
                header: 'Customer',
                enableSorting: false,
            },
            {
                accessorKey: 'project',
                header: 'Project',
                cell: ({ row }) => (
                    <span className="font-bold text-foreground">
                        {row.original.project}
                    </span>
                ),
            },
            {
                accessorKey: 'status',
                header: 'Status',
                cell: ({ row }) => <StatusPill status={row.original.status} />,
            },
            {
                accessorKey: 'total_cents',
                header: 'Total',
                cell: ({ row }) => (
                    <span className="font-bold">
                        {money(row.original.total_cents)}
                    </span>
                ),
            },
            {
                accessorKey: 'created',
                header: 'Created',
            },
            {
                accessorKey: 'updated',
                header: 'Last Updated',
            },
            {
                accessorKey: 'next_action',
                header: 'Next Action',
                cell: ({ row }) => (
                    <span className="text-primary">
                        {row.original.next_action}
                    </span>
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

    function saveEstimate(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post('/estimates', {
            preserveScroll: true,
            onSuccess: () => setDrawerOpen(false),
        });
    }

    return (
        <AppLayout
            title="Estimates"
            subtitle="Create, price, review, and email estimate PDFs."
            action={
                <PrimaryButton onClick={() => setDrawerOpen(true)}>
                    <Plus size={18} /> New Estimate
                </PrimaryButton>
            }
        >
            <Head title="Estimates" />
            <div className="space-y-6">
                <MetricGrid>
                    {kpis.map((kpi, index) => (
                        <KpiCard
                            key={kpi.label}
                            label={kpi.label}
                            value={kpi.value}
                            icon={
                                [
                                    FileText,
                                    CheckCircle2,
                                    Clock,
                                    FileText,
                                    Target,
                                ][index]
                            }
                            moneyValue={kpi.money}
                            percentValue={kpi.percent}
                        />
                    ))}
                </MetricGrid>
                <Panel>
                    <div className="flex flex-col gap-4 border-b border-border p-5 lg:flex-row">
                        <SearchInput
                            value={liveFilters.values.search}
                            onChange={(value) =>
                                liveFilters.setFilter('search', value, {
                                    debounce: true,
                                })
                            }
                            placeholder="Search estimates..."
                        />
                        <select
                            name="status"
                            value={liveFilters.values.status ?? ''}
                            onChange={(event) =>
                                liveFilters.setFilter(
                                    'status',
                                    event.target.value,
                                )
                            }
                            className="h-11 rounded-md border border-border bg-card px-4 text-sm font-semibold"
                        >
                            <option value="">All Statuses</option>
                            {statuses.map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </select>
                        {hasFilters ? (
                            <SecondaryButton onClick={liveFilters.clearFilters}>
                                Clear
                            </SecondaryButton>
                        ) : null}
                    </div>
                    <DataTable
                        page={estimates}
                        columns={columns}
                        filters={filters}
                        route="/estimates"
                        onRowClick={(estimate) =>
                            router.visit(`/estimates/${estimate.id}/builder`)
                        }
                    />
                    <Pagination page={estimates} />
                </Panel>
            </div>

            <Drawer
                open={drawerOpen}
                title="New Estimate"
                subtitle="Start from a lead before signature, or attach an existing customer."
                onClose={() => setDrawerOpen(false)}
            >
                <form onSubmit={saveEstimate} className="space-y-4">
                    <Field label="Project Name" error={form.errors.project_name}>
                        <input
                            className="field"
                            value={form.data.project_name}
                            onChange={(event) =>
                                form.setData('project_name', event.target.value)
                            }
                        />
                    </Field>
                    <Field label="Lead">
                        <select
                            className="field"
                            value={form.data.lead_id}
                            onChange={(event) =>
                                form.setData('lead_id', event.target.value)
                            }
                        >
                            <option value="">No lead</option>
                            {leads.map((lead) => (
                                <option key={lead.id} value={lead.id}>
                                    {lead.name}
                                </option>
                            ))}
                        </select>
                    </Field>
                    <Field label="Customer">
                        <select
                            className="field"
                            value={form.data.customer_id}
                            onChange={(event) =>
                                form.setData('customer_id', event.target.value)
                            }
                        >
                            <option value="">Create after signature</option>
                            {customers.map((customer) => (
                                <option key={customer.id} value={customer.id}>
                                    {customer.name}
                                </option>
                            ))}
                        </select>
                    </Field>
                    <Field label="Scope Summary">
                        <textarea
                            className="field min-h-28"
                            value={form.data.scope_summary}
                            onChange={(event) =>
                                form.setData(
                                    'scope_summary',
                                    event.target.value,
                                )
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
                            <Save size={17} /> Create Estimate
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
