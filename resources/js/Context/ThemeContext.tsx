import {
    createContext,
    ReactNode,
    useContext,
    useEffect,
    useState,
} from 'react';

export type RawTheme = 'light' | 'dark' | 'system';
type Theme = 'light' | 'dark';

interface ThemeContextValue {
    theme: Theme;
    rawTheme: RawTheme;
    setRawTheme: (theme: RawTheme) => void;
}

function parseRawTheme(themeCandidate: string | null | undefined): RawTheme {
    if (
        themeCandidate === 'light' ||
        themeCandidate === 'dark' ||
        themeCandidate === 'system'
    ) {
        return themeCandidate;
    }

    return 'light';
}

function getSystemTheme(): Theme {
    if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
        return 'dark';
    }

    return 'light';
}

const ThemeContext = createContext<ThemeContextValue | undefined>(undefined);

export function ThemeProvider({ children }: { children: ReactNode }) {
    const [rawTheme, setRawTheme] = useState<RawTheme>(() =>
        parseRawTheme(localStorage.theme),
    );
    const [systemTheme, setSystemTheme] = useState<Theme>(() =>
        getSystemTheme(),
    );

    const theme = rawTheme === 'system' ? systemTheme : rawTheme;

    useEffect(() => {
        localStorage.theme = rawTheme;
        document.documentElement.classList.toggle('dark', theme === 'dark');
    }, [rawTheme, theme]);

    useEffect(() => {
        const handleStorageChange = (event: StorageEvent) => {
            if (event.key === 'theme') {
                setRawTheme(parseRawTheme(event.newValue));
            }
        };

        window.addEventListener('storage', handleStorageChange);

        return () => window.removeEventListener('storage', handleStorageChange);
    }, []);

    useEffect(() => {
        const colorSchemeMediaQuery = window.matchMedia(
            '(prefers-color-scheme: dark)',
        );
        const handleSystemThemeChange = () => setSystemTheme(getSystemTheme());

        colorSchemeMediaQuery.addEventListener(
            'change',
            handleSystemThemeChange,
        );

        return () => {
            colorSchemeMediaQuery.removeEventListener(
                'change',
                handleSystemThemeChange,
            );
        };
    }, []);

    return (
        <ThemeContext.Provider value={{ theme, rawTheme, setRawTheme }}>
            {children}
        </ThemeContext.Provider>
    );
}

export function useTheme() {
    const value = useContext(ThemeContext);

    if (!value) {
        throw new Error('useTheme must be used inside a ThemeProvider');
    }

    return value.theme;
}

export function useRawTheme() {
    const value = useContext(ThemeContext);

    if (!value) {
        throw new Error('useRawTheme must be used inside a ThemeProvider');
    }

    return value;
}
