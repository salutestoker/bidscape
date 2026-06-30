import { Head } from '@inertiajs/react';
import {
    ArrowRight,
    Check,
    ClipboardList,
    DollarSign,
    FileText,
    Plus,
    Target,
    Users,
} from 'lucide-react';
import AppLayout from '@/Components/Bidscape/AppLayout';
import {
    KpiCard,
    MetricGrid,
    Panel,
    PrimaryButton,
    SecondaryButton,
} from '@/Components/Bidscape/UI';
import { money } from '@/lib/format';

interface MetricProps {
    open_leads: number;
    active_estimates: number;
    sold_this_month: number;
    contract_value_this_month_cents: number;
    close_rate_basis_points: number;
}

interface PipelineItem {
    label: string;
    count: number;
    value_cents: number;
}

interface Activity {
    event: string;
    description: string;
    time: string;
}

interface Props {
    metrics: MetricProps;
    pipeline: PipelineItem[];
    activities: Activity[];
    trend: Array<{ label: string; value_cents: number }>;
    quickStats: Array<{ label: string; value?: string | number; value_cents?: number }>;
}

export default function Dashboard({
    metrics,
    pipeline,
    activities,
    trend,
    quickStats,
}: Props) {
    const cents = (value: number | string | null | undefined) => Number(value ?? 0);
    const maxTrend = Math.max(...trend.map((item) => cents(item.value_cents)), 1);
    const totalPipeline = pipeline.reduce((sum, item) => sum + cents(item.value_cents), 0);

    return (
        <AppLayout
            title="Good morning, Nick."
            subtitle="Here's what's happening with your business today."
        >
            <Head title="Dashboard" />
            <div className="space-y-6">
                <MetricGrid>
                    <KpiCard label="Open Leads" value={metrics.open_leads} trend="Needs action" icon={Users} />
                    <KpiCard label="Active Estimates" value={metrics.active_estimates} trend="In sales flow" icon={FileText} />
                    <KpiCard label="Sold (This Month)" value={metrics.sold_this_month} trend="Signed estimates" icon={DollarSign} />
                    <KpiCard label="Contract Value" value={metrics.contract_value_this_month_cents} trend="This month" icon={DollarSign} moneyValue />
                    <KpiCard label="Close Rate" value={metrics.close_rate_basis_points} trend="Signed / resolved" icon={Target} percentValue />
                </MetricGrid>

                <div className="grid gap-6 xl:grid-cols-[1.35fr_.85fr] min-[1600px]:grid-cols-[1.45fr_.85fr_.8fr]">
                    <section className="relative min-h-[330px] overflow-hidden rounded-lg bg-[#102016] p-9 text-white shadow-[0_18px_40px_rgba(15,23,42,.18)]">
                        <img
                            src="/images/demo/hero-landscape.svg"
                            className="absolute inset-0 h-full w-full object-cover"
                            alt=""
                        />
                        <div className="absolute inset-0 bg-gradient-to-r from-[#06120c]/90 via-[#06120c]/55 to-transparent" />
                        <div className="relative max-w-xl pt-8">
                            <h2 className="text-4xl font-black leading-tight tracking-normal md:text-5xl">
                                Build beautiful outdoor spaces. Profit with confidence.
                            </h2>
                            <p className="mt-5 text-lg font-medium text-white/88">
                                From lead to contract, all in one place.
                            </p>
                            <div className="mt-8 flex flex-wrap gap-4">
                                <PrimaryButton href="/estimates">
                                    <Plus size={18} /> New Estimate
                                </PrimaryButton>
                                <SecondaryButton className="border-white/45 bg-white/10 text-white hover:bg-white/18">
                                    Watch Demo <ArrowRight size={17} />
                                </SecondaryButton>
                            </div>
                        </div>
                    </section>

                    <Panel className="p-6">
                        <h2 className="text-lg font-bold">Pipeline Overview</h2>
                        <div className="mt-6 grid gap-5 md:grid-cols-[160px_1fr] xl:grid-cols-1 2xl:grid-cols-[160px_1fr]">
                            <div className="relative mx-auto flex size-44 items-center justify-center rounded-full bg-[conic-gradient(#08783f_0_38%,#35a365_38%_68%,#95cbaa_68%_84%,#d2d8d7_84%_100%)]">
                                <div className="flex size-28 flex-col items-center justify-center rounded-full bg-card text-center">
                                    <strong>{money(totalPipeline)}</strong>
                                    <span className="text-xs text-muted-foreground">Total Pipeline</span>
                                </div>
                            </div>
                            <div className="space-y-3">
                                {pipeline.map((item, index) => (
                                    <div key={item.label} className="flex items-center justify-between gap-4">
                                        <div className="flex items-center gap-3">
                                            <span className="size-3 rounded-full" style={{ background: ['#08783f', '#35a365', '#95cbaa', '#b8c1c1'][index] }} />
                                            <span className="font-medium text-muted-foreground">{item.label}</span>
                                        </div>
                                        <span className="font-bold">{money(item.value_cents)}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                        <a className="mt-6 flex h-10 items-center justify-center rounded-md bg-secondary text-sm font-bold text-primary" href="/estimates">
                            View Full Pipeline
                        </a>
                    </Panel>

                    <Panel className="p-6 xl:col-span-2 min-[1600px]:col-span-1">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-bold">Recent Activity</h2>
                            <a href="/reports" className="text-sm font-bold text-primary">
                                View all
                            </a>
                        </div>
                        <div className="mt-5 space-y-4">
                            {activities.map((activity, index) => (
                                <div key={`${activity.description}-${activity.time}-${index}`} className="flex items-center gap-4">
                                    <span className="flex size-9 items-center justify-center rounded-full bg-[#168447] text-white">
                                        <Check size={16} />
                                    </span>
                                    <p className="min-w-0 flex-1 truncate font-medium text-foreground">{activity.description}</p>
                                    <span className="text-xs font-medium text-muted-foreground">{activity.time}</span>
                                </div>
                            ))}
                        </div>
                    </Panel>
                </div>

                <div className="grid gap-6 xl:grid-cols-2 min-[1600px]:grid-cols-[1fr_1fr_.68fr]">
                    <Panel className="p-6 xl:col-span-2 min-[1600px]:col-span-1">
                        <h2 className="text-lg font-bold">Sales Pipeline</h2>
                        <div className="mt-8 flex h-56 items-end justify-around gap-5">
                            {pipeline.map((item, index) => {
                                const height = Math.max(42, (cents(item.value_cents) / Math.max(totalPipeline, 1)) * 520);
                                return (
                                    <div key={item.label} className="flex flex-1 flex-col items-center gap-3">
                                        <span className="font-bold">{money(item.value_cents)}</span>
                                        <div className="w-full max-w-[96px] rounded-t-md bg-primary" style={{ height, opacity: 1 - index * 0.12 }} />
                                        <span className="text-sm font-bold">{item.label}</span>
                                        <span className="text-2xl font-bold">{item.count}</span>
                                    </div>
                                );
                            })}
                        </div>
                    </Panel>

                    <Panel className="p-6">
                        <h2 className="text-lg font-bold">Contract Value Trend</h2>
                        <div className="mt-8 flex h-56 items-end gap-4 border-b border-l border-border pl-4">
                            {trend.map((item) => (
                                <div key={item.label} className="flex flex-1 flex-col items-center gap-3">
                                    <div className="w-full rounded-t-md bg-gradient-to-t from-[#d8eadf] to-[#08783f]" style={{ height: `${Math.max(14, (cents(item.value_cents) / maxTrend) * 190)}px` }} />
                                    <span className="text-xs font-medium text-muted-foreground">{item.label}</span>
                                </div>
                            ))}
                        </div>
                    </Panel>

                    <Panel className="p-6">
                        <h2 className="text-lg font-bold">Quick Actions</h2>
                        <div className="mt-5 divide-y divide-border">
                            {[
                                ['Add New Lead', '/leads'],
                                ['Create Estimate', '/estimates'],
                                ['View All Estimates', '/estimates'],
                                ['View Customers', '/customers'],
                                ['Browse Assemblies', '/assemblies'],
                                ['Manage Materials', '/materials'],
                            ].map(([label, href]) => (
                                <a key={label} href={href} className="flex items-center justify-between py-4 font-semibold">
                                    <span className="flex items-center gap-3"><ClipboardList size={18} className="text-primary" /> {label}</span>
                                    <ArrowRight size={16} />
                                </a>
                            ))}
                        </div>
                    </Panel>
                </div>

                <MetricGrid>
                    {quickStats.map((stat) => (
                        <Panel key={stat.label} className="p-5">
                            <p className="text-xs font-semibold text-muted-foreground">{stat.label}</p>
                            <p className="mt-2 text-2xl font-bold">
                                {stat.value_cents !== undefined ? money(stat.value_cents) : stat.value}
                            </p>
                            <p className="mt-2 text-xs font-bold text-primary">Updated today</p>
                        </Panel>
                    ))}
                </MetricGrid>
            </div>
        </AppLayout>
    );
}
