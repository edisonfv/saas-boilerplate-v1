<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

defineProps<{
    links: PaginationLink[];
    from?: number | null;
    to?: number | null;
    total?: number;
}>();
</script>

<template>
    <div
        v-if="links.length > 3"
        class="flex flex-col items-center justify-between gap-3 border-t border-ink-200 pt-4 sm:flex-row dark:border-ink-800"
    >
        <p
            v-if="total !== undefined"
            class="text-sm text-ink-500 dark:text-ink-400"
        >
            Mostrando {{ from }}–{{ to }} de {{ total }}
        </p>

        <div class="flex flex-wrap items-center gap-1">
            <template v-for="(link, index) in links" :key="index">
                <span
                    v-if="!link.url"
                    class="rounded-lg px-3 py-1.5 text-sm text-ink-300 dark:text-ink-700"
                    v-html="link.label"
                />
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    :class="[
                        'rounded-lg px-3 py-1.5 text-sm font-medium transition-colors',
                        link.active
                            ? 'bg-primary-600 text-white'
                            : 'text-ink-600 hover:bg-ink-100 dark:text-ink-300 dark:hover:bg-ink-800',
                    ]"
                >
                    <span v-html="link.label" />
                </Link>
            </template>
        </div>
    </div>
</template>
