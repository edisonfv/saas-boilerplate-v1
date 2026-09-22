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
        class="rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
    >
        <div
            v-if="title"
            class="border-b border-slate-200 px-5 py-4 dark:border-slate-800"
        >
            <h2
                class="text-sm font-semibold tracking-wide text-slate-950 dark:text-white"
            >
                {{ title }}
            </h2>
        </div>
        <div class="p-5">
            <slot />
        </div>
    </div>
</template>
