export function money(cents: number | string | null | undefined): string {
    const value = Number(cents ?? 0) / 100;

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: value >= 1000 ? 0 : 2,
    }).format(value);
}

export function percent(basisPoints: number | string | null | undefined): string {
    return `${(Number(basisPoints ?? 0) / 100).toFixed(1).replace('.0', '')}%`;
}

export function cx(...classes: Array<string | false | null | undefined>): string {
    return classes.filter(Boolean).join(' ');
}
