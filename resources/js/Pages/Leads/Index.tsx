import { Head, Link, useForm } from '@inertiajs/react';
import {
    BriefcaseBusiness,
    Calendar,
    ClipboardList,
    Phone,
    Plus,
    Save,
    UserRound,
    XCircle,
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

interface LeadRow {
    id: number;
    name: string;
    email?: string | null;
    phone: string;
    contact: string;
    lead_source_id?: number | null;
    source: string;
    status: string;
    status_value: string;
    status_color: string;
    site_address: string;
    city?: string | null;
    state?: string | null;
    postal_code?: string | null;
    contact_preference?: string | null;
    project_interest?: string | null;
    requested_project_specifications?: string | null;
    site_notes?: string | null;
    internal_notes?: string | null;
    gate_code?: string | null;
    site_visit_scheduled_at?: string | null;
    next_follow_up_at?: string | null;
    lost_reason?: string | null;
    created: string;
    next_action: string;
    next_action_at: string;
}

interface Props {
    leads: Paginated<LeadRow>;
    sources: Array<{ id: number; name: string }>;
    statuses: Array<{ value: string; label: string; color: string }>;
    filters: {
        search?: string;
        status?: string;
        source?: string;
        sort?: string;
        direction?: string;
    };
    kpis: Array<{ label: string; value: number; trend: string }>;
}

const blankLead = {
    name: '',
    email: '',
    phone: '',
    lead_source_id: '',
    site_address: '',
    city: 'Mesa',
    state: 'AZ',
    postal_code: '',
    contact_preference: 'Phone',
    project_interest: '',
    requested_project_specifications: '',
    site_notes: '',
    internal_notes: '',
    gate_code: '',
    site_visit_scheduled_at: '',
    next_follow_up_at: '',
};

export default function LeadsIndex({
    leads,
    sources,
    statuses,
    filters,
    kpis,
}: Props) {
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editing, setEditing] = useState<LeadRow | null>(null);
    const form = useForm({ ...blankLead });
    const liveFilters = useLiveTableFilters('/leads', filters);
    const hasFilters = Object.values(liveFilters.values).some(Boolean);

    const columns = useMemo<ColumnDef<LeadRow>[]>(
        () => [
            {
                accessorKey: 'name',
                header: 'Lead Name',
                cell: ({ row }) => (
                    <span className="font-bold">{row.original.name}</span>
                ),
            },
            {
                accessorKey: 'contact',
                header: 'Contact',
            },
            {
                accessorKey: 'source',
                header: 'Source',
            },
            {
                accessorKey: 'status',
                header: 'Status',
                cell: ({ row }) => (
                    <StatusPill
                        status={row.original.status}
                        tone={row.original.status_color}
                    />
                ),
            },
            {
                accessorKey: 'created',
                header: 'Created',
            },
            {
                accessorKey: 'next_action',
                header: 'Next Action',
                cell: ({ row }) => (
                    <>
                        <span className="font-semibold">
                            {row.original.next_action}
                        </span>
                        <br />
                        <span className="text-primary">
                            {row.original.next_action_at}
                        </span>
                    </>
                ),
            },
            {
                id: 'actions',
                header: 'Actions',
                enableSorting: false,
                cell: ({ row }) => (
                    <div
                        className="flex items-center justify-end gap-2"
                        onClick={(event) => event.stopPropagation()}
                    >
                        {![
                            'estimate_sent',
                            'approved',
                            'closed',
                        ].includes(row.original.status_value) ? (
                            <Link
                                method="post"
                                href={`/leads/${row.original.id}/convert`}
                                as="button"
                                className="rounded-md bg-secondary px-3 py-2 text-xs font-bold text-primary"
                            >
                                Convert
                            </Link>
                        ) : null}
                        <RowMenu />
                    </div>
                ),
            },
        ],
        [],
    );

    function openCreate() {
        setEditing(null);
        form.setData({ ...blankLead });
        form.clearErrors();
        setDrawerOpen(true);
    }

    function openEdit(lead: LeadRow) {
        setEditing(lead);
        form.setData({
            name: lead.name ?? '',
            email: lead.email ?? '',
            phone: lead.phone ?? '',
            lead_source_id: lead.lead_source_id ? String(lead.lead_source_id) : '',
            site_address: lead.site_address ?? '',
            city: lead.city ?? '',
            state: lead.state ?? '',
            postal_code: lead.postal_code ?? '',
            contact_preference: lead.contact_preference ?? 'Phone',
            project_interest: lead.project_interest ?? '',
            requested_project_specifications:
                lead.requested_project_specifications ?? '',
            site_notes: lead.site_notes ?? '',
            internal_notes: lead.internal_notes ?? '',
            gate_code: lead.gate_code ?? '',
            site_visit_scheduled_at: lead.site_visit_scheduled_at ?? '',
            next_follow_up_at: lead.next_follow_up_at ?? '',
        });
        form.clearErrors();
        setDrawerOpen(true);
    }

    function saveLead(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => setDrawerOpen(false),
        };

        if (editing) {
            form.put(`/leads/${editing.id}`, options);
            return;
        }

        form.post('/leads', options);
    }

    return (
        <AppLayout
            title="Leads"
            subtitle="Manage and track all incoming leads."
            action={
                <PrimaryButton onClick={openCreate}>
                    <Plus size={18} /> New Lead
                </PrimaryButton>
            }
        >
            <Head title="Leads" />
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
                                    UserRound,
                                    Phone,
                                    ClipboardList,
                                    Calendar,
                                    BriefcaseBusiness,
                                    XCircle,
                                ][index]
                            }
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
                            placeholder="Search leads..."
                        />
                        <select
                            name="source"
                            value={liveFilters.values.source ?? ''}
                            onChange={(event) =>
                                liveFilters.setFilter(
                                    'source',
                                    event.target.value,
                                )
                            }
                            className="h-11 rounded-md border border-border bg-card px-4 text-sm font-semibold"
                        >
                            <option value="">All Sources</option>
                            {sources.map((source) => (
                                <option key={source.id} value={source.id}>
                                    {source.name}
                                </option>
                            ))}
                        </select>
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
                        page={leads}
                        columns={columns}
                        filters={filters}
                        route="/leads"
                        onRowClick={openEdit}
                    />
                    <Pagination page={leads} />
                </Panel>
            </div>

            <Drawer
                open={drawerOpen}
                title={editing ? 'Edit Lead' : 'New Lead'}
                subtitle="Capture sales intake details before estimate work begins."
                onClose={() => setDrawerOpen(false)}
            >
                <form onSubmit={saveLead} className="space-y-4">
                    <Field label="Name" error={form.errors.name}>
                        <input
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            className="field"
                        />
                    </Field>
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Phone" error={form.errors.phone}>
                            <input
                                value={form.data.phone}
                                onChange={(event) =>
                                    form.setData('phone', event.target.value)
                                }
                                className="field"
                            />
                        </Field>
                        <Field label="Email" error={form.errors.email}>
                            <input
                                value={form.data.email}
                                onChange={(event) =>
                                    form.setData('email', event.target.value)
                                }
                                className="field"
                            />
                        </Field>
                    </div>
                    <Field label="Site Address" error={form.errors.site_address}>
                        <input
                            value={form.data.site_address}
                            onChange={(event) =>
                                form.setData('site_address', event.target.value)
                            }
                            className="field"
                        />
                    </Field>
                    <div className="grid gap-4 md:grid-cols-3">
                        <Field label="City">
                            <input
                                value={form.data.city}
                                onChange={(event) =>
                                    form.setData('city', event.target.value)
                                }
                                className="field"
                            />
                        </Field>
                        <Field label="State">
                            <input
                                value={form.data.state}
                                onChange={(event) =>
                                    form.setData('state', event.target.value)
                                }
                                className="field"
                            />
                        </Field>
                        <Field label="Postal Code">
                            <input
                                value={form.data.postal_code}
                                onChange={(event) =>
                                    form.setData(
                                        'postal_code',
                                        event.target.value,
                                    )
                                }
                                className="field"
                            />
                        </Field>
                    </div>
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field
                            label="Source"
                            error={form.errors.lead_source_id}
                        >
                            <select
                                value={form.data.lead_source_id}
                                onChange={(event) =>
                                    form.setData(
                                        'lead_source_id',
                                        event.target.value,
                                    )
                                }
                                className="field"
                            >
                                <option value="">Select source</option>
                                {sources.map((source) => (
                                    <option key={source.id} value={source.id}>
                                        {source.name}
                                    </option>
                                ))}
                            </select>
                        </Field>
                        <Field
                            label="Site Visit Date & Time"
                            error={form.errors.site_visit_scheduled_at}
                        >
                            <input
                                type="datetime-local"
                                value={form.data.site_visit_scheduled_at}
                                onChange={(event) =>
                                    form.setData(
                                        'site_visit_scheduled_at',
                                        event.target.value,
                                    )
                                }
                                className="field"
                            />
                        </Field>
                    </div>
                    <div className="rounded-md border border-border bg-muted/50 px-4 py-3 text-sm">
                        <span className="font-bold text-foreground">
                            Automatic Status:{' '}
                        </span>
                        <span className="font-black text-primary">
                            {previewLeadStatus(
                                form.data.site_visit_scheduled_at,
                                editing,
                            )}
                        </span>
                    </div>
                    <Field label="Requested Specs">
                        <textarea
                            value={form.data.requested_project_specifications}
                            onChange={(event) =>
                                form.setData(
                                    'requested_project_specifications',
                                    event.target.value,
                                )
                            }
                            className="field min-h-24"
                        />
                    </Field>
                    <Field label="Notes">
                        <textarea
                            value={form.data.internal_notes}
                            onChange={(event) =>
                                form.setData(
                                    'internal_notes',
                                    event.target.value,
                                )
                            }
                            className="field min-h-20"
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
                            <Save size={17} /> Save Lead
                        </PrimaryButton>
                    </div>
                </form>
            </Drawer>
        </AppLayout>
    );
}

function previewLeadStatus(
    siteVisitScheduledAt: string,
    editing?: LeadRow | null,
) {
    if (
        editing &&
        ['estimate_sent', 'approved', 'closed'].includes(editing.status_value)
    ) {
        return editing.status;
    }

    if (!siteVisitScheduledAt) {
        return 'Pending Contact';
    }

    return new Date(siteVisitScheduledAt) > new Date()
        ? 'Site Visit'
        : 'Pending Estimate';
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
