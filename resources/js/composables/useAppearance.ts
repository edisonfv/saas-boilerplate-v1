import { onMounted, ref } from 'vue';

export type Appearance = 'light' | 'dark' | 'system';

function mediaQuery() {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
}

function getStoredAppearance() {
    if (typeof window === 'undefined') {
        return null;
    }

    return localStorage.getItem('appearance') as Appearance | null;
}

function setCookie(name: string, value: string, days = 365) {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
}

export function updateTheme(value: Appearance) {
    if (typeof window === 'undefined') {
        return;
    }

    const isDark =
        value === 'system'
            ? (mediaQuery()?.matches ?? false)
            : value === 'dark';

    document.documentElement.classList.toggle('dark', isDark);
}

function handleSystemThemeChange() {
    updateTheme(getStoredAppearance() ?? 'system');
}

export function initializeTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    updateTheme(getStoredAppearance() ?? 'system');

    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

const appearance = ref<Appearance>('system');

export function useAppearance() {
    onMounted(() => {
        const savedAppearance = getStoredAppearance();

        if (savedAppearance) {
            appearance.value = savedAppearance;
        }
    });

    function updateAppearance(value: Appearance) {
        appearance.value = value;

        localStorage.setItem('appearance', value);
        setCookie('appearance', value);

        updateTheme(value);
    }

    return {
        appearance,
        updateAppearance,
    };
}
