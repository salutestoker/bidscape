import { Head, Link } from '@inertiajs/react';
import {
    Bell,
    Building2,
    FileInput,
    FileSliders,
    ListChecks,
    PackageOpen,
} from 'lucide-react';
import AppLayout from '@/Components/Bidscape/AppLayout';
import { Panel } from '@/Components/Bidscape/UI';

interface Section {
    key: string;
    title: string;
    description: string;
    href: string | null;
    disabled: boolean;
}

interface Props {
    sections: Section[];
}

const icons = {
    company_profile: Building2,
    company_defaults: FileSliders,
    price_sheet: PackageOpen,
    notification_settings: Bell,
    import_price_sheet: FileInput,
};

export default function SettingsIndex({ sections }: Props) {
    return (
        <AppLayout
            title="Settings"
            subtitle="Manage your company, account, defaults, and integrations."
        >
            <Head title="Settings" />
            <div className="grid gap-6 xl:grid-cols-2">
                {sections.map((section) => {
                    const Icon = icons[section.key as keyof typeof icons] ?? ListChecks;

                    return (
                        <Panel
                            key={section.key}
                            className="min-h-[180px] p-8"
                        >
                            <div className="flex items-start gap-8">
                                <span className="flex size-20 shrink-0 items-center justify-center rounded-lg bg-secondary text-primary">
                                    <Icon size={42} />
                                </span>
                                <div className="min-w-0">
                                    <h2 className="text-xl font-black">
                                        {section.title}
                                    </h2>
                                    <p className="mt-4 text-muted-foreground">
                                        {section.description}
                                    </p>
                                    {section.disabled ? (
                                        <span className="mt-7 inline-flex rounded-md border border-border bg-muted px-3 py-2 text-sm font-black text-muted-foreground">
                                            Planned
                                        </span>
                                    ) : (
                                        <Link
                                            href={section.href ?? '#'}
                                            className="mt-7 inline-flex items-center gap-2 font-black text-primary"
                                        >
                                            Configure <span>-&gt;</span>
                                        </Link>
                                    )}
                                </div>
                            </div>
                        </Panel>
                    );
                })}
            </div>
        </AppLayout>
    );
}
