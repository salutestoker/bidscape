import { Button } from '@/Components/ui/button';
import { ToastType, useToastState } from '@/Context/ToastContext';
import { cn } from '@/lib/utils';
import { CheckCircle2, Info, TriangleAlert, X, XCircle } from 'lucide-react';
import { useEffect } from 'react';

const iconByType = {
    success: CheckCircle2,
    error: XCircle,
    info: Info,
    warning: TriangleAlert,
};

const labelByType = {
    success: 'Success',
    error: 'Error',
    info: 'Notice',
    warning: 'Warning',
};

const toneByType: Record<ToastType, string> = {
    success:
        'border-primary/30 ring-primary/10 [&_.toast-icon]:bg-primary/10 [&_.toast-icon]:text-primary [&_.toast-label]:text-primary',
    error: 'border-destructive/35 ring-destructive/10 [&_.toast-icon]:bg-destructive/10 [&_.toast-icon]:text-destructive [&_.toast-label]:text-destructive',
    info: 'border-sky-400/35 ring-sky-400/10 [&_.toast-icon]:bg-sky-400/10 [&_.toast-icon]:text-sky-700 dark:[&_.toast-icon]:text-sky-300 [&_.toast-label]:text-sky-700 dark:[&_.toast-label]:text-sky-300',
    warning:
        'border-amber-400/40 ring-amber-400/10 [&_.toast-icon]:bg-amber-400/10 [&_.toast-icon]:text-amber-700 dark:[&_.toast-icon]:text-amber-300 [&_.toast-label]:text-amber-700 dark:[&_.toast-label]:text-amber-300',
};

export function Toast() {
    const { dismissToast, toastState } = useToastState();

    useEffect(() => {
        if (!toastState || toastState.persistent) {
            return;
        }

        const timer = window.setTimeout(dismissToast, 5000);

        return () => window.clearTimeout(timer);
    }, [dismissToast, toastState]);

    if (!toastState) {
        return null;
    }

    const Icon = iconByType[toastState.type];

    return (
        <div
            aria-live="polite"
            aria-atomic="true"
            className="animate-in fade-in-0 slide-in-from-bottom-3 fixed bottom-5 right-5 z-50 w-[calc(100vw-2.5rem)] max-w-md duration-300"
        >
            <div
                className={cn(
                    'rounded-lg border bg-card/95 px-4 py-3 text-card-foreground shadow-2xl shadow-foreground/10 ring-1 backdrop-blur',
                    toneByType[toastState.type],
                )}
            >
                <div className="flex items-start gap-3">
                    <div className="toast-icon mt-0.5 rounded-full p-1">
                        <Icon className="size-5" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <div className="toast-label text-xs font-bold uppercase tracking-[0.18em]">
                            {labelByType[toastState.type]}
                        </div>
                        <p className="mt-1 text-sm font-medium leading-5">
                            {toastState.message}
                        </p>
                        {toastState.actions?.length ? (
                            <div className="mt-3 flex flex-wrap gap-2">
                                {toastState.actions.map((action) => (
                                    <Button
                                        key={action.label}
                                        type="button"
                                        variant={action.variant ?? 'outline'}
                                        size="sm"
                                        disabled={action.disabled}
                                        onClick={action.onClick}
                                    >
                                        {action.label}
                                    </Button>
                                ))}
                            </div>
                        ) : null}
                    </div>
                    {!toastState.persistent ? (
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            className="-mr-1 -mt-1"
                            onClick={() => dismissToast()}
                        >
                            <X />
                            <span className="sr-only">
                                Dismiss notification
                            </span>
                        </Button>
                    ) : null}
                </div>
            </div>
        </div>
    );
}
