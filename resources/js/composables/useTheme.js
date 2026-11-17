import { ref } from 'vue';

const dark = ref(document.documentElement.classList.contains('dark'));

function setDark(value) {
    dark.value = value;
    document.documentElement.classList.toggle('dark', value);
    localStorage.setItem('theme', value ? 'dark' : 'light');
}

/**
 * Tailwind class-based dark mode, managed the shadcn way: a `dark` class on
 * the root element backed by localStorage persistence (bootstrapped inline
 * in app.blade.php before first paint).
 */
export function useTheme() {
    return {
        dark,
        toggle() {
            setDark(!dark.value);
        },
        setDark,
    };
}
