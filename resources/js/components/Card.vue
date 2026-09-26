<script setup lang="ts">
withDefaults(
    defineProps<{
        title?: string;
    }>(),
    {
        title: undefined,
    },
);
</script>

<template>
    <div
        class="rounded-xl border border-ink-200 bg-white shadow-sm shadow-ink-950/[0.03] dark:border-ink-800 dark:bg-ink-900"
    >
        <div
            v-if="title"
            class="border-b border-ink-200 px-5 py-4 dark:border-ink-800"
        >
            <h2 class="text-sm font-bold text-ink-950 dark:text-white">
                {{ title }}
            </h2>
        </div>
        <div class="p-5">
            <slot />
        </div>
    </div>
</template>
