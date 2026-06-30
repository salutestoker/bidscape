import { Head, useForm } from '@inertiajs/react';
import { Plus, Save } from 'lucide-react';
import { FormEvent } from 'react';
import AppLayout from '@/Components/Bidscape/AppLayout';
import { PrimaryButton, SecondaryButton } from '@/Components/Bidscape/UI';
import { Field, SectionPanel, SettingsBackLink } from './Partials';

interface DigestSettings {
    digest_frequency: string;
    include_pipeline_summary: boolean;
    include_late_estimates: boolean;
    include_recent_activity: boolean;
    include_sales_summary: boolean;
}

interface ReminderRule {
    lead_status: string;
    lead_status_label: string;
    is_enabled: boolean;
    days_after_status: number | null;
    recipients: string[];
}

interface ReminderRuleForm {
    lead_status: string;
    lead_status_label: string;
    is_enabled: boolean;
    days_after_status: string | number | null;
    recipients: string[];
}

interface NotificationsForm {
    digest: DigestSettings;
    rules: ReminderRuleForm[];
}

interface Props {
    notificationSettings: {
        digest: DigestSettings;
        rules: ReminderRule[];
    };
    digestFrequencyOptions: Array<{ value: string; label: string }>;
}

export default function Notifications({
    notificationSettings,
    digestFrequencyOptions,
}: Props) {
    const form = useForm<NotificationsForm>({
        digest: notificationSettings.digest,
        rules: notificationSettings.rules.map((rule) => ({
            ...rule,
            days_after_status:
                rule.days_after_status !== null
                    ? String(rule.days_after_status)
                    : '',
            recipients: rule.recipients.length ? rule.recipients : [''],
        })),
    });
    const errors = form.errors as Record<string, string | undefined>;

    function saveNotifications(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            rules: data.rules.map((rule) => ({
                ...rule,
                days_after_status: rule.days_after_status
                    ? Number(rule.days_after_status)
                    : null,
                recipients: rule.recipients
                    .map((email) => email.trim())
                    .filter(Boolean),
            })),
        }));
        form.put('/settings/notifications', { preserveScroll: true });
    }

    function setDigest(field: keyof DigestSettings, value: string | boolean) {
        form.setData('digest', {
            ...form.data.digest,
            [field]: value,
        });
    }

    function setRule(index: number, rule: ReminderRuleForm) {
        form.setData('rules', form.data.rules.map((item, itemIndex) => (itemIndex === index ? rule : item)));
    }

    function setRecipient(ruleIndex: number, emailIndex: number, value: string) {
        const rule = form.data.rules[ruleIndex];
        const recipients = [...rule.recipients];
        recipients[emailIndex] = value;
        setRule(ruleIndex, { ...rule, recipients });
    }

    function addRecipient(ruleIndex: number) {
        const rule = form.data.rules[ruleIndex];
        setRule(ruleIndex, {
            ...rule,
            recipients: [...rule.recipients, ''],
        });
    }

    function removeRecipient(ruleIndex: number, emailIndex: number) {
        const rule = form.data.rules[ruleIndex];
        const recipients = rule.recipients.filter(
            (_email, index) => index !== emailIndex,
        );
        setRule(ruleIndex, {
            ...rule,
            recipients: recipients.length ? recipients : [''],
        });
    }

    return (
        <AppLayout
            title="Notification Settings"
            subtitle="Configure company digest emails and sales follow-up reminders."
            action={<SettingsBackLink />}
        >
            <Head title="Notification Settings" />
            <form onSubmit={saveNotifications} className="space-y-6">
                <SectionPanel
                    title="Company Performance Digest"
                    description="Select the frequency and content included in the company summary email."
                >
                    <div className="grid gap-6 lg:grid-cols-[320px_1fr]">
                        <Field
                            label="Digest Frequency"
                            error={errors['digest.digest_frequency']}
                        >
                            <select
                                className="field"
                                value={form.data.digest.digest_frequency}
                                onChange={(event) =>
                                    setDigest(
                                        'digest_frequency',
                                        event.target.value,
                                    )
                                }
                            >
                                {digestFrequencyOptions.map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </Field>
                        <div className="grid gap-3 md:grid-cols-2">
                            <CheckboxRow
                                label="Pipeline Summary"
                                checked={
                                    form.data.digest.include_pipeline_summary
                                }
                                onChange={(checked) =>
                                    setDigest(
                                        'include_pipeline_summary',
                                        checked,
                                    )
                                }
                            />
                            <CheckboxRow
                                label="Late Estimates"
                                checked={form.data.digest.include_late_estimates}
                                onChange={(checked) =>
                                    setDigest('include_late_estimates', checked)
                                }
                            />
                            <CheckboxRow
                                label="Recent Activity"
                                checked={
                                    form.data.digest.include_recent_activity
                                }
                                onChange={(checked) =>
                                    setDigest(
                                        'include_recent_activity',
                                        checked,
                                    )
                                }
                            />
                            <CheckboxRow
                                label="Sales Summary"
                                checked={form.data.digest.include_sales_summary}
                                onChange={(checked) =>
                                    setDigest('include_sales_summary', checked)
                                }
                            />
                        </div>
                    </div>
                </SectionPanel>

                <SectionPanel
                    title="Sales Follow-Up Reminders"
                    description="Send reminders when leads remain in a sales status past the configured day threshold."
                >
                    <div className="space-y-4">
                        {form.data.rules.map((rule, ruleIndex) => (
                            <div
                                key={rule.lead_status}
                                className="rounded-lg border border-border bg-muted/40 p-5"
                            >
                                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <label className="flex items-center gap-3 text-base font-black">
                                            <input
                                                type="checkbox"
                                                checked={rule.is_enabled}
                                                onChange={(event) =>
                                                    setRule(ruleIndex, {
                                                        ...rule,
                                                        is_enabled:
                                                            event.target
                                                                .checked,
                                                    })
                                                }
                                            />
                                            {rule.lead_status_label}
                                        </label>
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            Reminder based on days in this lead
                                            status.
                                        </p>
                                    </div>
                                    <div className="w-full lg:w-56">
                                        <Field
                                            label="Days In Status"
                                            error={
                                                errors[
                                                    `rules.${ruleIndex}.days_after_status`
                                                ]
                                            }
                                        >
                                            <input
                                                type="number"
                                                min="1"
                                                max="365"
                                                className="field"
                                                value={
                                                    rule.days_after_status ?? ''
                                                }
                                                onChange={(event) =>
                                                    setRule(ruleIndex, {
                                                        ...rule,
                                                        days_after_status:
                                                            event.target.value,
                                                    })
                                                }
                                            />
                                        </Field>
                                    </div>
                                </div>
                                <div className="mt-4">
                                    <div className="mb-3 flex items-center justify-between gap-3">
                                        <p className="text-sm font-black">
                                            Recipients
                                        </p>
                                        <SecondaryButton
                                            type="button"
                                            className="h-9 px-3"
                                            onClick={() =>
                                                addRecipient(ruleIndex)
                                            }
                                        >
                                            <Plus size={16} /> Add
                                        </SecondaryButton>
                                    </div>
                                    <div className="space-y-3">
                                        {rule.recipients.map(
                                            (email, emailIndex) => (
                                                <div
                                                    key={emailIndex}
                                                    className="flex gap-2"
                                                >
                                                    <input
                                                        type="email"
                                                        className="field"
                                                        value={email}
                                                        placeholder="sales@example.com"
                                                        onChange={(event) =>
                                                            setRecipient(
                                                                ruleIndex,
                                                                emailIndex,
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                    />
                                                    <SecondaryButton
                                                        type="button"
                                                        className="h-11 px-3"
                                                        onClick={() =>
                                                            removeRecipient(
                                                                ruleIndex,
                                                                emailIndex,
                                                            )
                                                        }
                                                    >
                                                        Remove
                                                    </SecondaryButton>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                    {errors[`rules.${ruleIndex}.recipients`] ? (
                                        <p className="mt-2 text-xs font-semibold text-destructive">
                                            {
                                                errors[
                                                    `rules.${ruleIndex}.recipients`
                                                ]
                                            }
                                        </p>
                                    ) : null}
                                </div>
                            </div>
                        ))}
                    </div>
                </SectionPanel>

                <div className="flex justify-end">
                    <PrimaryButton type="submit" disabled={form.processing}>
                        <Save size={17} /> Save Notifications
                    </PrimaryButton>
                </div>
            </form>
        </AppLayout>
    );
}

function CheckboxRow({
    label,
    checked,
    onChange,
}: {
    label: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
}) {
    return (
        <label className="flex items-center gap-3 rounded-md border border-border bg-card px-4 py-3 text-sm font-bold">
            <input
                type="checkbox"
                checked={checked}
                onChange={(event) => onChange(event.target.checked)}
            />
            {label}
        </label>
    );
}
