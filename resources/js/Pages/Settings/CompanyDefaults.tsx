import { Head, useForm } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    Eye,
    EyeOff,
    Plus,
    Save,
} from 'lucide-react';
import { FormEvent, ReactNode, useState } from 'react';
import AppLayout from '@/Components/Bidscape/AppLayout';
import {
    Drawer,
    PrimaryButton,
    SecondaryButton,
} from '@/Components/Bidscape/UI';
import {
    Field,
    SectionPanel,
    SettingsBackLink,
    basisPointsToPercent,
    percentToBasisPoints,
} from './Partials';

interface TemplateSection {
    key: string;
    label: string;
    enabled: boolean;
    sort_order: number;
}

interface SectionDefinition {
    key: string;
    label: string;
    description: string;
    enabled: boolean;
}

interface Template {
    terms_text?: string | null;
    email_subject?: string | null;
    email_body?: string | null;
    recipients: string[];
    sections: TemplateSection[];
}

interface LeadSource {
    id: number;
    name: string;
    channel?: string | null;
    is_active: boolean;
}

interface Props {
    company: {
        default_price_sheet_markup_basis_points: number;
    };
    estimateTemplate: Template;
    jobPacketTemplate: Template;
    estimateSectionDefinitions: SectionDefinition[];
    jobPacketSectionDefinitions: SectionDefinition[];
    leadSources: LeadSource[];
}

interface DefaultsForm {
    default_price_sheet_markup_basis_points: string | number;
    estimate_template: {
        terms_text: string;
        email_subject: string;
        email_body: string;
        sections: TemplateSection[];
    };
    job_packet_template: {
        recipients: string[];
        sections: TemplateSection[];
    };
}

interface LeadSourceForm {
    name: string;
    channel: string;
    is_active: boolean;
}

