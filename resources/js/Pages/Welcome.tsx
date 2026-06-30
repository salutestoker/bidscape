import ApplicationLogo from '@/Components/ApplicationLogo';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/Components/ui/card';
import { Separator } from '@/Components/ui/separator';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BadgeCheck,
    BriefcaseBusiness,
    Calculator,
    CheckCircle2,
    ClipboardCheck,
    FileSignature,
    FileText,
    HandCoins,
    Inbox,
    Layers3,
    LockKeyhole,
    MailCheck,
    ShieldCheck,
    type LucideIcon,
} from 'lucide-react';

const workflowSteps: Array<{
    title: string;
    copy: string;
    icon: LucideIcon;
}> = [
    {
        title: 'Lead intake',
        copy: 'Capture homeowner details, source, scope notes, and site context in one company-scoped sales record.',
        icon: Inbox,
    },
    {
        title: 'Estimate building',
        copy: 'Use assemblies, materials, labor, overhead, and margin controls without floating-point pricing drift.',
        icon: Calculator,
    },
    {
        title: 'Branded delivery',
        copy: 'Send polished estimate PDFs and emails that keep every client-facing detail tied to the bid.',
        icon: MailCheck,
    },
    {
        title: 'Public approval',
        copy: 'Give customers a focused review page where they can approve, type a signature, or decline with context.',
        icon: FileSignature,
    },
    {
        title: 'Deposit recording',
        copy: 'Record the deposit step after approval so sold work is ready for the office handoff.',
        icon: HandCoins,
    },
    {
        title: 'Sold-project register',
        copy: 'Keep the sales team clear on what is open, sent, approved, signed, and sold.',
        icon: BriefcaseBusiness,
    },
    {
        title: 'Immutable packet handoff',
        copy: 'Package the approved estimate, customer details, attachments, and signature into a locked job packet.',
        icon: LockKeyhole,
    },
];

const estimatePoints = [
    'Scope rows, assemblies, and unit pricing stay visible while the team builds.',
    'Margin and selling price update beside the estimate instead of in a separate spreadsheet.',
    'Branded proposal output flows from the same approved estimate data.',
];

const approvalPoints: Array<{
    title: string;
    copy: string;
    icon: LucideIcon;
}> = [
    {
        title: 'Review the bid',
        copy: 'Customers see scope, terms, totals, and company details without needing an account.',
        icon: FileText,
    },
    {
        title: 'Approve or decline',
        copy: 'Typed signatures and decline reasons come back into the sales record for a clean next step.',
        icon: ClipboardCheck,
    },
    {
        title: 'Prepare the packet',
        copy: 'Approved work can move into deposit recording and a packet handoff without production tracking.',
        icon: ShieldCheck,
    },
];

const proofStats = [
    { label: 'Open leads', value: '18' },
    { label: 'Active estimates', value: '7' },
    { label: 'Avg margin', value: '38.2%' },
];

