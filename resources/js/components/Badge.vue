<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

type BadgeTone = 'green' | 'amber' | 'red' | 'gray' | 'blue';

const props = withDefaults(
    defineProps<{
        tone?: BadgeTone;
    }>(),
    {
        tone: 'gray',
    },
);

const toneClasses: Record<BadgeTone, string> = {
    green: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20',
    amber: 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20',
    red: 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20',
    gray: 'bg-ink-50 text-ink-600 ring-ink-500/20 dark:bg-ink-400/10 dark:text-ink-400 dark:ring-ink-400/20',
    blue: 'bg-primary-50 text-primary-700 ring-primary-700/10 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-400/30',
};

const classes = computed(() =>
    cn(
        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
        toneClasses[props.tone],
    ),
);
</script>

<template>
    <span :class="classes">
        <slot />
    </span>
</template>
