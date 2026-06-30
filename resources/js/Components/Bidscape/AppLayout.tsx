import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    BriefcaseBusiness,
    Box,
    Calculator,
    ChartNoAxesColumn,
    ChevronDown,
    ChevronsLeft,
    ChevronsRight,
    ClipboardList,
    FileText,
    Home,
    Menu,
    Settings,
    Sun,
    Users,
    X,
} from 'lucide-react';
import { motion, useReducedMotion } from 'motion/react';
import { CSSProperties, ReactNode, useState } from 'react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { GlobalEffects } from '@/Components/Bidscape/GlobalEffects';
import { AnimatedPage, StaggeredReveal } from '@/Components/Bidscape/Motion';
import ThemeToggle from '@/Components/ThemeToggle';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/Components/ui/tooltip';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';

const navItems = [
    { label: 'Dashboard', href: '/dashboard', icon: Home },
    { label: 'Leads', href: '/leads', icon: ClipboardList },
    { label: 'Estimates', href: '/estimates', icon: FileText },
    { label: 'Jobs', href: '/jobs', icon: BriefcaseBusiness },
    { label: 'Customers', href: '/customers', icon: Users },
    { label: 'Assemblies', href: '/assemblies', icon: Calculator },
    { label: 'Materials', href: '/materials', icon: Box },
    { label: 'Reports', href: '/reports', icon: ChartNoAxesColumn },
    { label: 'Settings', href: '/settings', icon: Settings },
];

