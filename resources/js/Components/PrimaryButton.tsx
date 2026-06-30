import { ButtonHTMLAttributes } from 'react';
import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';

export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <Button
            {...props}
            className={cn('h-10 px-4 text-xs font-semibold uppercase tracking-widest', className)}
            disabled={disabled}
        >
            {children}
        </Button>
    );
}
