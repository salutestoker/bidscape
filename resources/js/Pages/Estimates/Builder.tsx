import { Head, router, useForm } from '@inertiajs/react';
import {
    Calculator,
    FileText,
    List,
    Mail,
    MoreVertical,
    Plus,
    Send,
    TrendingUp,
} from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import AppLayout from '@/Components/Bidscape/AppLayout';
import { Panel, PrimaryButton, SecondaryButton } from '@/Components/Bidscape/UI';
import { money, percent } from '@/lib/format';

interface Assembly {
    id: number;
    name: string;
    category: string;
    unit: string;
    base_cost_cents: number;
    selling_price_cents: number;
    image_path: string;
}

interface Material {
    id: number;
    name: string;
    type_label: string;
    category: string;
    unit: string;
    selling_price_cents: number;
    photo_path?: string | null;
}

interface EstimateItem {
    id: number;
    name: string;
    subtitle: string;
    item_type: string;
    quantity: string;
    unit: string;
    unit_price_cents: number;
    total_cents: number;
    thumbnail_path?: string | null;
}

interface Props {
    estimate: {
        id: number;
        number: string;
        project: string;
        client: string;
        client_email?: string | null;
        status: string;
        status_value: string;
        review_url?: string | null;
        summary: Record<string, number>;
        items: EstimateItem[];
    };
    assemblies: Assembly[];
    materials: Material[];
    itemTypes: Array<{ value: string; label: string }>;
}

const steps = ['Scope', 'Estimate', 'Review', 'Send / Email PDF'];

