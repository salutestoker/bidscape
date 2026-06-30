import { Head, Link } from '@inertiajs/react';
import {
    Check,
    Download,
    FileSpreadsheet,
    FileText,
    Folder,
    Image,
    MapPin,
    PackageOpen,
} from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/Components/Bidscape/AppLayout';
import { Panel, PrimaryButton } from '@/Components/Bidscape/UI';
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/Components/ui/tabs';
import { money } from '@/lib/format';

interface PacketItem {
    name: string;
    quantity: string;
    unit: string;
    item_type?: string;
    total_cents?: number;
    notes?: string;
}

interface Props {
    job: {
        id: number;
        number: string;
        project: string;
        status: string;
        contract_value_cents: number;
        contract_signed: string;
        site_address: string;
        site_notes: string;
        customer: { name: string; email: string; phone: string };
        deposit_paid_cents: number;
        balance_due_cents: number;
        packet: { id: number; packet_number: string; status: string } | null;
        snapshot: { items?: PacketItem[] };
        materials: PacketItem[];
        labor: PacketItem[];
        equipment: PacketItem[];
        attachments: Array<{ id: number; name: string; type: string; size_bytes: number }>;
    };
}

const tabs = ['Overview', 'Materials', 'Labor', 'Notes', 'Photos'] as const;

export default function JobPacket({ job }: Props) {
    const [activeTab, setActiveTab] = useState<(typeof tabs)[number]>('Overview');
    const scope = job.snapshot?.items ?? [];
    const pdfHref = job.packet ? `/job-packets/${job.packet.id}/pdf` : '#';

    return (
        <AppLayout
            title="Jobs"
            subtitle={`${job.project} - Job Packet`}
            action={
                <PrimaryButton href={pdfHref}>
                    <Download size={18} /> Download PDF
                </PrimaryButton>
            }
        >
            <Head title={`${job.project} Job Packet`} />
            <Tabs
                value={activeTab}
                onValueChange={(value) =>
                    setActiveTab(value as (typeof tabs)[number])
                }
                className="gap-6"
            >
                <TabsList variant="line" className="w-full justify-start">
                    {tabs.map((tab) => (
                        <TabsTrigger key={tab} value={tab} className="flex-none px-4">
                            {tab}
                        </TabsTrigger>
                    ))}
                </TabsList>

                <TabsContent value="Overview">
                    <Overview job={job} scope={scope} />
                </TabsContent>
                <TabsContent value="Materials">
                    <ItemPanel title="Materials" items={job.materials} />
                </TabsContent>
                <TabsContent value="Labor">
                    <ItemPanel
                        title="Labor And Equipment"
                        items={[...job.labor, ...job.equipment]}
                    />
                </TabsContent>
                <TabsContent value="Notes">
                    <Panel className="p-6">
                        <h2 className="text-xl font-black">Sales Handoff Notes</h2>
                        <p className="mt-5 leading-8 text-foreground">
                            {job.site_notes}
                        </p>
                        <ul className="mt-5 space-y-2">
                            {scope
                                .filter((item) => item.notes)
                                .map((item) => (
                                    <li key={item.name} className="text-sm">
                                        <strong>{item.name}:</strong>{' '}
                                        {item.notes}
                                    </li>
                            ))}
                        </ul>
                    </Panel>
                </TabsContent>
                <TabsContent value="Photos">
                    <Panel className="p-6">
                        <h2 className="text-xl font-black">Photos And Files</h2>
                        <div className="mt-5 grid gap-4 md:grid-cols-3">
                            {job.attachments.length ? (
                                job.attachments.map((attachment) => (
                                    <div
                                        key={attachment.id}
                                        className="rounded-lg border border-border p-4"
                                    >
                                        <Image className="text-primary" />
                                        <p className="mt-3 font-bold">
                                            {attachment.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {Math.round(
                                                attachment.size_bytes / 1024,
                                            )}{' '}
                                            KB
                                        </p>
                                    </div>
                                ))
                            ) : (
                                <p className="text-muted-foreground">
                                    No photos uploaded yet.
                                </p>
                            )}
                        </div>
                    </Panel>
                </TabsContent>

                <Panel className="p-6">
                    <h2 className="flex items-center gap-3 text-xl font-black">
                        <Folder className="text-primary" /> Packet Contents
                    </h2>
                    <div className="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {[
                            [FileText, 'Estimate PDF', 'Generated PDF'],
                            [FileText, 'Signed Contract', 'Signature record'],
                            [FileSpreadsheet, 'Estimate Breakdown', 'Snapshot data'],
                            [FileText, 'Scope of Work', 'Accepted scope'],
                            [Image, 'Site Photos', 'Uploaded files'],
                        ].map(([Icon, title, meta]) => (
                            <div
                                key={title as string}
                                className="rounded-lg border border-border p-4"
                            >
                                <Icon className="text-primary" />
                                <p className="mt-3 font-bold">
                                    {title as string}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {meta as string}
                                </p>
                            </div>
                        ))}
                    </div>
                </Panel>
                <Link
                    method="post"
                    href={`/jobs/${job.id}/packet-ready`}
                    as="button"
                    className="rounded-md bg-secondary px-5 py-3 text-sm font-bold text-primary"
                >
                    Mark Packet Ready
                </Link>
            </Tabs>
        </AppLayout>
    );
}

