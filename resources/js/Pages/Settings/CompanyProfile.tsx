import { Head, useForm } from '@inertiajs/react';
import { RotateCcw, Save, Upload } from 'lucide-react';
import { FormEvent, useState } from 'react';
import AppLayout from '@/Components/Bidscape/AppLayout';
import { PrimaryButton, SecondaryButton } from '@/Components/Bidscape/UI';
import { Field, SectionPanel, SettingsBackLink } from './Partials';

interface CompanyProfile {
    name: string;
    industry: string;
    email: string | null;
    phone: string | null;
    website: string | null;
    address: string | null;
    city: string | null;
    state: string | null;
    postal_code: string | null;
    contractor_license_number: string | null;
    logo_url: string | null;
    brand_primary_color: string | null;
}

interface CompanyProfileForm {
    name: string;
    industry: string;
    email: string;
    phone: string;
    website: string;
    address: string;
    city: string;
    state: string;
    postal_code: string;
    contractor_license_number: string;
    brand_primary_color: string;
    logo: File | null;
}

interface Props {
    company: CompanyProfile;
}

const defaultPrimaryColor = '#07883f';

export default function CompanyProfile({ company }: Props) {
    const [preview, setPreview] = useState<string | null>(company.logo_url);
    const form = useForm<CompanyProfileForm>({
        name: company.name,
        industry: company.industry,
        email: company.email ?? '',
        phone: company.phone ?? '',
        website: company.website ?? '',
        address: company.address ?? '',
        city: company.city ?? '',
        state: company.state ?? '',
        postal_code: company.postal_code ?? '',
        contractor_license_number: company.contractor_license_number ?? '',
        brand_primary_color: company.brand_primary_color ?? '',
        logo: null,
    });
    const effectivePrimaryColor =
        form.data.brand_primary_color || defaultPrimaryColor;

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post('/settings/company-profile', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.setData('logo', null),
        });
    }

    function setLogo(file: File | null) {
        form.setData('logo', file);

        if (!file) {
            setPreview(company.logo_url);
            return;
        }

        setPreview(URL.createObjectURL(file));
    }

    return (
        <AppLayout
            title="Company Profile"
            subtitle="Manage company identity, contact information, logo, and app branding."
            action={<SettingsBackLink />}
        >
            <Head title="Company Profile" />
            <form onSubmit={submit} className="space-y-6">
                <SectionPanel
                    title="Company Information"
                    description="These fields appear on estimates, public review pages, and company-facing records."
                >
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Company Name" error={form.errors.name}>
                            <input
                                className="field"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                            />
                        </Field>
                        <Field label="Industry" error={form.errors.industry}>
                            <input
                                className="field"
                                value={form.data.industry}
                                onChange={(event) =>
                                    form.setData('industry', event.target.value)
                                }
                            />
                        </Field>
                        <Field label="Email" error={form.errors.email}>
                            <input
                                type="email"
                                className="field"
                                value={form.data.email}
                                onChange={(event) =>
                                    form.setData('email', event.target.value)
                                }
                            />
                        </Field>
                        <Field label="Phone" error={form.errors.phone}>
                            <input
                                className="field"
                                value={form.data.phone}
                                onChange={(event) =>
                                    form.setData('phone', event.target.value)
                                }
                            />
                        </Field>
                        <Field label="Website" error={form.errors.website}>
                            <input
                                className="field"
                                value={form.data.website}
                                onChange={(event) =>
                                    form.setData('website', event.target.value)
                                }
                            />
                        </Field>
                        <Field
                            label="ROC / Contractor License"
                            error={form.errors.contractor_license_number}
                        >
                            <input
                                className="field"
                                value={form.data.contractor_license_number}
                                onChange={(event) =>
                                    form.setData(
                                        'contractor_license_number',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                    </div>
                </SectionPanel>

                <SectionPanel title="Address">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Street Address" error={form.errors.address}>
                            <input
                                className="field"
                                value={form.data.address}
                                onChange={(event) =>
                                    form.setData('address', event.target.value)
                                }
                            />
                        </Field>
                        <Field label="City" error={form.errors.city}>
                            <input
                                className="field"
                                value={form.data.city}
                                onChange={(event) =>
                                    form.setData('city', event.target.value)
                                }
                            />
                        </Field>
                        <Field label="State" error={form.errors.state}>
                            <input
                                className="field"
                                value={form.data.state}
                                onChange={(event) =>
                                    form.setData('state', event.target.value)
                                }
                            />
                        </Field>
                        <Field
                            label="Postal Code"
                            error={form.errors.postal_code}
                        >
                            <input
                                className="field"
                                value={form.data.postal_code}
                                onChange={(event) =>
                                    form.setData(
                                        'postal_code',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                    </div>
                </SectionPanel>

                <SectionPanel
                    title="Branding"
                    description="Logo and color choices personalize Bidscape for this company."
                >
                    <div className="grid gap-6 lg:grid-cols-[280px_1fr]">
                        <div>
                            <div className="flex aspect-[4/3] items-center justify-center overflow-hidden rounded-lg border border-border bg-muted">
                                {preview ? (
                                    <img
                                        src={preview}
                                        alt="Company logo preview"
                                        className="h-full w-full object-contain p-4"
                                    />
                                ) : (
                                    <Upload className="text-muted-foreground" />
                                )}
                            </div>
                            <Field label="Logo" error={form.errors.logo}>
                                <input
                                    type="file"
                                    accept="image/*"
                                    className="field pt-2"
                                    onChange={(event) =>
                                        setLogo(
                                            event.target.files?.[0] ?? null,
                                        )
                                    }
                                />
                            </Field>
                        </div>

                        <div className="max-w-md">
                            <Field
                                label="Primary Action Color"
                                error={form.errors.brand_primary_color}
                            >
                                <div className="flex flex-col gap-3 sm:flex-row">
                                    <input
                                        type="color"
                                        className="h-11 w-14 shrink-0 rounded-md border border-input bg-card p-1"
                                        value={effectivePrimaryColor}
                                        onChange={(event) =>
                                            form.setData(
                                                'brand_primary_color',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <input
                                        className="field"
                                        value={form.data.brand_primary_color}
                                        placeholder={defaultPrimaryColor}
                                        onChange={(event) =>
                                            form.setData(
                                                'brand_primary_color',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <SecondaryButton
                                        type="button"
                                        className="shrink-0"
                                        disabled={!form.data.brand_primary_color}
                                        onClick={() =>
                                            form.setData(
                                                'brand_primary_color',
                                                '',
                                            )
                                        }
                                    >
                                        <RotateCcw size={16} /> Use Default
                                    </SecondaryButton>
                                </div>
                            </Field>
                        </div>
                    </div>
                </SectionPanel>

                <div className="flex justify-end">
                    <PrimaryButton type="submit" disabled={form.processing}>
                        <Save size={17} /> Save Profile
                    </PrimaryButton>
                </div>
            </form>
        </AppLayout>
    );
}
