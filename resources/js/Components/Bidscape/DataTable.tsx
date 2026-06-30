import { router } from '@inertiajs/react';
import {
    ColumnDef,
    SortingState,
    flexRender,
    functionalUpdate,
    getCoreRowModel,
    useReactTable,
} from '@tanstack/react-table';
import { ArrowDown, ArrowUp, ChevronsUpDown } from 'lucide-react';
import { useMemo } from 'react';
import {
    Table as ShadTable,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { cn } from '@/lib/utils';
import { Paginated } from './UI';

export type TableFilters = Record<string, string | undefined>;

export function DataTable<TData extends { id: number | string }>({
    page,
    columns,
    filters,
    route,
    onRowClick,
    rowClassName,
}: {
    page: Paginated<TData>;
    columns: ColumnDef<TData>[];
    filters: TableFilters;
    route: string;
    onRowClick?: (row: TData) => void;
    rowClassName?: (row: TData) => string;
}) {
    const data = useMemo(() => page.data, [page.data]);
    const sorting = useMemo<SortingState>(() => {
        if (!filters.sort) {
            return [];
        }

        return [
            {
                id: filters.sort,
                desc: filters.direction === 'desc',
            },
        ];
    }, [filters.direction, filters.sort]);

    // eslint-disable-next-line react-hooks/incompatible-library
    const table = useReactTable({
        data,
        columns,
        manualPagination: true,
        manualSorting: true,
        state: { sorting },
        getCoreRowModel: getCoreRowModel(),
        getRowId: (row) => String(row.id),
        onSortingChange: (updater) => {
            const next = functionalUpdate(updater, sorting);
            const first = next[0];
            const query = Object.fromEntries(
                Object.entries(filters).filter(
                    ([key, value]) =>
                        key !== 'page' && value !== undefined && value !== '',
                ),
            );

            if (first) {
                query.sort = first.id;
                query.direction = first.desc ? 'desc' : 'asc';
            } else {
                delete query.sort;
                delete query.direction;
            }

            router.get(route, query, {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            });
        },
    });

    return (
        <div className="overflow-x-auto">
            <ShadTable>
                <TableHeader>
                    {table.getHeaderGroups().map((headerGroup) => (
                        <TableRow
                            key={headerGroup.id}
                            className="bg-muted/50 text-xs uppercase text-muted-foreground hover:bg-muted/50"
                        >
                            {headerGroup.headers.map((header) => {
                                const sorted = header.column.getIsSorted();

                                return (
                                    <TableHead
                                        key={header.id}
                                        className="whitespace-nowrap px-6 py-4 font-bold"
                                    >
                                        {header.isPlaceholder ? null : (
                                            <button
                                                type="button"
                                                className={cn(
                                                    'inline-flex items-center gap-2 text-left uppercase',
                                                    !header.column.getCanSort() &&
                                                        'cursor-default',
                                                )}
                                                disabled={
                                                    !header.column.getCanSort()
                                                }
                                                onClick={header.column.getToggleSortingHandler()}
                                            >
                                                {flexRender(
                                                    header.column.columnDef
                                                        .header,
                                                    header.getContext(),
                                                )}
                                                {header.column.getCanSort() ? (
                                                    sorted === 'asc' ? (
                                                        <ArrowUp className="size-3.5" />
                                                    ) : sorted === 'desc' ? (
                                                        <ArrowDown className="size-3.5" />
                                                    ) : (
                                                        <ChevronsUpDown className="size-3.5 opacity-50" />
                                                    )
                                                ) : null}
                                            </button>
                                        )}
                                    </TableHead>
                                );
                            })}
                        </TableRow>
                    ))}
                </TableHeader>
                <TableBody>
                    {table.getRowModel().rows.length ? (
                        table.getRowModel().rows.map((row) => (
                            <TableRow
                                key={row.id}
                                className={cn(
                                    onRowClick &&
                                        'cursor-pointer hover:bg-muted/50',
                                    rowClassName?.(row.original),
                                )}
                                onClick={() => onRowClick?.(row.original)}
                            >
                                {row.getVisibleCells().map((cell) => (
                                    <TableCell
                                        key={cell.id}
                                        className="px-6 py-4"
                                    >
                                        {flexRender(
                                            cell.column.columnDef.cell,
                                            cell.getContext(),
                                        )}
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))
                    ) : (
                        <TableRow>
                            <TableCell
                                colSpan={columns.length}
                                className="px-6 py-10 text-center text-sm font-semibold text-muted-foreground"
                            >
                                No records found.
                            </TableCell>
                        </TableRow>
                    )}
                </TableBody>
            </ShadTable>
        </div>
    );
}
