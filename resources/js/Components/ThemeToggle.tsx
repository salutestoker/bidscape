import { Moon, Sun } from 'lucide-react';
import { useRawTheme } from '@/Context/ThemeContext';
import { cn } from '@/lib/utils';
import { Switch } from '@/Components/ui/switch';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/Components/ui/tooltip';

export default function ThemeToggle({ className }: { className?: string }) {
    const { rawTheme, setRawTheme } = useRawTheme();
    const checked = rawTheme === 'dark';

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <label
                    className={cn(
                        'flex items-center gap-2 rounded-lg border border-border bg-card px-3 py-2 text-muted-foreground shadow-sm',
                        className,
                    )}
                >
                    <Moon className="size-4" aria-hidden="true" />
                    <Switch
                        aria-label="Toggle dark mode"
                        checked={checked}
                        onCheckedChange={(value) =>
                            setRawTheme(value ? 'dark' : 'light')
                        }
                    />
                    <Sun className="size-4" aria-hidden="true" />
                </label>
            </TooltipTrigger>
            <TooltipContent>Toggle light and dark mode</TooltipContent>
        </Tooltip>
    );
}