export default function AppLayout({
    title,
    subtitle,
    action,
    children,
}: {
    title: string;
    subtitle?: string;
    action?: ReactNode;
    children: ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const [collapsed, setCollapsed] = useState(
        () =>
            typeof window !== 'undefined' &&
            window.localStorage.getItem('bidscape.sidebarCollapsed') === 'true',
    );
    const { auth } = usePage<PageProps>().props;
    const { url } = usePage<PageProps>();
    const path = url.split('?')[0];
    const brandStyle = {
        ...(auth.company?.brand_primary_color
            ? {
                  '--primary': auth.company.brand_primary_color,
                  '--ring': auth.company.brand_primary_color,
                  '--sidebar-primary': auth.company.brand_primary_color,
                  '--sidebar-accent-foreground':
                      auth.company.brand_primary_color,
              }
            : {}),
    } as CSSProperties;

    function toggleCollapsed() {
        setCollapsed((current) => {
            const next = !current;
            window.localStorage.setItem(
                'bidscape.sidebarCollapsed',
                String(next),
            );

            return next;
        });
    }

    return (
        <div
            className="min-h-screen bg-background text-foreground"
            style={brandStyle}
        >
            <GlobalEffects />
            <Sidebar
                open={open}
                collapsed={collapsed}
                onClose={() => setOpen(false)}
                onToggleCollapsed={toggleCollapsed}
            />
            <div
                className={cn(
                    'min-h-screen transition-[padding] duration-200',
                    collapsed ? 'lg:pl-[84px]' : 'lg:pl-[276px]',
                )}
            >
                <header className="sticky top-0 z-20 border-b border-border/80 bg-background/90 px-5 py-5 backdrop-blur md:px-9">
                    <div className="flex items-start justify-between gap-5">
                        <div className="flex min-w-0 items-start gap-4">
                            <Button
                                variant="outline"
                                size="icon-lg"
                                className="mt-1 lg:hidden"
                                onClick={() => setOpen(true)}
                                aria-label="Open navigation"
                            >
                                <Menu />
                            </Button>
                            <div className="min-w-0">
                                <h1 className="text-3xl font-bold tracking-normal md:text-[40px]">
                                    {title}
                                </h1>
                                {subtitle ? (
                                    <p className="mt-2 text-base font-medium text-muted-foreground">
                                        {subtitle}
                                    </p>
                                ) : null}
                            </div>
                        </div>
                        <div className="flex shrink-0 items-center gap-5">
                            <div className="hidden items-center gap-2 md:flex">
                                <Sun
                                    size={24}
                                    className="text-[#f6b318]"
                                    fill="#f6b318"
                                />
                                <div>
                                    <p className="text-lg font-bold">87°</p>
                                    <p className="text-xs font-medium text-muted-foreground">
                                        Mesa, Arizona
                                    </p>
                                </div>
                            </div>
                            <ThemeToggle className="hidden md:flex" />
                            <Button
                                variant="outline"
                                size="icon-lg"
                                className="relative hidden md:inline-flex"
                                aria-label="Notifications"
                            >
                                <Bell />
                                <Badge className="absolute -right-1 -top-1 flex size-5 items-center justify-center rounded-full p-0 text-[11px] font-bold">
                                    3
                                </Badge>
                            </Button>
                            {action}
                        </div>
                    </div>
                </header>
                <main className="px-5 py-6 md:px-9 md:py-8">
                    <AnimatedPage key={path}>
                        <StaggeredReveal>{children}</StaggeredReveal>
                    </AnimatedPage>
                </main>
            </div>
            <div className="fixed bottom-5 left-5 z-40 hidden rounded-full border border-border bg-card px-4 py-2 text-sm font-semibold text-foreground shadow-lg md:block lg:hidden">
                {auth.company?.name ?? 'Bidscape'}
            </div>
        </div>
    );
}

function Sidebar({
    open,
    collapsed,
    onClose,
    onToggleCollapsed,
}: {
    open: boolean;
    collapsed: boolean;
    onClose: () => void;
    onToggleCollapsed: () => void;
}) {
    const { auth } = usePage<PageProps>().props;
    const path = window.location.pathname;
    const shouldReduceMotion = useReducedMotion();

    return (
        <>
            <aside
                className={cn(
                    'fixed inset-y-0 left-0 z-40 flex w-[276px] flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground transition-[transform,width] duration-200 lg:translate-x-0',
                    open ? 'translate-x-0' : '-translate-x-full',
                    collapsed && 'lg:w-[84px]',
                )}
            >
                <div
                    className={cn(
                        'flex items-center justify-between px-8 py-8',
                        collapsed && 'lg:flex-col lg:gap-3 lg:px-3',
                    )}
                >
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Link
                                href="/dashboard"
                                aria-label="Bidscape dashboard"
                            >
                                {auth.company?.logo_url ? (
                                    <img
                                        src={auth.company.logo_url}
                                        alt={auth.company.name}
                                        className={cn(
                                            'h-12 w-[174px] object-contain object-left transition-[width]',
                                            collapsed && 'lg:w-12',
                                        )}
                                    />
                                ) : (
                                    <ApplicationLogo
                                        className={cn(
                                            'h-auto w-[174px] object-contain transition-[width]',
                                            collapsed && 'lg:w-12',
                                        )}
                                    />
                                )}
                            </Link>
                        </TooltipTrigger>
                        {collapsed ? (
                            <TooltipContent side="right">
                                Bidscape
                            </TooltipContent>
                        ) : null}
                    </Tooltip>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="hidden lg:inline-flex"
                        onClick={onToggleCollapsed}
                        aria-label={
                            collapsed
                                ? 'Expand navigation'
                                : 'Collapse navigation'
                        }
                    >
                        {collapsed ? <ChevronsRight /> : <ChevronsLeft />}
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="lg:hidden"
                        onClick={onClose}
                        aria-label="Close navigation"
                    >
                        <X />
                    </Button>
                </div>
                <nav
                    className={cn(
                        'flex flex-1 flex-col gap-2 px-5 py-3',
                        collapsed && 'lg:px-3',
                    )}
                >
                    {navItems.map(({ label, href, icon: Icon }) => {
                        const active =
                            href === '/dashboard'
                                ? path === href
                                : path.startsWith(href);

                        return (
                            <Tooltip key={href}>
                                <TooltipTrigger asChild>
                                    <Link
                                        href={href}
                                        className={cn(
                                            'flex h-12 items-center gap-4 rounded-md px-4 text-[15px] font-bold transition',
                                            active
                                                ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                                                : 'text-sidebar-foreground hover:bg-sidebar-accent/60',
                                            collapsed &&
                                                'lg:justify-center lg:px-0',
                                        )}
                                    >
                                        <Icon size={21} strokeWidth={2.1} />
                                        <span
                                            className={cn(
                                                'truncate',
                                                collapsed && 'lg:hidden',
                                            )}
                                        >
                                            {label}
                                        </span>
                                    </Link>
                                </TooltipTrigger>
                                {collapsed ? (
                                    <TooltipContent side="right">
                                        {label}
                                    </TooltipContent>
                                ) : null}
                            </Tooltip>
                        );
                    })}
                </nav>
                <div
                    className={cn(
                        'border-t border-sidebar-border p-5',
                        collapsed && 'lg:px-3',
                    )}
                >
                    <ThemeToggle className="mb-4 md:hidden" />
                    <div
                        className={cn(
                            'flex items-center gap-3',
                            collapsed && 'lg:justify-center',
                        )}
                    >
                        <Avatar className="size-12">
                            <AvatarFallback>NM</AvatarFallback>
                        </Avatar>
                        <div
                            className={cn(
                                'min-w-0 flex-1',
                                collapsed && 'lg:hidden',
                            )}
                        >
                            <p className="truncate text-sm font-bold">
                                {auth.user?.name ?? 'Nick Martinez'}
                            </p>
                            <p className="truncate text-xs font-medium text-muted-foreground">
                                {auth.company?.name ??
                                'Desert Ridge Landscaping'}
                            </p>
                        </div>
                        <ChevronDown
                            size={17}
                            className={cn(collapsed && 'lg:hidden')}
                        />
                    </div>
                </div>
            </aside>
            {open ? (
                <motion.button
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    transition={{ duration: shouldReduceMotion ? 0 : 0.16 }}
                    className="fixed inset-0 z-30 bg-foreground/25 lg:hidden"
                    aria-label="Close navigation overlay"
                    onClick={onClose}
                />
            ) : null}
        </>
    );
}
