import { Link } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    LucideIcon,
    MoreHorizontal,
    Search,
} from 'lucide-react';
import { MouseEventHandler, ReactNode } from 'react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card } from '@/Components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Input } from '@/Components/ui/input';
import {
    Pagination as ShadPagination,
    PaginationContent,
    PaginationItem,
} from '@/Components/ui/pagination';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/Components/ui/sheet';
import {
    Table as ShadTable,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { MotionItem } from '@/Components/Bidscape/Motion';
import { money, percent } from '@/lib/format';
import { cn } from '@/lib/utils';

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}

export function PrimaryButton({
    children,
    href,
    type = 'button',
    className,
    onClick,
    disabled = false,
}: {
    children: ReactNode;
    href?: string;
    type?: 'button' | 'submit';
    className?: string;
    onClick?: MouseEventHandler<HTMLButtonElement>;
    disabled?: boolean;
}) {
    const classes = cn('h-11 gap-2 px-5 font-semibold', className);

    if (href) {
        return (
            <Button asChild className={classes}>
                <Link href={href}>{children}</Link>
            </Button>
        );
    }

    return (
        <Button
            type={type}
            className={classes}
            onClick={onClick}
            disabled={disabled}
        >
            {children}
        </Button>
    );
}

export function SecondaryButton({
    children,
    href,
    type = 'button',
    className,
    onClick,
    disabled = false,
}: {
    children: ReactNode;
    href?: string;
    type?: 'button' | 'submit';
    className?: string;
    onClick?: MouseEventHandler<HTMLButtonElement>;
    disabled?: boolean;
}) {
    const classes = cn('h-11 gap-2 px-5 font-semibold', className);

    if (href) {
        return (
            <Button asChild variant="outline" className={classes}>
                <Link href={href}>{children}</Link>
            </Button>
        );
    }

    return (
        <Button
            type={type}
            variant="outline"
            className={classes}
            onClick={onClick}
            disabled={disabled}
        >
            {children}
        </Button>
    );
}

