<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import FlashToast from '@/components/FlashToast.vue';
import type { StorefrontInfo } from '@/lib/signatures';
import { ui } from '@/lib/ui';
import { home } from '@/routes';
import tenant from '@/routes/tenant';

/**
 * Chrome of a tenant's public signatures website: the tenant's own name
 * (not the platform brand), navigation to the landing sections, the
 * application CTA, staff access, and a floating WhatsApp button with the
 * tenant's configured number and message.
 */
withDefaults(
    defineProps<{
        storefront: StorefrontInfo;
        showNavigation?: boolean;
    }>(),
    { showNavigation: true },
);
</script>

<template>
    <div class="flex min-h-screen flex-col bg-ink-50 dark:bg-ink-950">
        <header
            class="sticky top-0 z-30 border-b border-ink-200 bg-white/90 backdrop-blur dark:border-ink-800 dark:bg-ink-950/90"
        >
            <div
                class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-4 px-4 sm:px-6"
            >
                <Link
                    :href="home().url"
                    class="flex min-w-0 items-center gap-2.5 font-extrabold text-ink-950 dark:text-white"
                >
                    <span
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary-600 text-sm text-white"
                        >{{ storefront.company_name.charAt(0) }}</span
                    >
                    <span class="truncate">{{ storefront.company_name }}</span>
                </Link>

                <nav
                    v-if="showNavigation"
                    class="hidden items-center gap-6 text-sm font-semibold text-ink-600 md:flex dark:text-ink-300"
                >
                    <a
                        href="/#precios"
                        class="hover:text-ink-950 dark:hover:text-white"
                        >Precios</a
                    >
                    <a
                        href="/#como-funciona"
                        class="hover:text-ink-950 dark:hover:text-white"
                        >Cómo funciona</a
                    >
                    <a
                        href="/#requisitos"
                        class="hover:text-ink-950 dark:hover:text-white"
                        >Requisitos</a
                    >
                    <a
                        href="/#preguntas"
                        class="hover:text-ink-950 dark:hover:text-white"
                        >Preguntas</a
                    >
                </nav>

                <div class="flex shrink-0 items-center gap-2">
                    <AppearanceToggle class="hidden sm:flex" />
                    <Link
                        v-if="showNavigation"
                        :href="tenant.signatures.storefront.create().url"
                        :class="[ui.buttonPrimary, 'h-9 px-3.5']"
                    >
                        Solicitar firma
                    </Link>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer
            class="border-t border-ink-200 bg-white dark:border-ink-800 dark:bg-ink-900"
        >
            <div
                class="mx-auto flex w-full max-w-6xl flex-col gap-3 px-4 pt-8 pb-24 text-sm text-ink-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:pr-24 sm:pb-8"
            >
                <p>
                    © {{ new Date().getFullYear() }}
                    {{ storefront.company_name }} · Firmas electrónicas emitidas
                    por Uanataca Ecuador
                </p>
                <div class="flex flex-wrap items-center gap-4">
                    <a
                        v-if="storefront.contact_email"
                        :href="`mailto:${storefront.contact_email}`"
                        :class="ui.link"
                        >{{ storefront.contact_email }}</a
                    >
                    <span v-if="storefront.contact_phone">{{
                        storefront.contact_phone
                    }}</span>
                    <Link
                        href="/login"
                        class="hover:text-ink-950 dark:hover:text-white"
                        >Acceso para personal</Link
                    >
                </div>
            </div>
        </footer>

        <a
            v-if="storefront.whatsapp_url"
            :href="storefront.whatsapp_url"
            target="_blank"
            rel="noopener"
            aria-label="Escríbenos por WhatsApp"
            class="fixed right-5 bottom-5 z-40 flex size-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg shadow-black/20 transition hover:scale-105"
        >
            <svg
                viewBox="0 0 24 24"
                class="size-7"
                fill="currentColor"
                aria-hidden="true"
            >
                <path
                    d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.88 1.21 3.08.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.41-.07-.12-.27-.2-.57-.35ZM12.04 21.5h-.01a9.45 9.45 0 0 1-4.82-1.32l-.35-.2-3.58.94.96-3.49-.23-.36A9.42 9.42 0 0 1 2.6 12c0-5.2 4.24-9.43 9.45-9.43 2.52 0 4.89.98 6.67 2.77a9.37 9.37 0 0 1 2.76 6.67c0 5.2-4.24 9.44-9.44 9.44Zm8.04-17.49A11.3 11.3 0 0 0 12.04.68C5.77.68.66 5.78.66 12.05c0 2 .52 3.96 1.52 5.69L.57 23.6l6-1.57a11.35 11.35 0 0 0 5.46 1.39h.01c6.27 0 11.38-5.1 11.38-11.37 0-3.04-1.18-5.9-3.34-8.04Z"
                />
            </svg>
        </a>

        <FlashToast />
    </div>
</template>
