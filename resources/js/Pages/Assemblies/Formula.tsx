import { Head } from '@inertiajs/react';
import { Box, Calculator, Eye, FlaskConical, Save, Users, Wrench } from 'lucide-react';
import AppLayout from '@/Components/Bidscape/AppLayout';
import { Panel, PrimaryButton } from '@/Components/Bidscape/UI';
import { money, percent } from '@/lib/format';

interface Component { id: number; name: string; quantity_per_unit: string; unit: string; unit_cost_cents: number; formula?: string; }
interface Assembly {
    id: number; name: string; unit: string; waste_factor_basis_points: number; base_depth_inches: string | null; labor_hours_per_unit: string; default_minutes_per_unit: string; production_rate_per_day: string | null; base_cost_cents: number; markup_basis_points: number; selling_price_cents: number; components: Component[];
}

export default function Formula({ assembly }: { assembly: Assembly }) {
    return (
        <AppLayout title="Assemblies" subtitle={`${assembly.name} - Formula Editor`} action={<PrimaryButton><Save size={18} /> Save Assembly</PrimaryButton>}>
            <Head title={`${assembly.name} Formula`} />
            <div className="space-y-6">
                <div className="flex flex-wrap gap-4">
                    {[[Calculator, 'General'], [FlaskConical, 'Formula'], [Box, 'Components'], [Users, 'Labor'], [Wrench, 'Equipment']].map(([Icon, label], index) => <button key={label as string} className={`flex h-14 items-center gap-3 rounded-md border px-6 font-bold ${index === 1 ? 'border-border bg-secondary text-primary' : 'border-border bg-card'}`}><Icon /> {label as string}</button>)}
                </div>
                <div className="grid gap-6 xl:grid-cols-[1.1fr_.9fr]">
                    <Panel className="p-7">
                        <h2 className="flex items-center gap-4 text-xl font-black"><span className="flex size-14 items-center justify-center rounded-lg bg-secondary text-primary"><Calculator /></span> Assembly Formula</h2>
                        <p className="mt-2 text-muted-foreground">Define the estimated material, labor, and hardware quantities per unit.</p>
                        <div className="mt-8 grid gap-6 md:grid-cols-2">
                            <Field label="Unit" value={assembly.unit} />
                            <Field label="Waste Factor (%)" value={percent(assembly.waste_factor_basis_points)} />
                            <Field label="Base Depth (in)" value={assembly.base_depth_inches ?? 'N/A'} />
                            <Field label="Labor Hours / 100 sqft" value={assembly.labor_hours_per_unit} />
                            <Field label="Default Mins / 100 sqft" value={assembly.default_minutes_per_unit} />
                            <Field label="Production Rate" value={assembly.production_rate_per_day ? `${assembly.production_rate_per_day} sqft / day` : 'N/A'} />
                        </div>
                    </Panel>
                    <Panel className="p-7">
                        <h2 className="flex items-center gap-4 text-xl font-black"><span className="flex size-14 items-center justify-center rounded-lg bg-secondary text-primary"><Box /></span> Component Breakdown</h2>
                        <p className="mt-2 text-muted-foreground">All components and their required quantities per unit.</p>
                        <div className="mt-6 space-y-3">
                            {assembly.components.map((component) => <div key={component.id} className="flex items-center justify-between rounded-md border border-border p-4"><strong>{component.name}</strong><span className="text-muted-foreground">{component.quantity_per_unit} {component.unit}</span><strong className="text-primary">{money(component.unit_cost_cents)}</strong></div>)}
                        </div>
                        <div className="mt-7 grid gap-3 rounded-md bg-secondary p-4 md:grid-cols-4">
                            <Metric label="Base Cost" value={money(assembly.base_cost_cents)} />
                            <Metric label="Markup" value={percent(assembly.markup_basis_points)} />
                            <Metric label="Selling Price" value={money(assembly.selling_price_cents)} />
                            <Metric label="Unit" value={assembly.unit} />
                        </div>
                        <button className="mt-6 flex h-14 w-full items-center justify-center gap-3 rounded-md border border-border bg-secondary font-bold text-primary"><Eye /> View / Edit Details</button>
                    </Panel>
                </div>
            </div>
        </AppLayout>
    );
}

function Field({ label, value }: { label: string; value: string }) {
    return <label className="block"><span className="text-sm font-bold text-muted-foreground">{label}</span><span className="mt-2 flex h-16 items-center rounded-md border border-border bg-card px-5 font-semibold">{value}</span></label>;
}

function Metric({ label, value }: { label: string; value: string }) {
    return <div><p className="text-xs font-semibold text-muted-foreground">{label}</p><p className="font-black">{value}</p></div>;
}
