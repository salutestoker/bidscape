import { Head, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, FileText, XCircle } from 'lucide-react';
import { FormEvent, useState } from 'react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { Button } from '@/Components/ui/button';
import { money, percent } from '@/lib/format';
import { PageProps } from '@/types';

interface EstimateItem {
    name: string;
    subtitle?: string | null;
    description?: string | null;
    quantity: string;
    unit: string;
    unit_price_cents: number;
    total_cents: number;
    notes?: string | null;
}

interface Props {
    token: string;
    canRespond: boolean;
    estimate: {
        number: string;
        project: string;
        status: string;
        status_label: string;
        sent_at?: string | null;
        expires_at?: string | null;
        scope_summary?: string | null;
        terms?: string | null;
        summary: Record<string, number>;
        company: {
            name: string;
            email?: string | null;
            phone?: string | null;
            website?: string | null;
        };
        lead?: {
            name: string;
            email?: string | null;
            phone?: string | null;
            site_address?: string | null;
        } | null;
        customer?: {
            name: string;
            email?: string | null;
            phone?: string | null;
            site_address?: string | null;
        } | null;
        items: EstimateItem[];
        sections: string[];
    };
}

export default function EstimateReview({ token, estimate, canRespond }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [declining, setDeclining] = useState(false);
    const sections = estimate.sections ?? [
        'header',
        'prepared_for',
        'project_site',
        'scope_summary',
        'scope_items',
        'price_summary',
        'terms',
    ];
    const hasSection = (key: string) => sections.includes(key);
    const contentSections = sections.filter((section) =>
        ['scope_summary', 'scope_items', 'terms'].includes(section),
    );
    const approve = useForm({
        signature_name: estimate.customer?.name ?? estimate.lead?.name ?? '',
        signature_email:
            estimate.customer?.email ?? estimate.lead?.email ?? '',
    });
    const decline = useForm({
        decline_reason_type: 'revise_bid',
        reason: '',
    });

    function approveEstimate(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        approve.post(`/estimate-review/${token}/approve`, {
            preserveScroll: true,
        });
    }

    function declineEstimate(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        decline.post(`/estimate-review/${token}/decline`, {
            preserveScroll: true,
        });
    }

    return (
        <main className="min-h-screen bg-background px-5 py-8 text-foreground md:px-10">
            <Head title={`${estimate.number} Review`} />
            <div className="mx-auto flex max-w-6xl flex-col gap-6">
                {hasSection('header') || hasSection('price_summary') ? (
                    <header className="flex flex-col gap-5 rounded-lg border border-border bg-card p-6 shadow-[0_14px_36px_rgba(15,23,42,0.05)] md:flex-row md:items-start md:justify-between">
                        {hasSection('header') ? (
                            <div>
                                <div className="mb-5 w-fit rounded-lg bg-white p-1 shadow-sm">
                                    <ApplicationLogo className="w-[180px]" />
                                </div>
                                <p className="text-sm font-black uppercase tracking-normal text-primary">
                                    {estimate.company.name}
                                </p>
                                <h1 className="mt-2 text-4xl font-black">
                                    {estimate.project}
                                </h1>
                                <p className="mt-2 text-muted-foreground">
                                    Estimate {estimate.number} ·{' '}
                                    {estimate.status_label}
                                </p>
                            </div>
                        ) : (
                            <div />
                        )}
                        {hasSection('price_summary') ? (
                            <div className="rounded-md bg-secondary px-5 py-4 text-right">
                                <p className="text-sm font-bold text-muted-foreground">
                                    Selling Price
                                </p>
                                <p className="text-3xl font-black text-primary">
                                    {money(
                                        estimate.summary.selling_price_cents,
                                    )}
                                </p>
                                <p className="text-sm font-bold text-primary">
                                    {percent(
                                        estimate.summary
                                            .gross_margin_basis_points,
                                    )}{' '}
                                    margin
                                </p>
                            </div>
                        ) : null}
                    </header>
                ) : null}

                {flash.success ? (
                    <div className="rounded-md border border-border bg-secondary px-4 py-3 text-sm font-semibold text-primary">
                        {flash.success}
                    </div>
                ) : null}

                <section className="grid gap-6 lg:grid-cols-[1fr_340px]">
                    {contentSections.length ? (
                        <div className="space-y-6">
                            {contentSections.map((section) => {
                                if (section === 'scope_summary') {
                                    return estimate.scope_summary ? (
                                        <div
                                            key={section}
                                            className="rounded-lg border border-border bg-card p-6 shadow-[0_14px_36px_rgba(15,23,42,0.05)]"
                                        >
                                            <h2 className="flex items-center gap-3 text-2xl font-black">
                                                <FileText className="text-primary" />{' '}
                                                Scope Summary
                                            </h2>
                                            <p className="mt-4 leading-7 text-muted-foreground">
                                                {estimate.scope_summary}
                                            </p>
                                        </div>
                                    ) : null;
                                }

                                if (section === 'terms') {
                                    return estimate.terms ? (
                                        <div
                                            key={section}
                                            className="rounded-lg border border-border bg-card p-6 shadow-[0_14px_36px_rgba(15,23,42,0.05)]"
                                        >
                                            <h2 className="text-2xl font-black">
                                                Terms
                                            </h2>
                                            <p className="mt-4 leading-7 text-muted-foreground">
                                                {estimate.terms}
                                            </p>
                                        </div>
                                    ) : null;
                                }

                                return (
                                    <div
                                        key={section}
                                        className="rounded-lg border border-border bg-card p-6 shadow-[0_14px_36px_rgba(15,23,42,0.05)]"
                                    >
                                        <h2 className="flex items-center gap-3 text-2xl font-black">
                                            <FileText className="text-primary" />{' '}
                                            Scope Items
                                        </h2>
                                        <div className="mt-6 overflow-x-auto">
                                            <table className="min-w-full text-left text-sm">
                                                <thead className="border-y border-border bg-muted/50 text-xs font-bold uppercase text-muted-foreground">
                                                    <tr>
                                                        <th className="px-4 py-3">
                                                            Item
                                                        </th>
                                                        <th>Qty</th>
                                                        <th>Unit</th>
                                                        <th>Unit Price</th>
                                                        <th>Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-border">
                                                    {estimate.items.map(
                                                        (item) => (
                                                            <tr key={item.name}>
                                                                <td className="px-4 py-4">
                                                                    <strong>
                                                                        {
                                                                            item.name
                                                                        }
                                                                    </strong>
                                                                    <p className="text-muted-foreground">
                                                                        {
                                                                            item.subtitle
                                                                        }
                                                                    </p>
                                                                </td>
                                                                <td>
                                                                    {
                                                                        item.quantity
                                                                    }
                                                                </td>
                                                                <td>
                                                                    {item.unit}
                                                                </td>
                                                                <td>
                                                                    {money(
                                                                        item.unit_price_cents,
                                                                    )}
                                                                </td>
                                                                <td className="font-black">
                                                                    {money(
                                                                        item.total_cents,
                                                                    )}
                                                                </td>
                                                            </tr>
                                                        ),
                                                    )}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <div />
                    )}

                    <aside className="flex flex-col gap-6">
                        {hasSection('prepared_for') ||
                        hasSection('project_site') ? (
                            <div className="rounded-lg border border-border bg-card p-6 shadow-[0_14px_36px_rgba(15,23,42,0.05)]">
                                <h2 className="text-xl font-black">Review</h2>
                                <div className="mt-5 flex flex-col gap-3 text-sm">
                                    {hasSection('prepared_for') ? (
                                        <>
                                            <Row
                                                label="Prepared For"
                                                value={
                                                    estimate.customer?.name ??
                                                    estimate.lead?.name ??
                                                    'Customer'
                                                }
                                            />
                                            <Row
                                                label="Phone"
                                                value={
                                                    estimate.customer?.phone ??
                                                    estimate.lead?.phone ??
                                                    ''
                                                }
                                            />
                                        </>
                                    ) : null}
                                    {hasSection('project_site') ? (
                                        <Row
                                            label="Site"
                                            value={
                                                estimate.customer
                                                    ?.site_address ??
                                                estimate.lead?.site_address ??
                                                ''
                                            }
                                        />
                                    ) : null}
                                    <Row
                                        label="Expires"
                                        value={estimate.expires_at ?? 'Not set'}
                                    />
                                </div>
                            </div>
                        ) : null}

                        {canRespond ? (
                            <div className="rounded-lg border border-border bg-card p-6 shadow-[0_14px_36px_rgba(15,23,42,0.05)]">
                                {!declining ? (
                                    <form
                                        onSubmit={approveEstimate}
                                        className="flex flex-col gap-3"
                                    >
                                        <h2 className="text-xl font-black">
                                            Approve And Sign
                                        </h2>
                                        <input
                                            className="field"
                                            value={approve.data.signature_name}
                                            onChange={(event) =>
                                                approve.setData(
                                                    'signature_name',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Typed signature"
                                        />
                                        <input
                                            className="field"
                                            value={approve.data.signature_email}
                                            onChange={(event) =>
                                                approve.setData(
                                                    'signature_email',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Email"
                                        />
                                        <Button
                                            type="submit"
                                            className="h-12 w-full font-black"
                                        >
                                            <CheckCircle2 data-icon="inline-start" /> Approve
                                            And Sign
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setDeclining(true)}
                                            className="h-12 w-full font-black text-destructive"
                                        >
                                            <XCircle data-icon="inline-start" /> Decline
                                        </Button>
                                    </form>
                                ) : (
                                    <form
                                        onSubmit={declineEstimate}
                                        className="flex flex-col gap-3"
                                    >
                                        <h2 className="text-xl font-black">
                                            Decline Estimate
                                        </h2>
                                        <div className="grid gap-2">
                                            {[
                                                {
                                                    value: 'revise_bid',
                                                    label: 'Decline - Revise Bid',
                                                    description:
                                                        'Send this back for estimate revision.',
                                                },
                                                {
                                                    value: 'no_follow_up',
                                                    label: 'Decline - No Follow Up',
                                                    description:
                                                        'Close this lead with no further follow-up.',
                                                },
                                            ].map((option) => (
                                                <button
                                                    key={option.value}
                                                    type="button"
                                                    onClick={() =>
                                                        decline.setData(
                                                            'decline_reason_type',
                                                            option.value,
                                                        )
                                                    }
                                                    className={`rounded-md border px-4 py-3 text-left transition ${
                                                        decline.data
                                                            .decline_reason_type ===
                                                        option.value
                                                            ? 'border-primary bg-secondary text-primary'
                                                            : 'border-border bg-background text-foreground hover:bg-muted/50'
                                                    }`}
                                                >
                                                    <span className="block font-black">
                                                        {option.label}
                                                    </span>
                                                    <span className="mt-1 block text-sm text-muted-foreground">
                                                        {option.description}
                                                    </span>
                                                </button>
                                            ))}
                                            {decline.errors
                                                .decline_reason_type ? (
                                                <p className="text-xs font-semibold text-destructive">
                                                    {
                                                        decline.errors
                                                            .decline_reason_type
                                                    }
                                                </p>
                                            ) : null}
                                        </div>
                                        <textarea
                                            className="field min-h-28"
                                            value={decline.data.reason}
                                            onChange={(event) =>
                                                decline.setData(
                                                    'reason',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Optional note"
                                        />
                                        {decline.errors.reason ? (
                                            <p className="text-xs font-semibold text-destructive">
                                                {decline.errors.reason}
                                            </p>
                                        ) : null}
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            className="h-12 w-full font-black"
                                        >
                                            Decline Estimate
                                        </Button>
                                    </form>
                                )}
                            </div>
                        ) : null}
                    </aside>
                </section>
            </div>
        </main>
    );
}

function Row({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex justify-between gap-4">
            <span className="text-muted-foreground">{label}</span>
            <strong className="text-right">{value}</strong>
        </div>
    );
}
