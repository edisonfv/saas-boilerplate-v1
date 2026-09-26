<script setup lang="ts">
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import AppLogo from '@/components/AppLogo.vue';
import FlashToast from '@/components/FlashToast.vue';
import { brand } from '@/lib/brand';

/**
 * Chrome for guest-facing pages that aren't login screens (public support
 * form, ticket tracking, surveys). Content sits on the 12-column grid.
 */
defineProps<{
    context?: string;
}>();
</script>

<template>
    <div class="flex min-h-screen flex-col bg-ink-50 dark:bg-ink-950">
        <header
            class="border-b border-ink-200 bg-white/80 backdrop-blur dark:border-ink-800 dark:bg-ink-950/80"
        >
            <div
                class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4 sm:px-6"
            >
                <AppLogo size="sm" :caption="context ?? 'Centro de ayuda'" />
                <AppearanceToggle />
            </div>
        </header>

        <main
            class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6 md:py-12"
        >
            <div class="app-grid">
                <slot />
            </div>
        </main>

        <footer
            class="mx-auto w-full max-w-6xl px-4 py-6 text-xs text-ink-500 sm:px-6"
        >
            © {{ new Date().getFullYear() }} {{ brand.wordmark.join('') }} ·
            {{ brand.tagline }}
        </footer>

        <FlashToast />
    </div>
</template>
