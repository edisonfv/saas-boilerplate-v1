<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import AppLogo from '@/components/AppLogo.vue';
import AppLogoMark from '@/components/AppLogoMark.vue';
import Icon from '@/components/Icon.vue';
import { brand } from '@/lib/brand';
import central from '@/routes/central';
import type { IconName } from '@/types/icon';

const page = usePage();
const isAuthenticated = computed(() => Boolean(page.props.auth?.user));

const pillars: { icon: IconName; title: string; description: string }[] = [
    {
        icon: 'building',
        title: 'Multi-tenant',
        description:
            'Cada organización con su propia base de datos, dominio y usuarios.',
    },
    {
        icon: 'shield',
        title: 'Acceso controlado',
        description:
            'Roles y permisos granulares, tanto en la consola como en cada tenant.',
    },
    {
        icon: 'layers',
        title: 'Planes y módulos',
        description:
            'Catálogo comercial con módulos, features y límites por suscripción.',
    },
];
</script>

<template>
    <Head title="Bienvenido" />

    <div
        class="relative flex min-h-screen flex-col overflow-hidden bg-ink-50 dark:bg-ink-950"
    >
        <AppLogoMark
            class="pointer-events-none absolute -top-40 -right-40 size-[40rem] opacity-[0.05] dark:opacity-[0.06]"
            tone="default"
        />

        <header
            class="relative mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-5 sm:px-6"
        >
            <AppLogo size="sm" />
            <div class="flex items-center gap-3">
                <AppearanceToggle />
                <Link
                    :href="
                        isAuthenticated
                            ? central.dashboard().url
                            : central.login().url
                    "
                    class="hidden h-9 items-center rounded-lg px-3 text-sm font-semibold text-ink-700 transition hover:bg-white hover:text-ink-950 sm:inline-flex dark:text-ink-200 dark:hover:bg-ink-900 dark:hover:text-white"
                >
                    {{ isAuthenticated ? 'Ir a la consola' : 'Iniciar sesión' }}
                </Link>
            </div>
        </header>

        <main
            class="relative mx-auto flex w-full max-w-6xl flex-1 flex-col justify-center px-4 py-16 sm:px-6"
        >
            <p class="eyebrow text-primary-600 dark:text-primary-400">
                {{ brand.tagline }}
            </p>
            <h1
                class="mt-5 max-w-3xl text-4xl leading-[1.08] font-extrabold tracking-tight text-brand-indigo sm:text-6xl dark:text-white"
            >
                {{ brand.slogan }}
            </h1>
            <p
                class="mt-6 max-w-xl text-base leading-7 text-ink-600 sm:text-lg dark:text-ink-300"
            >
                La plataforma para operar tus clientes, planes y accesos con la
                trazabilidad que exige el cumplimiento.
            </p>

            <div class="mt-10 flex flex-wrap items-center gap-3">
                <Link
                    :href="
                        isAuthenticated
                            ? central.dashboard().url
                            : central.login().url
                    "
                    class="inline-flex h-12 items-center gap-2 rounded-xl bg-primary-600 px-6 text-sm font-bold text-white shadow-lg shadow-primary-600/25 transition hover:bg-primary-700"
                >
                    {{
                        isAuthenticated
                            ? 'Ir a la consola central'
                            : 'Acceder a la consola'
                    }}
                    <Icon name="arrow-right" class="size-4" />
                </Link>
                <span
                    class="inline-flex items-center gap-2 text-sm text-ink-500 dark:text-ink-400"
                >
                    <span class="size-2 rounded-full bg-accent-500" />
                    Acceso exclusivo para el equipo de plataforma
                </span>
            </div>

            <div class="mt-20 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div
                    v-for="pillar in pillars"
                    :key="pillar.title"
                    class="rounded-2xl border border-ink-200 bg-white/70 p-6 backdrop-blur dark:border-ink-800 dark:bg-ink-900/70"
                >
                    <div
                        class="flex size-11 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-500/15 dark:text-primary-300"
                    >
                        <Icon :name="pillar.icon" />
                    </div>
                    <p class="mt-4 font-bold text-ink-950 dark:text-white">
                        {{ pillar.title }}
                    </p>
                    <p
                        class="mt-1.5 text-sm leading-6 text-ink-500 dark:text-ink-400"
                    >
                        {{ pillar.description }}
                    </p>
                </div>
            </div>
        </main>

        <footer
            class="relative mx-auto w-full max-w-6xl px-4 py-6 text-xs text-ink-500 sm:px-6 dark:text-ink-400"
        >
            © {{ new Date().getFullYear() }} {{ brand.wordmark.join('') }}
        </footer>
    </div>
</template>
