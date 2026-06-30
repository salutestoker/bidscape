import { Head } from '@inertiajs/react';
import { ArrowRight, CheckCircle2, Filter, Handshake, PieChart, TrendingUp, Users } from 'lucide-react';
import { ReactNode } from 'react';
import AppLayout from '@/Components/Bidscape/AppLayout';
import { MetricGrid, Panel } from '@/Components/Bidscape/UI';
import { money, percent } from '@/lib/format';

interface Props {
    sales: { total_contract_value_cents: number; average_contract_value_cents: number; contracts_won: number; by_category: Array<{ category: string; total_cents: number; count: number }> };
    sources: { rows: Array<{ name: string; leads_count: number; approved_count: number; contract_value_cents: number; conversion_basis_points: number }> };
    conversion: { created: number; sent: number; approved: number; signed: number; conversion_basis_points: number; signed_value_cents: number };
    dashboard: { sold_this_month: number; contract_value_this_month_cents: number; active_estimates: number; close_rate_basis_points: number; open_leads: number };
}

export default function ReportsIndex({ sales, sources, conversion, dashboard }: Props) {
    const cards = [
        ['Sales Summary', 'View a summary of products sold and total contract value.', ['Products / Services Sold', 'Total Contract Value', 'Average Contract Value', 'Sales by Month'], PieChart],
        ['Lead Source Report', 'See where your best leads come from and total contract value by source.', ['Leads by Source', 'Converted to Estimate', 'Converted to Contract', 'Contract Value by Source'], Filter],
        ['Estimate Conversion Performance', 'Analyze how estimates are converting to contracts and total value.', ['Conversion Rate', 'Estimates Sent', 'Signed Contracts', 'Contract Value from Estimates'], TrendingUp],
    ] as const;

    return (
        <AppLayout title="Reports" subtitle="Business performance from lead to contract.">
            <Head title="Reports" />
            <div className="space-y-6">
                <div className="grid gap-6 xl:grid-cols-3">
                    {cards.map(([title, copy, includes, Icon]) => (
                        <Panel key={title} className="p-8">
                            <span className="flex size-20 items-center justify-center rounded-lg bg-secondary text-primary"><Icon size={40} /></span>
                            <h2 className="mt-7 text-2xl font-black">{title}</h2>
                            <p className="mt-4 text-lg leading-7 text-muted-foreground">{copy}</p>
                            <div className="mt-7 border-t border-border pt-6">
                                <p className="text-xs font-black uppercase text-muted-foreground">Report Includes:</p>
                                <div className="mt-4 space-y-4">
                                    {includes.map((item) => <p key={item} className="flex items-center gap-3 font-medium"><CheckCircle2 size={18} className="text-primary" />{item}</p>)}
                                </div>
                            </div>
                            <button className="mt-8 flex h-14 w-full items-center justify-center gap-3 rounded-md bg-secondary font-black text-primary">Open Report <ArrowRight size={18} /></button>
                        </Panel>
                    ))}
                </div>
                <Panel className="p-7">
                    <h2 className="text-xl font-black">Quick Insights (This Month)</h2>
                    <MetricGrid>
                        <Insight icon={<Handshake />} label="Signed Contracts" value={String(dashboard.sold_this_month)} />
                        <Insight icon={<TrendingUp />} label="Contract Value" value={money(dashboard.contract_value_this_month_cents)} />
                        <Insight icon={<TrendingUp />} label="Estimates Sent" value={String(conversion.sent)} />
                        <Insight icon={<CheckCircle2 />} label="Conversion Rate" value={percent(conversion.conversion_basis_points)} />
                        <Insight icon={<Users />} label="New Leads" value={String(dashboard.open_leads)} />
                    </MetricGrid>
                </Panel>
                <div className="grid gap-6 xl:grid-cols-3">
                    <Panel className="p-6"><h3 className="font-black">Top Categories</h3><div className="mt-4 space-y-3">{sales.by_category.slice(0, 4).map((row) => <div key={row.category} className="flex justify-between"><span>{row.category}</span><strong>{money(row.total_cents)}</strong></div>)}</div></Panel>
                    <Panel className="p-6"><h3 className="font-black">Lead Sources</h3><div className="mt-4 space-y-3">{sources.rows.slice(0, 4).map((row) => <div key={row.name} className="flex justify-between"><span>{row.name}</span><strong>{percent(row.conversion_basis_points)}</strong></div>)}</div></Panel>
                    <Panel className="p-6"><h3 className="font-black">Estimate Funnel</h3><div className="mt-4 space-y-3">{[['Created', conversion.created], ['Sent', conversion.sent], ['Approved', conversion.approved], ['Signed', conversion.signed]].map(([label, value]) => <div key={label} className="flex justify-between"><span>{label}</span><strong>{value}</strong></div>)}</div></Panel>
                </div>
            </div>
        </AppLayout>
    );
}

function Insight({ icon, label, value }: { icon: ReactNode; label: string; value: string }) {
    return <div className="flex items-center gap-5 border-r border-border last:border-r-0"><span className="flex size-16 items-center justify-center rounded-full bg-secondary text-primary">{icon}</span><span><p className="text-sm text-muted-foreground">{label}</p><p className="text-3xl font-black">{value}</p><p className="mt-2 text-sm font-bold text-primary">vs last month</p></span></div>;
}
