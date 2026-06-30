import { ImgHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export default function ApplicationLogo({
    className,
    alt = 'Bidscape',
    ...props
}: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <>
            <img
                {...props}
                src="/images/brand/bidscape-logo-500px.jpg"
                alt={alt}
                className={cn(
                    'h-auto object-contain',
                    className,
                )}
            />
        </>
    );
}
