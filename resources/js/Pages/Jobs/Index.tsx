import { Head, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import {
    BriefcaseBusiness,
    CheckCircle2,
    DollarSign,
    FileCheck2,
} from 'lucide-react';
import { useMemo } from 'react';
import AppLayout from '@/Components/Bidscape/AppLayout';
import { DataTable } from '@/Components/Bidscape/DataTable';
import {
    KpiCard,
    MetricGrid,
    Paginated,
    Pagination,
    Panel,
    RowMenu,
    SearchInput,
    SecondaryButton,
    StatusPill,
} from '@/Components/Bidscape/UI';
import { useLiveTableFilters } from '@/hooks/useLiveTableFilters';
import { money } from '@/lib/format';

interface JobRow {
    id: number;
    number: string;
    customer: string;
    project: string;
    status: string;
    contract_value_cents: number;
    signed: string;
    deposit_status: string;
    updated: string;
    next_action: string;
}

interface Props {
    jobs: Paginated<JobRow>;
    filters: {
        search?: string;
        status?: string;
        sort?: string;
        direction?: string;
    };
    statuses: Array<{ value: string; label: string }>;
    kpis: Array<{ label: string; value: number; trend?: string; money?: boolean }>;
}

export default function JobsIndex({ jobs, filters, statuses, kpis }: Props) {
    const liveFilters = useLiveTableFilters('/jobs', filters);
    const hasFilters = Object.values(liveFilters.values).some(Boolean);
    const columns = useMemo<ColumnDef<JobRow>[]>(
        () => [
            {
                accessorKey: 'number',
                header: 'Job #',
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
                    <a
                        href={`/jobs/${row.original.id}/packet`}
                        className="font-bold"
                        onClick={(event) => event.stopPropagation()}
                    >
                        {row.original.project}
                    </a>
                ),
            },
            {
                accessorKey: 'status',
                header: 'Status',
                cell: ({ row }) => <StatusPill status={row.original.status} />,
            },
            {
                accessorKey: 'contract_value_cents',
                header: 'Contract Value',
                cell: ({ row }) => (
                    <span className="font-bold">
                        {money(row.original.contract_value_cents)}
                    </span>
                ),
            },
            {
                accessorKey: 'signed',
                header: 'Signed Date',
            },
            {
                accessorKey: 'deposit_status',
                header: 'Deposit',
                enableSorting: false,
                cell: ({ row }) => (
                    <StatusPill status={row.original.deposit_status} />
                ),
            },
            {
                accessorKey: 'updated',
                header: 'Last Updated',
            },
            {
                accessorKey: 'next_action',
                header: 'Next Action',
                enableSorting: false,
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

    return (
        <AppLayout
            title="Jobs"
            subtitle="Sold projects are created only when an estimate is signed."
        >
            <Head title="Jobs" />
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
                                    BriefcaseBusiness,
                                    FileCheck2,
                                    CheckCircle2,
                                    DollarSign,
                                    DollarSign,
                                ][index]
                            }
                            moneyValue={kpi.money}
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
                            placeholder="Search jobs..."
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
                            <SecondaryButton onClick={liveFilters.clearFilters}>Clear</SecondaryButton>
                        ) : null}
                    </div>
                    <DataTable
                        page={jobs}
                        columns={columns}
                        filters={filters}
                        route="/jobs"
                        onRowClick={(job) => router.visit(`/jobs/${job.id}/packet`)}
                        rowClassName={() => '[&_td:first-child]:border-l-4 [&_td:first-child]:border-primary'}
                    />
                    <Pagination page={jobs} />
                </Panel>
            </div>
        </AppLayout>
    );
}