export default function Welcome() {
    return (
        <>
            <Head>
                <title>Contractor Sales Workflow</title>
                <meta
                    name="description"
                    content="Bidscape helps contractors turn leads into approved estimates, deposits, and immutable job packet handoffs."
                />
            </Head>

            <main className="bg-background text-foreground min-h-screen">
                <header className="mx-auto flex w-full max-w-7xl items-center justify-between px-5 py-4 sm:px-6 lg:px-8">
                    <Link href="/" aria-label="Bidscape home">
                        <span className="bg-card block rounded-lg p-1.5 shadow-sm">
                            <ApplicationLogo className="w-[150px] sm:w-[180px]" />
                        </span>
                    </Link>

                    <nav
                        aria-label="Landing page"
                        className="text-muted-foreground hidden items-center gap-6 text-sm font-semibold md:flex"
                    >
                        <a href="#workflow" className="hover:text-foreground">
                            Workflow
                        </a>
                        <a href="#estimates" className="hover:text-foreground">
                            Estimates
                        </a>
                        <a href="#approval" className="hover:text-foreground">
                            Approval
                        </a>
                    </nav>

                    <div className="flex items-center gap-2">
                        <Button asChild variant="ghost">
                            <Link href={route('login')}>Log in</Link>
                        </Button>
                        <Button asChild className="hidden sm:inline-flex">
                            <Link href={route('register')}>
                                Sign up
                                <ArrowRight data-icon="inline-end" />
                            </Link>
                        </Button>
                    </div>
                </header>

                <section className="relative overflow-hidden">
                    <div className="absolute inset-0">
                        <img
                            src="/images/landing/dashboard.jpg"
                            alt=""
                            aria-hidden="true"
                            className="size-full object-cover object-left-top"
                        />
                        <div className="bg-background/96 sm:bg-background/88 lg:bg-background/82 absolute inset-0" />
                    </div>

                    <div className="relative mx-auto flex min-h-[72svh] max-w-7xl flex-col justify-center px-5 py-16 sm:px-6 lg:px-8">
                        <div className="flex max-w-3xl flex-col items-start gap-6">
                            <Badge variant="secondary">
                                Contractor sales workflow
                            </Badge>
                            <h1 className="text-foreground text-5xl leading-[0.95] font-black tracking-normal sm:text-6xl lg:text-7xl">
                                Bidscape
                            </h1>
                            <p className="text-muted-foreground max-w-2xl text-xl leading-8 font-semibold sm:text-2xl sm:leading-9">
                                Turn leads into accurate estimates, customer
                                approvals, deposits, and locked job packets
                                without dragging sales into production
                                operations.
                            </p>
                            <div className="flex flex-col gap-3 sm:flex-row">
                                <Button asChild size="lg">
                                    <Link href={route('register')}>
                                        Sign up
                                        <ArrowRight data-icon="inline-end" />
                                    </Link>
                                </Button>
                                <Button asChild size="lg" variant="secondary">
                                    <Link href={route('login')}>Log in</Link>
                                </Button>
                            </div>
                        </div>

                        <div className="mt-12 grid max-w-3xl grid-cols-3 gap-3">
                            {proofStats.map((stat) => (
                                <div
                                    key={stat.label}
                                    className="border-border bg-card/90 rounded-lg border px-3 py-3 shadow-[0_14px_36px_rgba(15,23,42,0.12)] sm:px-4"
                                >
                                    <p className="text-muted-foreground text-xs font-bold sm:text-sm">
                                        {stat.label}
                                    </p>
                                    <p className="text-foreground mt-1 text-2xl font-black sm:text-3xl">
                                        {stat.value}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                <section
                    id="workflow"
                    className="mx-auto flex max-w-7xl flex-col gap-8 px-5 py-16 sm:px-6 lg:px-8"
                >
                    <div className="grid gap-6 lg:grid-cols-[0.8fr_1.2fr] lg:items-end">
                        <div>
                            <Badge variant="outline">Lead to packet</Badge>
                            <h2 className="mt-4 text-3xl font-black tracking-normal sm:text-4xl">
                                One sales workflow for contractors.
                            </h2>
                        </div>
                        <p className="text-muted-foreground text-lg leading-8">
                            Bidscape keeps the V1 surface focused on selling:
                            convert the lead, build the estimate, send it,
                            capture the customer response, record the deposit,
                            and hand off a complete packet.
                        </p>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        {workflowSteps.map((step) => {
                            const Icon = step.icon;

                            return (
                                <Card key={step.title}>
                                    <CardHeader>
                                        <div className="bg-secondary text-primary flex size-11 items-center justify-center rounded-lg">
                                            <Icon />
                                        </div>
                                        <CardTitle>{step.title}</CardTitle>
                                        <CardDescription>
                                            {step.copy}
                                        </CardDescription>
                                    </CardHeader>
                                </Card>
                            );
                        })}
                    </div>
                </section>

                <section id="estimates" className="bg-muted/60 py-16">
                    <div className="mx-auto grid max-w-7xl gap-8 px-5 sm:px-6 lg:grid-cols-[0.92fr_1.08fr] lg:px-8">
                        <div className="flex flex-col justify-center gap-6">
                            <Badge variant="secondary">Estimate builder</Badge>
                            <div className="flex flex-col gap-4">
                                <h2 className="text-3xl font-black tracking-normal sm:text-4xl">
                                    Price the scope while protecting margin.
                                </h2>
                                <p className="text-muted-foreground text-lg leading-8">
                                    The estimate builder keeps sales math,
                                    assemblies, scope rows, and branded proposal
                                    output in the same workflow so the team can
                                    sell with confidence.
                                </p>
                            </div>

                            <div className="flex flex-col gap-3">
                                {estimatePoints.map((point) => (
                                    <div
                                        key={point}
                                        className="bg-card flex items-start gap-3 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm"
                                    >
                                        <CheckCircle2 className="text-primary mt-0.5 shrink-0" />
                                        <span>{point}</span>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="border-border bg-card overflow-hidden rounded-lg border shadow-[0_14px_36px_rgba(15,23,42,0.08)]">
                            <img
                                src="/images/landing/estimate-builder.jpg"
                                alt="Bidscape estimate builder showing scope rows, assemblies, and selling price"
                                className="h-full w-full object-cover"
                            />
                        </div>
                    </div>
                </section>

                <section
                    id="approval"
                    className="mx-auto grid max-w-7xl gap-8 px-5 py-16 sm:px-6 lg:grid-cols-[1fr_0.9fr] lg:px-8"
                >
                    <Card>
                        <CardHeader>
                            <Badge variant="outline" className="w-fit">
                                Public review
                            </Badge>
                            <CardTitle className="text-3xl font-black tracking-normal sm:text-4xl">
                                Customer approval without account friction.
                            </CardTitle>
                            <CardDescription className="text-base leading-7">
                                A focused estimate review page gives customers
                                the details they need to approve, sign, or
                                decline. The office gets a clear next step
                                without expanding beyond the sales handoff.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-col gap-5">
                                {approvalPoints.map((point, index) => {
                                    const Icon = point.icon;

                                    return (
                                        <div
                                            key={point.title}
                                            className="flex flex-col gap-5"
                                        >
                                            <div className="flex items-start gap-4">
                                                <div className="bg-secondary text-primary flex size-11 shrink-0 items-center justify-center rounded-lg">
                                                    <Icon />
                                                </div>
                                                <div>
                                                    <h3 className="font-black">
                                                        {point.title}
                                                    </h3>
                                                    <p className="text-muted-foreground mt-1 leading-7">
                                                        {point.copy}
                                                    </p>
                                                </div>
                                            </div>
                                            {index <
                                            approvalPoints.length - 1 ? (
                                                <Separator />
                                            ) : null}
                                        </div>
                                    );
                                })}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div className="bg-secondary text-primary flex size-12 items-center justify-center rounded-lg">
                                <BadgeCheck />
                            </div>
                            <CardTitle>Locked handoff</CardTitle>
                            <CardDescription>
                                The approved estimate becomes the source of
                                truth for the job packet.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="bg-muted/60 flex flex-col gap-4 rounded-lg p-4">
                                <div className="flex items-center justify-between gap-4">
                                    <span className="font-bold">
                                        Estimate status
                                    </span>
                                    <Badge variant="secondary">Approved</Badge>
                                </div>
                                <Separator />
                                <div className="flex items-center justify-between gap-4">
                                    <span className="font-bold">Signature</span>
                                    <span className="text-primary text-sm font-semibold">
                                        Typed and stored
                                    </span>
                                </div>
                                <Separator />
                                <div className="flex items-center justify-between gap-4">
                                    <span className="font-bold">Deposit</span>
                                    <span className="text-primary text-sm font-semibold">
                                        Ready to record
                                    </span>
                                </div>
                                <Separator />
                                <div className="flex items-center justify-between gap-4">
                                    <span className="font-bold">
                                        Job packet
                                    </span>
                                    <span className="text-primary text-sm font-semibold">
                                        Immutable handoff
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                        <CardFooter>
                            <div className="text-muted-foreground flex items-center gap-3 text-sm font-semibold">
                                <Layers3 className="text-primary" />
                                Scope, customer, terms, attachments, and signed
                                approval stay together.
                            </div>
                        </CardFooter>
                    </Card>
                </section>

                <section className="px-5 pb-16 sm:px-6 lg:px-8">
                    <div className="bg-foreground text-background mx-auto flex max-w-7xl flex-col gap-6 rounded-lg px-6 py-10 sm:px-8 lg:flex-row lg:items-center lg:justify-between">
                        <div className="max-w-3xl">
                            <p className="text-primary text-sm font-black tracking-normal uppercase">
                                Ready for contractor sales
                            </p>
                            <h2 className="mt-3 text-3xl font-black tracking-normal sm:text-4xl">
                                Start with the workflow your sales team already
                                needs.
                            </h2>
                            <p className="text-background/80 mt-4 text-lg leading-8">
                                Bidscape keeps the first version focused:
                                intake, estimate, send, approve, deposit, sold
                                register, and packet handoff.
                            </p>
                        </div>
                        <div className="flex flex-col gap-3 sm:flex-row lg:shrink-0">
                            <Button asChild size="lg">
                                <Link href={route('register')}>
                                    Sign up
                                    <ArrowRight data-icon="inline-end" />
                                </Link>
                            </Button>
                            <Button asChild size="lg" variant="secondary">
                                <Link href={route('login')}>Log in</Link>
                            </Button>
                        </div>
                    </div>
                </section>
            </main>
        </>
    );
}
