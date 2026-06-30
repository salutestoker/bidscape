import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { ReactNode } from 'react';
import { SecondaryButton } from '@/Components/Bidscape/UI';

export function SettingsBackLink() {
    return (
        <SecondaryButton href="/settings" className="h-10 px-3">
            <ArrowLeft size={16} /> Settings
        </SecondaryButton>
    );
}

export function Field({
    label,
    error,
    children,
    hint,
}: {
    label: string;
    error?: string;
    children: ReactNode;
    hint?: string;
}) {
    return (
        <label className="block text-sm font-bold text-foreground">
            <span>{label}</span>
            <div className="mt-2">{children}</div>
            {hint ? (
                <p className="mt-2 text-xs font-semibold text-muted-foreground">
                    {hint}
                </p>
            ) : null}
            {error ? (
                <p className="mt-2 text-xs font-semibold text-destructive">
                    {error}
                </p>
            ) : null}
        </label>
    );
}

export function SectionPanel({
    title,
    description,
    children,
}: {
    title: string;
    description?: string;
    children: ReactNode;
}) {
    return (
        <section className="rounded-lg border border-border bg-card p-6 shadow-[0_14px_36px_rgba(15,23,42,0.05)]">
            <div className="mb-6">
                <h2 className="text-xl font-black">{title}</h2>
                {description ? (
                    <p className="mt-2 text-sm font-medium text-muted-foreground">
                        {description}
                    </p>
                ) : null}
            </div>
            {children}
        </section>
    );
}

export function InlineLink({
    href,
    children,
}: {
    href: string;
    children: ReactNode;
}) {
    return (
        <Link href={href} className="font-black text-primary">
            {children}
        </Link>
    );
}

export function centsToDollars(cents: number | string | null | undefined): string {
    return (Number(cents ?? 0) / 100).toFixed(2);
}

export function dollarsToCents(value: string | number): number {
    const normalized = Number.parseFloat(String(value || '0'));

    return Number.isFinite(normalized) ? Math.round(normalized * 100) : 0;
}

export function basisPointsToPercent(
    basisPoints: number | string | null | undefined,
): string {
    return (Number(basisPoints ?? 0) / 100).toString();
}

export function percentToBasisPoints(value: string | number): number {
    const normalized = Number.parseFloat(String(value || '0'));

    return Number.isFinite(normalized) ? Math.round(normalized * 100) : 0;
}
