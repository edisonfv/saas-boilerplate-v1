<script setup lang="ts">
defineProps<{
    title: string;
    description: string;
}>();
</script>

<template>
    <div
        class="flex min-h-screen items-center justify-center bg-slate-50 p-6 dark:bg-slate-950"
    >
        <div class="w-full max-w-md">
            <div class="mb-8 flex flex-col items-center gap-3 text-center">
                <div
                    class="flex size-11 items-center justify-center rounded-lg bg-slate-950 text-lg font-bold text-white dark:bg-white dark:text-slate-950"
                >
                    S
                </div>
                <div>
                    <p class="font-semibold text-slate-950 dark:text-white">
                        SaaS Central
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Acceso del equipo de plataforma
                    </p>
                </div>
            </div>

            <div
                class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <h1
                    class="text-lg font-semibold text-slate-950 dark:text-white"
                >
                    {{ title }}
                </h1>
                <p class="mt-2 mb-6 text-sm text-slate-500 dark:text-slate-400">
                    {{ description }}
                </p>

                <slot />
            </div>
        </div>
    </div>
</template>