export default function CompanyDefaults({
    company,
    estimateTemplate,
    jobPacketTemplate,
    estimateSectionDefinitions,
    jobPacketSectionDefinitions,
    leadSources,
}: Props) {
    const [leadDrawerOpen, setLeadDrawerOpen] = useState(false);
    const [editingLeadSource, setEditingLeadSource] =
        useState<LeadSource | null>(null);
    const form = useForm<DefaultsForm>({
        default_price_sheet_markup_basis_points: basisPointsToPercent(
            company.default_price_sheet_markup_basis_points,
        ),
        estimate_template: {
            terms_text: estimateTemplate.terms_text ?? '',
            email_subject: estimateTemplate.email_subject ?? '',
            email_body: estimateTemplate.email_body ?? '',
            sections: sortedSections(
                estimateTemplate.sections,
                estimateSectionDefinitions,
            ),
        },
        job_packet_template: {
            recipients: jobPacketTemplate.recipients.length
                ? jobPacketTemplate.recipients
                : [''],
            sections: sortedSections(
                jobPacketTemplate.sections,
                jobPacketSectionDefinitions,
            ),
        },
    });
    const leadForm = useForm<LeadSourceForm>({
        name: '',
        channel: '',
        is_active: true,
    });
    const errors = form.errors as Record<string, string | undefined>;

    function saveDefaults(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            default_price_sheet_markup_basis_points: percentToBasisPoints(
                data.default_price_sheet_markup_basis_points,
            ),
            job_packet_template: {
                ...data.job_packet_template,
                recipients: data.job_packet_template.recipients
                    .map((email) => email.trim())
                    .filter(Boolean),
            },
        }));
        form.put('/settings/company-defaults', { preserveScroll: true });
    }

    function openLeadSource(source?: LeadSource) {
        setEditingLeadSource(source ?? null);
        leadForm.setData({
            name: source?.name ?? '',
            channel: source?.channel ?? '',
            is_active: source?.is_active ?? true,
        });
        leadForm.clearErrors();
        setLeadDrawerOpen(true);
    }

    function saveLeadSource(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (editingLeadSource) {
            leadForm.put(
                `/settings/company-defaults/lead-sources/${editingLeadSource.id}`,
                {
                    preserveScroll: true,
                    onSuccess: () => setLeadDrawerOpen(false),
                },
            );
            return;
        }

        leadForm.post('/settings/company-defaults/lead-sources', {
            preserveScroll: true,
            onSuccess: () => setLeadDrawerOpen(false),
        });
    }

    function setEstimateSections(sections: TemplateSection[]) {
        form.setData('estimate_template', {
            ...form.data.estimate_template,
            sections,
        });
    }

    function setJobPacketSections(sections: TemplateSection[]) {
        form.setData('job_packet_template', {
            ...form.data.job_packet_template,
            sections,
        });
    }

    function setJobRecipient(index: number, value: string) {
        const recipients = [...form.data.job_packet_template.recipients];
        recipients[index] = value;
        form.setData('job_packet_template', {
            ...form.data.job_packet_template,
            recipients,
        });
    }

    function addJobRecipient() {
        form.setData('job_packet_template', {
            ...form.data.job_packet_template,
            recipients: [...form.data.job_packet_template.recipients, ''],
        });
    }

    function removeJobRecipient(index: number) {
        const recipients = form.data.job_packet_template.recipients.filter(
            (_email, emailIndex) => emailIndex !== index,
        );
        form.setData('job_packet_template', {
            ...form.data.job_packet_template,
            recipients: recipients.length ? recipients : [''],
        });
    }

    return (
        <AppLayout
            title="Company Defaults"
            subtitle="Manage estimate defaults, packet templates, price markup, and lead sources."
            action={<SettingsBackLink />}
        >
            <Head title="Company Defaults" />
            <form onSubmit={saveDefaults} className="space-y-6">
                <SectionPanel
                    title="Price Sheet Defaults"
                    description="This default markup is used for new price sheet rows unless the row is overridden."
                >
                    <div className="max-w-sm">
                        <Field
                            label="Default Markup Percentage"
                            error={
                                errors.default_price_sheet_markup_basis_points
                            }
                            hint="Stored as basis points and calculated as cost plus markup."
                        >
                            <div className="flex items-center gap-3">
                                <input
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    className="field"
                                    value={
                                        form.data
                                            .default_price_sheet_markup_basis_points
                                    }
                                    onChange={(event) =>
                                        form.setData(
                                            'default_price_sheet_markup_basis_points',
                                            event.target.value,
                                        )
                                    }
                                />
                                <span className="font-black text-muted-foreground">
                                    %
                                </span>
                            </div>
                        </Field>
                    </div>
                </SectionPanel>

                <SectionPanel
                    title="Estimate Template"
                    description="Control default estimate copy and the PDF/public review section order."
                >
                    <div className="grid gap-6 xl:grid-cols-[1fr_420px]">
                        <div className="space-y-4">
                            <Field
                                label="Default Terms And Conditions"
                                error={errors['estimate_template.terms_text']}
                            >
                                <textarea
                                    className="field min-h-36"
                                    value={
                                        form.data.estimate_template.terms_text
                                    }
                                    onChange={(event) =>
                                        form.setData('estimate_template', {
                                            ...form.data.estimate_template,
                                            terms_text: event.target.value,
                                        })
                                    }
                                />
                            </Field>
                            <Field
                                label="Default Estimate Email Subject"
                                error={
                                    errors['estimate_template.email_subject']
                                }
                            >
                                <input
                                    className="field"
                                    value={
                                        form.data.estimate_template
                                            .email_subject
                                    }
                                    onChange={(event) =>
                                        form.setData('estimate_template', {
                                            ...form.data.estimate_template,
                                            email_subject: event.target.value,
                                        })
                                    }
                                />
                            </Field>
                            <Field
                                label="Default Estimate Email Message"
                                error={errors['estimate_template.email_body']}
                            >
                                <textarea
                                    className="field min-h-28"
                                    value={
                                        form.data.estimate_template.email_body
                                    }
                                    onChange={(event) =>
                                        form.setData('estimate_template', {
                                            ...form.data.estimate_template,
                                            email_body: event.target.value,
                                        })
                                    }
                                />
                            </Field>
                        </div>
                        <TemplateBuilder
                            title="Estimate Layout"
                            definitions={estimateSectionDefinitions}
                            sections={form.data.estimate_template.sections}
                            onChange={setEstimateSections}
                        />
                    </div>
                </SectionPanel>

                <SectionPanel
                    title="Job Packet Template"
                    description="Control packet handoff sections and the recipients used when packet delivery is added."
                >
                    <div className="grid gap-6 xl:grid-cols-[1fr_420px]">
                        <div>
                            <div className="mb-3 flex items-center justify-between gap-3">
                                <h3 className="font-black">Packet Recipients</h3>
                                <SecondaryButton
                                    type="button"
                                    className="h-9 px-3"
                                    onClick={addJobRecipient}
                                >
                                    <Plus size={16} /> Add
                                </SecondaryButton>
                            </div>
                            <div className="space-y-3">
                                {form.data.job_packet_template.recipients.map(
                                    (email, index) => (
                                        <div
                                            key={index}
                                            className="flex gap-2"
                                        >
                                            <input
                                                type="email"
                                                className="field"
                                                value={email}
                                                placeholder="handoff@example.com"
                                                onChange={(event) =>
                                                    setJobRecipient(
                                                        index,
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <SecondaryButton
                                                type="button"
                                                className="h-11 px-3"
                                                onClick={() =>
                                                    removeJobRecipient(index)
                                                }
                                            >
                                                Remove
                                            </SecondaryButton>
                                        </div>
                                    ),
                                )}
                            </div>
                        </div>
                        <TemplateBuilder
                            title="Job Packet Layout"
                            definitions={jobPacketSectionDefinitions}
                            sections={form.data.job_packet_template.sections}
                            onChange={setJobPacketSections}
                        />
                    </div>
                </SectionPanel>

                <SectionPanel
                    title="Lead Sources"
                    description="These sources appear when creating or editing leads."
                >
                    <div className="mb-5 flex justify-end">
                        <PrimaryButton
                            type="button"
                            onClick={() => openLeadSource()}
                        >
                            <Plus size={17} /> New Lead Source
                        </PrimaryButton>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-left text-sm">
                            <thead className="border-y border-border bg-muted/50 text-xs font-bold uppercase text-muted-foreground">
                                <tr>
                                    <th className="px-4 py-3">Name</th>
                                    <th>Channel</th>
                                    <th>Status</th>
                                    <th className="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {leadSources.map((source) => (
                                    <tr key={source.id}>
                                        <td className="px-4 py-4 font-black">
                                            {source.name}
                                        </td>
                                        <td>
                                            {source.channel || 'Not assigned'}
                                        </td>
                                        <td>
                                            {source.is_active
                                                ? 'Active'
                                                : 'Inactive'}
                                        </td>
                                        <td className="text-right">
                                            <button
                                                type="button"
                                                className="font-black text-primary"
                                                onClick={() =>
                                                    openLeadSource(source)
                                                }
                                            >
                                                Edit
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </SectionPanel>

                <div className="flex justify-end">
                    <PrimaryButton type="submit" disabled={form.processing}>
                        <Save size={17} /> Save Defaults
                    </PrimaryButton>
                </div>
            </form>

            <Drawer
                open={leadDrawerOpen}
                title={editingLeadSource ? 'Edit Lead Source' : 'New Lead Source'}
                subtitle="Lead sources stay company-scoped and can be deactivated without losing history."
                onClose={() => setLeadDrawerOpen(false)}
            >
                <form onSubmit={saveLeadSource} className="space-y-4">
                    <Field label="Name" error={leadForm.errors.name}>
                        <input
                            className="field"
                            value={leadForm.data.name}
                            onChange={(event) =>
                                leadForm.setData('name', event.target.value)
                            }
                        />
                    </Field>
                    <Field label="Channel" error={leadForm.errors.channel}>
                        <input
                            className="field"
                            value={leadForm.data.channel}
                            onChange={(event) =>
                                leadForm.setData('channel', event.target.value)
                            }
                        />
                    </Field>
                    <label className="flex items-center gap-3 text-sm font-bold">
                        <input
                            type="checkbox"
                            checked={leadForm.data.is_active}
                            onChange={(event) =>
                                leadForm.setData(
                                    'is_active',
                                    event.target.checked,
                                )
                            }
                        />
                        Active
                    </label>
                    <div className="flex justify-end gap-3 pt-2">
                        <SecondaryButton
                            type="button"
                            onClick={() => setLeadDrawerOpen(false)}
                        >
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton
                            type="submit"
                            disabled={leadForm.processing}
                        >
                            <Save size={17} /> Save Lead Source
                        </PrimaryButton>
                    </div>
                </form>
            </Drawer>
        </AppLayout>
    );
}

function TemplateBuilder({
    title,
    definitions,
    sections,
    onChange,
}: {
    title: string;
    definitions: SectionDefinition[];
    sections: TemplateSection[];
    onChange: (sections: TemplateSection[]) => void;
}) {
    const descriptions = Object.fromEntries(
        definitions.map((definition) => [
            definition.key,
            definition.description,
        ]),
    );

    function update(index: number, section: TemplateSection) {
        onChange(sections.map((item, itemIndex) => (itemIndex === index ? section : item)));
    }

    function move(index: number, direction: -1 | 1) {
        const target = index + direction;
        if (target < 0 || target >= sections.length) {
            return;
        }

        const next = [...sections];
        [next[index], next[target]] = [next[target], next[index]];
        onChange(next.map((section, order) => ({ ...section, sort_order: order + 1 })));
    }

    return (
        <div className="rounded-lg border border-border bg-muted/40 p-4">
            <h3 className="font-black">{title}</h3>
            <div className="mt-4 space-y-3">
                {sections.map((section, index) => (
                    <div
                        key={section.key}
                        className="rounded-md border border-border bg-card p-4"
                    >
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="font-black">{section.label}</p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {descriptions[section.key]}
                                </p>
                            </div>
                            <div className="flex shrink-0 gap-1">
                                <IconButton
                                    label="Move up"
                                    disabled={index === 0}
                                    onClick={() => move(index, -1)}
                                >
                                    <ArrowUp size={16} />
                                </IconButton>
                                <IconButton
                                    label="Move down"
                                    disabled={index === sections.length - 1}
                                    onClick={() => move(index, 1)}
                                >
                                    <ArrowDown size={16} />
                                </IconButton>
                                <IconButton
                                    label={
                                        section.enabled
                                            ? 'Disable section'
                                            : 'Enable section'
                                    }
                                    onClick={() =>
                                        update(index, {
                                            ...section,
                                            enabled: !section.enabled,
                                        })
                                    }
                                >
                                    {section.enabled ? (
                                        <Eye size={16} />
                                    ) : (
                                        <EyeOff size={16} />
                                    )}
                                </IconButton>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
            <div className="mt-5 rounded-md bg-secondary/70 p-4 text-sm">
                <p className="font-black text-primary">Preview Order</p>
                <p className="mt-2 text-muted-foreground">
                    {sections
                        .filter((section) => section.enabled)
                        .map((section) => section.label)
                        .join(' -> ') || 'No sections enabled'}
                </p>
            </div>
        </div>
    );
}

function IconButton({
    label,
    disabled = false,
    onClick,
    children,
}: {
    label: string;
    disabled?: boolean;
    onClick: () => void;
    children: ReactNode;
}) {
    return (
        <button
            type="button"
            aria-label={label}
            disabled={disabled}
            onClick={onClick}
            className="flex size-9 items-center justify-center rounded-md border border-border bg-card text-muted-foreground transition hover:bg-muted hover:text-foreground disabled:cursor-not-allowed disabled:opacity-40"
        >
            {children}
        </button>
    );
}

function sortedSections(
    sections: TemplateSection[],
    definitions: SectionDefinition[],
): TemplateSection[] {
    const existing = Object.fromEntries(
        sections.map((section) => [section.key, section]),
    );

    return definitions
        .map((definition, index) => ({
            key: definition.key,
            label: existing[definition.key]?.label ?? definition.label,
            enabled: existing[definition.key]?.enabled ?? definition.enabled,
            sort_order: existing[definition.key]?.sort_order ?? index + 1,
        }))
        .sort((a, b) => a.sort_order - b.sort_order);
}
