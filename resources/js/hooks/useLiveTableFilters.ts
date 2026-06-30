import { router } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

export type LiveTableFilters = Record<string, string | undefined>;

function cleanFilters(filters: LiveTableFilters): Record<string, string> {
    return Object.fromEntries(
        Object.entries(filters).filter(
            ([key, value]) => key !== 'page' && value !== undefined && value !== '',
        ),
    ) as Record<string, string>;
}

export function useLiveTableFilters(
    route: string,
    filters: LiveTableFilters,
    debounceMs = 250,
) {
    const normalizedFilters = useMemo(() => cleanFilters(filters), [filters]);
    const [draftValues, setDraftValues] = useState<LiveTableFilters>({});
    const timer = useRef<number | null>(null);
    const values = useMemo(
        () => ({ ...normalizedFilters, ...draftValues }),
        [draftValues, normalizedFilters],
    );

    useEffect(
        () => () => {
            if (timer.current) {
                window.clearTimeout(timer.current);
            }
        },
        [],
    );

    const visit = useCallback(
        (next: LiveTableFilters, debounce: boolean) => {
            if (timer.current) {
                window.clearTimeout(timer.current);
            }

            const run = () => {
                router.get(route, cleanFilters(next), {
                    preserveScroll: true,
                    preserveState: true,
                    replace: true,
                });
            };

            if (debounce) {
                timer.current = window.setTimeout(run, debounceMs);
                return;
            }

            run();
        },
        [debounceMs, route],
    );

    const setFilter = useCallback(
        (key: string, value: string, options: { debounce?: boolean } = {}) => {
            const next = { ...values, [key]: value };
            setDraftValues((current) => ({
                ...current,
                [key]: value,
            }));
            visit(next, options.debounce ?? false);
        },
        [values, visit],
    );

    const clearFilters = useCallback(() => {
        setDraftValues({});
        visit({}, false);
    }, [visit]);

    return {
        clearFilters,
        setFilter,
        values,
    };
}
