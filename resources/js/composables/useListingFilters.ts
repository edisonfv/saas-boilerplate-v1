import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

/**
 * Drives a paginated Inertia index page's search box and sortable column
 * headers by round-tripping to the server (spatie/laravel-query-builder
 * reads `filter[search]`/`sort` on the backend). Only the given page prop
 * key is reloaded (`only`), so the rest of the shared page props don't
 * re-fetch on every keystroke.
 */
export function useListingFilters(
    url: string,
    only: string[],
    initial: { search?: string; sort?: string } = {},
    extraParams: () => Record<string, string> = () => ({}),
) {
    const search = ref(initial.search ?? '');
    const sort = ref(initial.sort ?? '');

    function reload() {
        router.get(
            url,
            {
                ...(search.value ? { 'filter[search]': search.value } : {}),
                ...(sort.value ? { sort: sort.value } : {}),
                ...extraParams(),
            },
            { preserveState: true, preserveScroll: true, replace: true, only },
        );
    }

    let debounceTimer: ReturnType<typeof setTimeout> | undefined;

    watch(search, () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(reload, 300);
    });

    function toggleSort(column: string) {
        sort.value = sort.value === column ? `-${column}` : column;
        reload();
    }

    function sortIndicator(column: string): 'asc' | 'desc' | null {
        if (sort.value === column) {
            return 'asc';
        }

        if (sort.value === `-${column}`) {
            return 'desc';
        }

        return null;
    }

    return {
        search,
        sort,
        toggleSort,
        sortIndicator,
        reload,
    };
}
