import { ref } from 'vue';

const storageKey = 'app-sidebar-collapsed';

/*
 * Module-scoped on purpose: pages wrap themselves in their layout, so the
 * shell re-mounts on every Inertia visit. Shared state keeps the sidebar
 * width stable between pages instead of re-reading storage and animating.
 */
const isCollapsed = ref(false);
const isRestored = ref(false);

function readStoredValue(): boolean {
    try {
        return localStorage.getItem(storageKey) === 'true';
    } catch {
        return false;
    }
}

export function useSidebar() {
    /** Call from onMounted — localStorage is not available during SSR. */
    function restore(): void {
        if (isRestored.value) {
            return;
        }

        isCollapsed.value = readStoredValue();

        // Enable width transitions only after the first paint so the
        // restored state is applied without animating from "expanded".
        requestAnimationFrame(() => {
            isRestored.value = true;
        });
    }

    function toggle(): void {
        isCollapsed.value = !isCollapsed.value;

        try {
            localStorage.setItem(storageKey, String(isCollapsed.value));
        } catch {
            // Storage can be unavailable (private mode); the toggle still works.
        }
    }

    return { isCollapsed, isRestored, restore, toggle };
}
