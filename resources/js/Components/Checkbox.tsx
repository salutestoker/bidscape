import { InputHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export default function Checkbox({
    className = '',
    ...props
}: InputHTMLAttributes<HTMLInputElement>) {
    return (
        <input
            {...props}
            type="checkbox"
            className={cn(
                'rounded border-input text-primary shadow-sm focus:ring-ring',
                className,
            )}
        />
    );
}
