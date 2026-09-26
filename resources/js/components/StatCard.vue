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
        class="flex min-h-24 items-center gap-4 rounded-xl border border-ink-200 bg-white p-4 shadow-sm dark:border-ink-800 dark:bg-ink-900"
    >
        <div
            class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-500/15 dark:text-primary-300"
        >
            <Icon :name="icon" />
        </div>
        <div class="min-w-0">
            <p class="text-xs font-medium text-ink-500 dark:text-ink-400">
                {{ label }}
            </p>
            <p
                class="mt-0.5 text-2xl font-extrabold tracking-tight text-ink-950 tabular-nums dark:text-white"
            >
                {{ value }}
            </p>
            <p
                v-if="helper"
                class="mt-0.5 text-xs text-ink-500 dark:text-ink-400"
            >
                {{ helper }}
            </p>
        </div>
    </div>
</template>