export function Panel({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    return (
        <Card
            data-reveal-item
            className={cn(
                'rounded-lg border-border bg-card shadow-[0_14px_36px_rgba(15,23,42,0.05)] dark:shadow-none',
                className,
            )}
        >
            {children}
        </Card>
    );
}

export function MetricGrid({ children }: { children: ReactNode }) {
    return (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {children}
        </div>
    );
}

export function KpiCard({
    label,
    value,
    trend,
    icon: Icon,
    moneyValue,
    percentValue,
}: {
    label: string;
    value: number | string;
    trend?: string;
    icon?: LucideIcon;
    moneyValue?: boolean;
    percentValue?: boolean;
}) {
    const display = moneyValue
        ? money(Number(value))
        : percentValue
          ? percent(Number(value))
          : value;

    return (
        <MotionItem>
            <Panel className="flex min-h-[140px] p-5">
                <div className="flex min-w-0 flex-1 items-start gap-4">
                    {Icon ? (
                        <div className="flex size-12 shrink-0 items-center justify-center rounded-lg bg-secondary text-primary">
                            <Icon size={22} strokeWidth={2.1} />
                        </div>
                    ) : null}
                    <div className="flex min-w-0 flex-1 flex-col">
                        <p className="text-sm font-semibold text-muted-foreground">
                            {label}
                        </p>
                        <p className="mt-2 min-h-10 break-words text-3xl font-bold tracking-normal text-foreground">
                            {display}
                        </p>
                        {trend ? (
                            <p className="mt-auto pt-3 text-sm font-semibold text-primary">
                                {trend}
                            </p>
                        ) : null}
                    </div>
                </div>
            </Panel>
        </MotionItem>
    );
}

const toneClasses: Record<string, string> = {
    neutral:
        'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200',
    blue: 'border-sky-200 bg-sky-100 text-sky-800 dark:border-sky-900 dark:bg-sky-950/60 dark:text-sky-200',
    amber: 'border-amber-200 bg-amber-100 text-amber-800 dark:border-amber-900 dark:bg-amber-950/60 dark:text-amber-200',
    indigo: 'border-indigo-200 bg-indigo-100 text-indigo-800 dark:border-indigo-900 dark:bg-indigo-950/60 dark:text-indigo-200',
    green: 'border-emerald-200 bg-emerald-100 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-200',
    red: 'border-red-200 bg-red-100 text-red-800 dark:border-red-900 dark:bg-red-950/60 dark:text-red-200',
};

export function StatusPill({
    status,
    tone,
}: {
    status: string;
    tone?: string;
}) {
    const key = status.toLowerCase().replaceAll(' ', '_');
    const destructive = ['lost', 'declined', 'closed'].includes(key);
    const muted = ['draft', 'expired', 'archived'].includes(key);
    const positive = ['approved', 'signed', 'sold', 'won', 'paid'].includes(key);
    const toneClass = tone ? toneClasses[tone] : null;

    return (
        <Badge
            variant={
                toneClass
                    ? 'outline'
                    : destructive
                      ? 'destructive'
                      : muted
                        ? 'outline'
                        : 'secondary'
            }
            className={cn(
                'rounded-md font-bold',
                toneClass,
                !toneClass && positive && 'bg-secondary text-primary',
            )}
        >
            {status}
        </Badge>
    );
}

export function Drawer({
    open,
    title,
    subtitle,
    children,
    footer,
    onClose,
}: {
    open: boolean;
    title: string;
    subtitle?: string;
    children: ReactNode;
    footer?: ReactNode;
    onClose: () => void;
}) {
    return (
        <Sheet open={open} onOpenChange={(value) => !value && onClose()}>
            <SheetContent className="gap-0 p-0">
                <SheetHeader className="border-b border-border px-6 py-5">
                    <SheetTitle className="text-2xl font-black">
                        {title}
                    </SheetTitle>
                    {subtitle ? (
                        <SheetDescription>{subtitle}</SheetDescription>
                    ) : null}
                </SheetHeader>
                <div className="flex-1 overflow-y-auto px-6 py-5">
                    {children}
                </div>
                {footer ? (
                    <SheetFooter className="border-t border-border px-6 py-4">
                        {footer}
                    </SheetFooter>
                ) : null}
            </SheetContent>
        </Sheet>
    );
}

export function SearchInput({
    onChange,
    value,
    placeholder,
}: {
    onChange?: (value: string) => void;
    value?: string;
    placeholder: string;
}) {
    return (
        <div className="relative w-full md:min-w-[420px]">
            <Search
                aria-hidden="true"
                className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                name="search"
                value={value ?? ''}
                onChange={(event) => onChange?.(event.target.value)}
                placeholder={placeholder}
                className="h-11 pl-10"
            />
        </div>
    );
}

export function Table({
    headings,
    children,
}: {
    headings: string[];
    children: ReactNode;
}) {
    return (
        <ShadTable>
            <TableHeader>
                <TableRow className="bg-muted/50 text-xs uppercase text-muted-foreground hover:bg-muted/50">
                    {headings.map((heading) => (
                        <TableHead key={heading} className="px-6 py-4 font-bold">
                            {heading}
                        </TableHead>
                    ))}
                    <TableHead className="px-6 py-4 text-right font-bold">
                        Actions
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody className="divide-y divide-border">{children}</TableBody>
        </ShadTable>
    );
}

export function RowMenu() {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" aria-label="Open row actions">
                    <MoreHorizontal />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuGroup>
                    <DropdownMenuItem disabled>No quick actions</DropdownMenuItem>
                </DropdownMenuGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

export function Pagination<T>({ page }: { page: Paginated<T> }) {
    if (!page.links?.length) {
        return null;
    }

    const previous = page.links[0];
    const next = page.links[page.links.length - 1];
    const pages = page.links.slice(1, -1);

    return (
        <div className="flex items-center justify-between border-t border-border px-5 py-4 text-sm text-muted-foreground">
            <p>
                Showing {page.from ?? 0} to {page.to ?? 0} of {page.total}
            </p>
            <ShadPagination className="mx-0 w-auto">
                <PaginationContent>
                    <PaginationItem>
                        <PageLink link={previous} icon={<ChevronLeft />} />
                    </PaginationItem>
                    {pages.map((link) => (
                        <PaginationItem key={link.label}>
                            <PageLink link={link} />
                        </PaginationItem>
                    ))}
                    <PaginationItem>
                        <PageLink link={next} icon={<ChevronRight />} />
                    </PaginationItem>
                </PaginationContent>
            </ShadPagination>
        </div>
    );
}

function PageLink({
    link,
    icon,
}: {
    link: PaginationLink;
    icon?: ReactNode;
}) {
    const content =
        icon ??
        link.label
            .replace('&laquo; Previous', 'Previous')
            .replace('Next &raquo;', 'Next');

    const classes = cn(
        'inline-flex size-9 items-center justify-center rounded-lg border border-border font-semibold transition',
        link.active
            ? 'bg-secondary text-primary'
            : 'bg-card text-foreground hover:bg-muted',
        !link.url && 'pointer-events-none opacity-45',
    );

    if (!link.url) {
        return <span className={classes}>{content}</span>;
    }

    return (
        <Link preserveScroll href={link.url} className={classes}>
            {content}
        </Link>
    );
}

export { TableCell, TableRow };
