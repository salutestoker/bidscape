import ApplicationLogo from '@/Components/ApplicationLogo';
import { GlobalEffects } from '@/Components/Bidscape/GlobalEffects';
import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="flex min-h-screen flex-col items-center bg-background pt-6 text-foreground sm:justify-center sm:pt-0">
            <GlobalEffects />
            <div>
                <Link href="/">
                    <span className="block rounded-lg bg-white p-2 shadow-sm">
                        <ApplicationLogo className="w-[220px]" />
                    </span>
                </Link>
            </div>

            <div className="mt-6 w-full overflow-hidden rounded-lg border border-border bg-card px-6 py-4 shadow-md sm:max-w-md">
                {children}
            </div>
        </div>
    );
}
