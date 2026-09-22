<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import type { IconName } from '@/types/icon';

defineProps<{
    label: string;
    value: string | number;
    icon: IconName;
    helper?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-24 items-center gap-3.5 rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
    >
        <div
            class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300"
        >
            <Icon :name="icon" />
        </div>
        <div class="min-w-0">
            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">
                {{ label }}
            </p>
            <p
                class="mt-0.5 text-xl font-semibold text-slate-950 dark:text-white"
            >
                {{ value }}
            </p>
            <p
                v-if="helper"
                class="mt-0.5 text-xs text-slate-500 dark:text-slate-400"
            >
                {{ helper }}
            </p>
        </div>
    </div>
</template>
