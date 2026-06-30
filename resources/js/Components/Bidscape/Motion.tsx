import { useGSAP } from '@gsap/react';
import gsap from 'gsap';
import { motion, useReducedMotion, type Variants } from 'motion/react';
import { ReactNode, useRef } from 'react';
import { cn } from '@/lib/utils';

gsap.registerPlugin(useGSAP);

const pageVariants: Variants = {
    initial: { opacity: 0, y: 10 },
    animate: { opacity: 1, y: 0 },
    exit: { opacity: 0, y: -8 },
};

const itemVariants: Variants = {
    initial: { opacity: 0, y: 12 },
    animate: { opacity: 1, y: 0 },
};

export function AnimatedPage({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    const shouldReduceMotion = useReducedMotion();

    return (
        <motion.div
            className={className}
            initial="initial"
            animate="animate"
            exit="exit"
            variants={pageVariants}
            transition={{
                duration: shouldReduceMotion ? 0 : 0.18,
                ease: 'easeOut',
            }}
            style={{ willChange: 'opacity, transform' }}
        >
            {children}
        </motion.div>
    );
}

export function MotionItem({
    children,
    className,
    delay = 0,
}: {
    children: ReactNode;
    className?: string;
    delay?: number;
}) {
    const shouldReduceMotion = useReducedMotion();

    return (
        <motion.div
            className={className}
            initial="initial"
            animate="animate"
            variants={itemVariants}
            transition={{
                type: 'spring',
                stiffness: 340,
                damping: 30,
                delay: shouldReduceMotion ? 0 : delay,
                duration: shouldReduceMotion ? 0 : undefined,
            }}
            style={{ willChange: 'opacity, transform' }}
        >
            {children}
        </motion.div>
    );
}

export function StaggeredReveal({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    const scope = useRef<HTMLDivElement>(null);
    const shouldReduceMotion = useReducedMotion();

    useGSAP(
        () => {
            if (shouldReduceMotion) {
                return;
            }

            gsap.from('[data-reveal-item]', {
                autoAlpha: 0,
                y: 12,
                duration: 0.35,
                ease: 'power2.out',
                stagger: 0.035,
            });
        },
        { dependencies: [shouldReduceMotion], scope },
    );

    return (
        <div ref={scope} className={cn('contents', className)}>
            {children}
        </div>
    );
}