function Overview({ job, scope }: { job: Props['job']; scope: PacketItem[] }) {
    return (
        <div className="grid gap-6 xl:grid-cols-[1fr_1.3fr]">
            <div className="space-y-6">
                <Panel className="p-6">
                    <h2 className="flex items-center gap-3 text-xl font-black">
                        <PackageOpen className="text-primary" /> Job
                        Information
                    </h2>
                    <dl className="mt-5 grid grid-cols-[170px_1fr] gap-3 text-sm">
                        {[
                            ['Job Name', job.project],
                            ['Job ID', job.number],
                            ['Status', job.status],
                            ['Contract Signed', job.contract_signed],
                            ['Customer', job.customer.name],
                            ['Phone', job.customer.phone],
                            ['Email', job.customer.email],
                        ].map(([label, value]) => (
                            <div key={label} className="contents">
                                <dt className="text-muted-foreground">{label}</dt>
                                <dd className="font-bold">{value}</dd>
                            </div>
                        ))}
                    </dl>
                </Panel>
                <Panel className="p-6">
                    <h2 className="flex items-center gap-3 text-xl font-black">
                        <FileText className="text-primary" /> Scope of Work
                    </h2>
                    <ul className="mt-4 space-y-2 text-sm">
                        {scope.map((item) => (
                            <li key={item.name} className="flex gap-3">
                                <Check
                                    size={17}
                                    className="text-primary"
                                />{' '}
                                Install {item.quantity} {item.unit} {item.name}
                            </li>
                        ))}
                    </ul>
                </Panel>
            </div>
            <div className="space-y-6">
                <Panel className="grid gap-6 p-6 md:grid-cols-[1fr_1.1fr]">
                    <div>
                        <h2 className="flex items-center gap-3 text-xl font-black">
                            <MapPin className="text-primary" /> Site Address
                        </h2>
                        <p className="mt-5 font-bold">{job.site_address}</p>
                        <p className="text-muted-foreground">Mesa, AZ 85204</p>
                    </div>
                    <img
                        src="/images/demo/map.svg"
                        alt="Map preview"
                        className="h-44 w-full rounded-lg object-cover"
                    />
                </Panel>
                <Panel className="p-6">
                    <h2 className="flex items-center gap-3 text-xl font-black">
                        <FileText className="text-primary" /> Site Notes
                    </h2>
                    <p className="mt-5 leading-8 text-foreground">
                        {job.site_notes}
                    </p>
                </Panel>
                <Panel className="grid gap-4 p-6 md:grid-cols-4">
                    <Metric
                        label="Contract Value"
                        value={money(job.contract_value_cents)}
                    />
                    <Metric
                        label="Deposit Paid"
                        value={money(job.deposit_paid_cents)}
                    />
                    <Metric
                        label="Balance Due"
                        value={money(job.balance_due_cents)}
                    />
                    <Metric label="Payment Terms" value="50% Deposit" />
                </Panel>
            </div>
        </div>
    );
}

function ItemPanel({ title, items }: { title: string; items: PacketItem[] }) {
    return (
        <Panel className="p-6">
            <h2 className="text-xl font-black">{title}</h2>
            <div className="mt-5 overflow-x-auto">
                <table className="min-w-full text-left text-sm">
                    <thead className="border-y border-border bg-muted/50 text-xs font-bold uppercase text-muted-foreground">
                        <tr>
                            <th className="px-4 py-3">Item</th>
                            <th>Type</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-border">
                        {items.map((item) => (
                            <tr key={`${item.name}-${item.item_type}`}>
                                <td className="px-4 py-3 font-bold">
                                    {item.name}
                                </td>
                                <td className="capitalize">
                                    {(item.item_type ?? 'scope').replace(
                                        '_',
                                        ' ',
                                    )}
                                </td>
                                <td>{item.quantity}</td>
                                <td>{item.unit}</td>
                                <td>{money(item.total_cents ?? 0)}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </Panel>
    );
}

function Metric({ label, value }: { label: string; value: string }) {
    return (
        <div className="border-r border-border last:border-r-0">
            <p className="text-xs font-semibold text-muted-foreground">{label}</p>
            <p className="mt-2 text-xl font-black">{value}</p>
        </div>
    );
}