export default function EstimateBuilder({
    estimate,
    assemblies,
    materials,
    itemTypes,
}: Props) {
    const [itemType, setItemType] = useState('assembly');
    const [selectedAssembly, setSelectedAssembly] = useState(
        assemblies[0]?.id ?? 0,
    );
    const [selectedMaterial, setSelectedMaterial] = useState(
        materials[0]?.id ?? 0,
    );
    const [quantity, setQuantity] = useState('100');
    const [customName, setCustomName] = useState('');
    const [customUnit, setCustomUnit] = useState('each');
    const [customPrice, setCustomPrice] = useState('0');
    const sendForm = useForm({
        recipient: estimate.client_email ?? '',
        subject: `${estimate.project} estimate`,
        message: 'Your estimate is ready for review.',
    });

    const selectedSource = useMemo(() => {
        if (itemType === 'assembly') {
            return assemblies.find((assembly) => assembly.id === selectedAssembly);
        }

        if (itemType === 'material') {
            return materials.find((material) => material.id === selectedMaterial);
        }

        return null;
    }, [assemblies, itemType, materials, selectedAssembly, selectedMaterial]);

    function addItem(event: FormEvent) {
        event.preventDefault();
        router.post(
            `/estimates/${estimate.id}/items`,
            {
                item_type: itemType,
                assembly_id: itemType === 'assembly' ? selectedAssembly : null,
                material_id: itemType === 'material' ? selectedMaterial : null,
                name:
                    itemType === 'custom' ||
                    itemType === 'labor' ||
                    itemType === 'equipment'
                        ? customName
                        : null,
                quantity,
                unit:
                    itemType === 'custom' ||
                    itemType === 'labor' ||
                    itemType === 'equipment'
                        ? customUnit
                        : null,
                unit_price_cents:
                    itemType === 'custom' ||
                    itemType === 'labor' ||
                    itemType === 'equipment'
                        ? Number(customPrice)
                        : null,
            },
            { preserveScroll: true },
        );
    }

    function sendEstimate(event: FormEvent) {
        event.preventDefault();
        sendForm.post(`/estimates/${estimate.id}/send`, {
            preserveScroll: true,
        });
    }

    return (
        <AppLayout
            title="Estimate Builder"
            subtitle={`${estimate.project} - ${estimate.client}`}
            action={
                <PrimaryButton onClick={() => router.visit('/estimates')}>
                    <FileText size={18} /> All Estimates
                </PrimaryButton>
            }
        >
            <Head title={`Estimate Builder - ${estimate.project}`} />
            <div className="space-y-7">
                <div className="grid gap-3 md:grid-cols-4">
                    {steps.map((step, index) => (
                        <div
                            key={step}
                            className={`flex h-14 items-center justify-center gap-3 rounded-full border text-base font-bold ${index === 0 ? 'border-primary bg-primary text-white' : 'border-border bg-card text-foreground'}`}
                        >
                            <span>{index + 1}</span> {step}
                        </div>
                    ))}
                </div>
                <div className="grid gap-6 xl:grid-cols-[300px_1fr_340px]">
                    <Panel className="p-6">
                        <h2 className="text-lg font-black uppercase tracking-normal">
                            Scope Selector
                        </h2>
                        <form onSubmit={addItem} className="mt-5 space-y-4">
                            <select
                                value={itemType}
                                onChange={(event) =>
                                    setItemType(event.target.value)
                                }
                                className="field"
                            >
                                {itemTypes.map((type) => (
                                    <option key={type.value} value={type.value}>
                                        {type.label}
                                    </option>
                                ))}
                            </select>
                            {itemType === 'assembly' ? (
                                <select
                                    value={selectedAssembly}
                                    onChange={(event) =>
                                        setSelectedAssembly(
                                            Number(event.target.value),
                                        )
                                    }
                                    className="field"
                                >
                                    {assemblies.map((assembly) => (
                                        <option
                                            key={assembly.id}
                                            value={assembly.id}
                                        >
                                            {assembly.name}
                                        </option>
                                    ))}
                                </select>
                            ) : null}
                            {itemType === 'material' ? (
                                <select
                                    value={selectedMaterial}
                                    onChange={(event) =>
                                        setSelectedMaterial(
                                            Number(event.target.value),
                                        )
                                    }
                                    className="field"
                                >
                                    {materials.map((material) => (
                                        <option
                                            key={material.id}
                                            value={material.id}
                                        >
                                            {material.name}
                                        </option>
                                    ))}
                                </select>
                            ) : null}
                            {['custom', 'labor', 'equipment'].includes(
                                itemType,
                            ) ? (
                                <>
                                    <input
                                        value={customName}
                                        onChange={(event) =>
                                            setCustomName(event.target.value)
                                        }
                                        className="field"
                                        placeholder="Item name"
                                    />
                                    <div className="grid grid-cols-2 gap-3">
                                        <input
                                            value={customUnit}
                                            onChange={(event) =>
                                                setCustomUnit(
                                                    event.target.value,
                                                )
                                            }
                                            className="field"
                                            placeholder="Unit"
                                        />
                                        <input
                                            type="number"
                                            value={customPrice}
                                            onChange={(event) =>
                                                setCustomPrice(
                                                    event.target.value,
                                                )
                                            }
                                            className="field"
                                            placeholder="Cents"
                                        />
                                    </div>
                                </>
                            ) : null}
                            <input
                                value={quantity}
                                onChange={(event) =>
                                    setQuantity(event.target.value)
                                }
                                className="field"
                                aria-label="Quantity"
                            />
                            <PrimaryButton type="submit" className="w-full">
                                <Plus size={18} /> Add Scope Item
                            </PrimaryButton>
                        </form>
                        <div className="mt-5 space-y-3">
                            {(itemType === 'material'
                                ? materials.slice(0, 6)
                                : assemblies.slice(0, 6)
                            ).map((source) => (
                                <button
                                    key={source.id}
                                    onClick={() =>
                                        itemType === 'material'
                                            ? setSelectedMaterial(source.id)
                                            : setSelectedAssembly(source.id)
                                    }
                                    className={`flex w-full items-center gap-3 rounded-md border p-3 text-left font-bold ${selectedSource?.id === source.id ? 'border-border bg-secondary text-primary' : 'border-border bg-card'}`}
                                >
                                    <img
                                        src={sourceImage(source)}
                                        className="size-10 rounded-md object-cover"
                                        alt=""
                                    />
                                    <span>{source.name}</span>
                                </button>
                            ))}
                        </div>
                        <SecondaryButton href="/assemblies" className="mt-8 w-full">
                            Manage Assemblies
                        </SecondaryButton>
                    </Panel>

                    <Panel className="p-6">
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <h2 className="flex items-center gap-3 text-xl font-black uppercase tracking-normal">
                                <FileText className="text-primary" /> Scope
                                Items
                            </h2>
                            <SecondaryButton>
                                <List size={17} /> View as List
                            </SecondaryButton>
                        </div>
                        <div className="mt-6 overflow-x-auto">
                            <table className="min-w-full text-left">
                                <thead className="text-xs font-bold uppercase text-muted-foreground">
                                    <tr>
                                        <th className="py-4">Item</th>
                                        <th>Type</th>
                                        <th>Qty</th>
                                        <th>Unit</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                        <th />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {estimate.items.map((item) => (
                                        <tr key={item.id}>
                                            <td className="py-4">
                                                <div className="flex items-center gap-4">
                                                    <img
                                                        src={
                                                            item.thumbnail_path ??
                                                            '/images/demo/gravel.svg'
                                                        }
                                                        className="size-16 rounded-md object-cover"
                                                        alt=""
                                                    />
                                                    <div>
                                                        <p className="font-black">
                                                            {item.name}
                                                        </p>
                                                        <p className="text-sm text-muted-foreground">
                                                            {item.subtitle}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="font-semibold capitalize">
                                                {item.item_type.replace('_', ' ')}
                                            </td>
                                            <td className="font-semibold">
                                                {item.quantity}
                                            </td>
                                            <td className="text-muted-foreground">
                                                {item.unit}
                                            </td>
                                            <td>{money(item.unit_price_cents)}</td>
                                            <td className="font-black">
                                                {money(item.total_cents)}
                                            </td>
                                            <td>
                                                <MoreVertical size={18} />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Panel>

                    <div className="space-y-6">
                        <Panel className="p-6">
                            <h2 className="flex items-center gap-3 text-xl font-black">
                                <Calculator className="text-primary" />{' '}
                                Estimate Summary
                            </h2>
                            <div className="mt-7 space-y-5 text-base">
                                {[
                                    [
                                        'Material Cost',
                                        estimate.summary.material_cost_cents,
                                    ],
                                    ['Labor Cost', estimate.summary.labor_cost_cents],
                                    [
                                        'Equipment',
                                        estimate.summary.equipment_cost_cents,
                                    ],
                                    [
                                        'Delivery',
                                        estimate.summary.delivery_cost_cents,
                                    ],
                                ].map(([label, value]) => (
                                    <SummaryRow
                                        key={label as string}
                                        label={label as string}
                                        value={value as number}
                                    />
                                ))}
                                <div className="border-t border-border pt-5">
                                    <SummaryRow
                                        label="Subtotal"
                                        value={estimate.summary.direct_cost_cents}
                                    />
                                    <SummaryRow
                                        label="Overhead (10%)"
                                        value={estimate.summary.overhead_cents}
                                    />
                                    <SummaryRow
                                        label="Profit (30%)"
                                        value={estimate.summary.profit_cents}
                                    />
                                </div>
                                <div className="border-t border-border pt-7">
                                    <p className="text-sm font-black uppercase text-muted-foreground">
                                        Selling Price
                                    </p>
                                    <p className="mt-2 text-4xl font-black text-primary">
                                        {money(
                                            estimate.summary
                                                .selling_price_cents,
                                        )}
                                    </p>
                                    <p className="mt-2 text-lg font-bold text-primary">
                                        {percent(
                                            estimate.summary
                                                .gross_margin_basis_points,
                                        )}{' '}
                                        Margin
                                    </p>
                                </div>
                            </div>
                            <PrimaryButton className="mt-9 w-full bg-secondary text-primary hover:bg-secondary/80">
                                <TrendingUp size={18} /> View Margin Analysis
                            </PrimaryButton>
                        </Panel>
                        <Panel className="p-6">
                            <h2 className="flex items-center gap-3 text-xl font-black">
                                <Mail className="text-primary" /> Send PDF
                            </h2>
                            <form onSubmit={sendEstimate} className="mt-5 space-y-3">
                                <input
                                    className="field"
                                    value={sendForm.data.recipient}
                                    onChange={(event) =>
                                        sendForm.setData(
                                            'recipient',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="customer@email.com"
                                />
                                <input
                                    className="field"
                                    value={sendForm.data.subject}
                                    onChange={(event) =>
                                        sendForm.setData(
                                            'subject',
                                            event.target.value,
                                        )
                                    }
                                />
                                <textarea
                                    className="field min-h-24"
                                    value={sendForm.data.message}
                                    onChange={(event) =>
                                        sendForm.setData(
                                            'message',
                                            event.target.value,
                                        )
                                    }
                                />
                                <PrimaryButton type="submit" className="w-full">
                                    <Send size={17} /> Email Estimate PDF
                                </PrimaryButton>
                            </form>
                            {estimate.review_url ? (
                                <a
                                    className="mt-4 block truncate text-sm font-bold text-primary"
                                    href={estimate.review_url}
                                >
                                    {estimate.review_url}
                                </a>
                            ) : null}
                        </Panel>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

function SummaryRow({ label, value }: { label: string; value: number }) {
    return (
        <div className="flex items-center justify-between gap-4">
            <span className="font-medium text-muted-foreground">{label}</span>
            <span className="font-black">{money(value)}</span>
        </div>
    );
}

function sourceImage(source: Assembly | Material): string {
    if ('image_path' in source) {
        return source.image_path;
    }

    return source.photo_path ?? '/images/demo/gravel.svg';
}
