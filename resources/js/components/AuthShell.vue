<script setup lang="ts">
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import AppLogo from '@/components/AppLogo.vue';
import AppLogoMark from '@/components/AppLogoMark.vue';
import { brand } from '@/lib/brand';

/**
 * Split-screen guest layout shared by the central console and tenant
 * workspaces: a brand panel (desktop only) next to the form card.
 */
defineProps<{
    title: string;
    description: string;
    /** Who this door is for, e.g. "Consola central" or a tenant's company. */
    context: string;
}>();
</script>

<template>
    <div class="flex min-h-screen bg-ink-50 dark:bg-ink-950">
        <aside
            class="relative hidden w-[44%] max-w-2xl flex-col justify-between overflow-hidden bg-brand-night p-12 text-white lg:flex"
        >
            <AppLogoMark
                tone="light"
                class="pointer-events-none absolute -right-24 -bottom-24 size-[30rem] opacity-[0.06]"
            />
            <div
                class="pointer-events-none absolute -top-32 -left-32 size-96 rounded-full bg-primary-500/20 blur-3xl"
            />

            <AppLogo
                tone="light"
                size="md"
                :caption="brand.tagline"
                class="relative"
            />

            <div class="relative max-w-md">
                <p class="eyebrow text-accent-400">{{ context }}</p>
                <p
                    class="mt-4 text-4xl leading-tight font-extrabold tracking-tight"
                >
                    {{ brand.slogan }}
                </p>
                <p class="mt-4 text-base leading-7 text-ink-300">
                    Gestiona accesos, módulos y cumplimiento desde un solo
                    lugar, con trazabilidad en cada cambio.
                </p>
            </div>

            <p class="relative text-xs text-ink-400">
                © {{ new Date().getFullYear() }}
                {{ brand.wordmark.join('') }}
            </p>
        </aside>

        <main class="relative flex flex-1 flex-col">
            <div class="flex justify-end p-4 sm:p-6">
                <AppearanceToggle />
            </div>

            <div
                class="flex flex-1 items-center justify-center px-4 pb-16 sm:px-6"
            >
                <div class="w-full max-w-sm">
                    <div class="mb-8 lg:hidden">
                        <AppLogo size="md" :caption="context" />
                    </div>

                    <h1
                        class="text-2xl font-extrabold text-ink-950 dark:text-white"
                    >
                        {{ title }}
                    </h1>
                    <p
                        class="mt-2 mb-8 text-sm leading-6 text-ink-600 dark:text-ink-400"
                    >
                        {{ description }}
                    </p>

                    <slot />
                </div>
            </div>
        </main>
    </div>
</template>
