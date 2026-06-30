import '../css/app.css';
import './bootstrap';

import { Toast } from '@/Components/Bidscape/Toast';
import { TooltipProvider } from '@/Components/ui/tooltip';
import { ThemeProvider } from '@/Context/ThemeContext';
import { ToastProvider } from '@/Context/ToastContext';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ComponentType, StrictMode } from 'react';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ) as Promise<ComponentType>,
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <StrictMode>
                <ThemeProvider>
                    <ToastProvider>
                        <TooltipProvider>
                            <Toast />
                            <App {...props} />
                        </TooltipProvider>
                    </ToastProvider>
                </ThemeProvider>
            </StrictMode>,
        );
    },
    progress: {
        color: '#07883f',
    },
});
