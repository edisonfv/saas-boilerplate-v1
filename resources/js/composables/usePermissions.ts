import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Reads the permissions shared by HandleInertiaRequests so pages can hide
 * links and actions the current user would get a 403 for.
 */
export function usePermissions() {
    const page = usePage();
    const permissions = computed(
        () => (page.props.auth?.permissions as string[] | undefined) ?? [],
    );

    function can(permission?: string): boolean {
        return !permission || permissions.value.includes(permission);
    }

    return { permissions, can };
}
